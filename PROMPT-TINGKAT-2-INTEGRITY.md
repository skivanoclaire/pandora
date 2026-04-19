# Prompt untuk Claude Code di VM PANDORA — Tingkat 2: Integritas Log Presensi

Copy-paste seluruh teks di bawah ini ke Claude Code di terminal VM PANDORA (project Laravel ~/pandora/src).

---

Saya butuh kamu menambahkan **Integrity Layer (Tingkat 2)** untuk aplikasi PANDORA. Tujuannya: log presensi tidak bisa dimanipulasi diam-diam, dan bukti integritasnya bisa diverifikasi secara independen oleh auditor eksternal (BPK, Inspektorat) tanpa harus mempercayai operator PANDORA.

Pendekatan yang dipakai: **hash-chain internal di PostgreSQL + anchoring Merkle root harian ke Bitcoin via OpenTimestamps**.

## PRINSIP DESAIN (WAJIB DIPATUHI)

1. **log_presensi harus append-only.** Tidak boleh ada UPDATE atau DELETE fisik pada baris existing. Koreksi data dilakukan via soft-invalidation (kolom `invalidated_at`), bukan penghapusan.

2. **Hash chain dihitung saat INSERT.** Setiap baris log_presensi menyimpan SHA-256 dari payload + hash baris sebelumnya. Kalau ada yang utak-atik data lama, semua hash setelahnya tidak cocok.

3. **Satu penulis untuk log_presensi.** Hanya Laravel (via job sinkronisasi SIMPEG atau Observer) yang menulis ke tabel ini. Python FastAPI / ML pipeline HANYA READ.

4. **Hasil analitik di tabel terpisah.** Output anomaly detection, clustering, feature engineering semua masuk ke tabel baru (anomaly_flags, features_kehadiran, dll). Jangan modifikasi log_presensi dari pipeline ML.

5. **Anchor harian ke OTS.** Setiap akhir hari, hitung Merkle root dari semua log_presensi hari itu, kirim ke OpenTimestamps, simpan bukti `.ots`.

## KONTEKS STACK EXISTING

- Laravel 13 di `~/pandora/src`
- PostgreSQL 16 + PostGIS
- Python FastAPI (kontainer `pandora-ml`)
- Redis 7
- Docker Compose di `~/pandora/docker-compose.yml`
- Nginx + SSL

## FILE YANG PERLU DIBUAT/DIUBAH

### A. DATABASE MIGRATIONS

#### 1. `database/migrations/xxxx_add_hash_chain_to_log_presensi.php`

Tambah kolom ke tabel `log_presensi` (buat migration untuk `log_presensi` kalau belum ada — struktur minimal: id, nip, waktu, latitude, longitude, status, device_id, source, created_at, updated_at; sesuaikan dengan skema SIMPEG yang sudah ada):

```php
Schema::table('log_presensi', function (Blueprint $table) {
    $table->binary('hash_current', 32)->nullable()->after('updated_at');
    $table->binary('hash_prev', 32)->nullable()->after('hash_current');
    $table->bigInteger('sequence_no')->nullable()->after('hash_prev');
    $table->timestampTz('invalidated_at')->nullable()->after('sequence_no');
    $table->text('invalidation_reason')->nullable()->after('invalidated_at');
    $table->foreignId('invalidated_by')->nullable()->constrained('users')->after('invalidation_reason');

    $table->index('sequence_no');
    $table->index('invalidated_at');
});
```

#### 2. `database/migrations/xxxx_create_ledger_anchor_table.php`

```php
Schema::create('ledger_anchor', function (Blueprint $table) {
    $table->id();
    $table->date('tanggal')->unique();
    $table->integer('jumlah_record');
    $table->binary('merkle_root', 32);
    $table->bigInteger('sequence_start');
    $table->bigInteger('sequence_end');
    $table->binary('ots_proof_incomplete')->nullable();
    $table->binary('ots_proof_complete')->nullable();
    $table->string('btc_block_hash', 64)->nullable();
    $table->bigInteger('btc_block_height')->nullable();
    $table->timestampTz('anchored_at')->nullable();
    $table->timestampTz('confirmed_at')->nullable();
    $table->enum('status', ['pending', 'anchored', 'confirmed', 'failed'])->default('pending');
    $table->text('error_message')->nullable();
    $table->timestampsTz();
});
```

#### 3. `database/migrations/xxxx_create_anomaly_flags_table.php`

Tabel terpisah untuk output pipeline ML (supaya log_presensi tetap bersih):

```php
Schema::create('anomaly_flags', function (Blueprint $table) {
    $table->id();
    $table->foreignId('log_presensi_id')->constrained('log_presensi');
    $table->string('anomaly_type', 50); // fake_gps, late_repeat, geo_outlier, dll
    $table->decimal('confidence', 5, 4);
    $table->jsonb('metadata')->nullable();
    $table->string('model_version', 20);
    $table->timestampTz('detected_at');
    $table->timestampsTz();

    $table->index(['log_presensi_id', 'anomaly_type']);
});
```

### B. LARAVEL SIDE

#### 4. `app/Models/LogPresensi.php`

Model dengan guard untuk mencegah UPDATE/DELETE:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class LogPresensi extends Model
{
    protected $table = 'log_presensi';
    protected $guarded = [];

    protected $casts = [
        'waktu' => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    // Cegah UPDATE/DELETE — append-only
    protected static function booted()
    {
        static::updating(function ($model) {
            // Izinkan hanya update kolom invalidation + hash (saat initial insert)
            $allowed = ['invalidated_at', 'invalidation_reason', 'invalidated_by', 'updated_at'];
            $dirty = array_keys($model->getDirty());
            $forbidden = array_diff($dirty, $allowed);
            if (!empty($forbidden)) {
                throw new RuntimeException(
                    'log_presensi is append-only. Cannot update: ' . implode(',', $forbidden)
                );
            }
        });

        static::deleting(function ($model) {
            throw new RuntimeException('log_presensi is append-only. Use soft invalidation instead.');
        });
    }

    public function isValid(): bool
    {
        return $this->invalidated_at === null;
    }

    public function invalidate(string $reason, int $userId): void
    {
        $this->invalidated_at = now();
        $this->invalidation_reason = $reason;
        $this->invalidated_by = $userId;
        $this->save();
    }
}
```

#### 5. `app/Services/HashChainService.php`

Service untuk menghitung hash rantai. Gunakan PostgreSQL advisory lock supaya aman dari race condition:

```php
<?php

namespace App\Services;

use App\Models\LogPresensi;
use Illuminate\Support\Facades\DB;

class HashChainService
{
    private const LOCK_KEY = 7842911; // arbitrary, unique for log_presensi chain

    public function appendHashed(array $payload): LogPresensi
    {
        return DB::transaction(function () use ($payload) {
            // Advisory lock — hanya satu worker yang bisa menghitung hash_prev pada satu waktu
            DB::statement('SELECT pg_advisory_xact_lock(?)', [self::LOCK_KEY]);

            $last = LogPresensi::orderByDesc('sequence_no')->first();
            $seq = $last ? $last->sequence_no + 1 : 1;
            $prevHash = $last ? $last->hash_current : str_repeat("\0", 32);

            // Canonical serialization — urutan key harus deterministik
            $canonical = $this->canonicalize($payload);
            $payloadHash = hash('sha256', $canonical, true);
            $hashCurrent = hash('sha256', $prevHash . $payloadHash . pack('J', $seq), true);

            $row = LogPresensi::create(array_merge($payload, [
                'sequence_no' => $seq,
                'hash_prev' => $prevHash,
                'hash_current' => $hashCurrent,
            ]));

            return $row;
        });
    }

    public function appendBatch(array $rows): array
    {
        return DB::transaction(function () use ($rows) {
            DB::statement('SELECT pg_advisory_xact_lock(?)', [self::LOCK_KEY]);

            $last = LogPresensi::orderByDesc('sequence_no')->first();
            $seq = $last ? $last->sequence_no : 0;
            $prevHash = $last ? $last->hash_current : str_repeat("\0", 32);

            $inserted = [];
            foreach ($rows as $payload) {
                $seq++;
                $canonical = $this->canonicalize($payload);
                $payloadHash = hash('sha256', $canonical, true);
                $hashCurrent = hash('sha256', $prevHash . $payloadHash . pack('J', $seq), true);

                $row = LogPresensi::create(array_merge($payload, [
                    'sequence_no' => $seq,
                    'hash_prev' => $prevHash,
                    'hash_current' => $hashCurrent,
                ]));

                $inserted[] = $row;
                $prevHash = $hashCurrent;
            }

            return $inserted;
        });
    }

    public function verifyChain(?int $from = null, ?int $to = null): array
    {
        $q = LogPresensi::orderBy('sequence_no');
        if ($from) $q->where('sequence_no', '>=', $from);
        if ($to) $q->where('sequence_no', '<=', $to);

        $broken = [];
        $prevHash = null;

        foreach ($q->cursor() as $row) {
            $expectedPrev = $prevHash ?? str_repeat("\0", 32);
            if ($row->hash_prev !== $expectedPrev) {
                $broken[] = ['seq' => $row->sequence_no, 'reason' => 'hash_prev mismatch'];
            }

            $canonical = $this->canonicalize($row->toArray());
            $payloadHash = hash('sha256', $canonical, true);
            $expected = hash('sha256', $row->hash_prev . $payloadHash . pack('J', $row->sequence_no), true);

            if ($expected !== $row->hash_current) {
                $broken[] = ['seq' => $row->sequence_no, 'reason' => 'hash_current mismatch'];
            }

            $prevHash = $row->hash_current;
        }

        return ['verified' => empty($broken), 'broken' => $broken];
    }

    private function canonicalize(array $payload): string
    {
        // Pilih kolom yang harus masuk hash (exclude kolom hash itu sendiri + timestamps Laravel)
        $keys = ['nip', 'waktu', 'latitude', 'longitude', 'status', 'device_id', 'source'];
        $subset = [];
        foreach ($keys as $k) {
            $subset[$k] = $payload[$k] ?? null;
        }
        ksort($subset);
        return json_encode($subset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
```

#### 6. `app/Services/MerkleTreeService.php`

```php
<?php

namespace App\Services;

class MerkleTreeService
{
    public function root(array $leaves): string
    {
        if (empty($leaves)) {
            return str_repeat("\0", 32);
        }

        $level = $leaves;
        while (count($level) > 1) {
            $next = [];
            for ($i = 0; $i < count($level); $i += 2) {
                $left = $level[$i];
                $right = $level[$i + 1] ?? $left; // duplicate last jika ganjil
                $next[] = hash('sha256', $left . $right, true);
            }
            $level = $next;
        }

        return $level[0];
    }
}
```

#### 7. `app/Console/Commands/LedgerAnchorDaily.php`

Command yang dipanggil scheduler setiap jam 23:55:

```php
<?php

namespace App\Console\Commands;

use App\Models\LogPresensi;
use App\Services\MerkleTreeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LedgerAnchorDaily extends Command
{
    protected $signature = 'ledger:anchor-daily {--date=}';
    protected $description = 'Hitung Merkle root harian dan kirim ke pandora-anchor service';

    public function handle(MerkleTreeService $merkle)
    {
        $date = $this->option('date') ?? now()->toDateString();

        $rows = LogPresensi::whereDate('created_at', $date)
            ->orderBy('sequence_no')
            ->get(['sequence_no', 'hash_current']);

        if ($rows->isEmpty()) {
            $this->warn("Tidak ada record pada $date");
            return self::SUCCESS;
        }

        $leaves = $rows->pluck('hash_current')->toArray();
        $root = $merkle->root($leaves);

        $anchor = DB::table('ledger_anchor')->updateOrInsert(
            ['tanggal' => $date],
            [
                'jumlah_record' => $rows->count(),
                'merkle_root' => $root,
                'sequence_start' => $rows->first()->sequence_no,
                'sequence_end' => $rows->last()->sequence_no,
                'status' => 'pending',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Kirim ke pandora-anchor service untuk submit ke OTS
        $response = Http::timeout(30)->post(env('ANCHOR_SERVICE_URL', 'http://pandora-anchor:8700') . '/anchor', [
            'date' => $date,
            'merkle_root_hex' => bin2hex($root),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            DB::table('ledger_anchor')->where('tanggal', $date)->update([
                'ots_proof_incomplete' => base64_decode($data['ots_proof_base64']),
                'anchored_at' => now(),
                'status' => 'anchored',
            ]);
            $this->info("Anchored $date, root=" . bin2hex($root));
        } else {
            DB::table('ledger_anchor')->where('tanggal', $date)->update([
                'status' => 'failed',
                'error_message' => $response->body(),
            ]);
            $this->error("Gagal anchor: " . $response->body());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
```

#### 8. `app/Console/Commands/LedgerAnchorUpgrade.php`

Upgrade bukti `.ots` yang belum terkonfirmasi Bitcoin (jalan setiap jam 06:00):

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LedgerAnchorUpgrade extends Command
{
    protected $signature = 'ledger:anchor-upgrade';
    protected $description = 'Upgrade bukti OTS yang belum confirmed';

    public function handle()
    {
        $pending = DB::table('ledger_anchor')
            ->where('status', 'anchored')
            ->whereNull('confirmed_at')
            ->get();

        foreach ($pending as $row) {
            $response = Http::timeout(60)->post(env('ANCHOR_SERVICE_URL', 'http://pandora-anchor:8700') . '/upgrade', [
                'ots_proof_base64' => base64_encode($row->ots_proof_incomplete),
            ]);

            if ($response->successful() && $response->json('upgraded')) {
                $data = $response->json();
                DB::table('ledger_anchor')->where('id', $row->id)->update([
                    'ots_proof_complete' => base64_decode($data['ots_proof_base64']),
                    'btc_block_hash' => $data['btc_block_hash'] ?? null,
                    'btc_block_height' => $data['btc_block_height'] ?? null,
                    'confirmed_at' => now(),
                    'status' => 'confirmed',
                ]);
                $this->info("Confirmed {$row->tanggal}");
            }
        }
    }
}
```

#### 9. `app/Console/Commands/LedgerVerify.php`

```php
<?php

namespace App\Console\Commands;

use App\Services\HashChainService;
use Illuminate\Console\Command;

class LedgerVerify extends Command
{
    protected $signature = 'ledger:verify {--from=} {--to=}';
    protected $description = 'Verifikasi integritas hash chain log_presensi';

    public function handle(HashChainService $svc)
    {
        $result = $svc->verifyChain($this->option('from'), $this->option('to'));
        if ($result['verified']) {
            $this->info('Hash chain VALID');
            return self::SUCCESS;
        }
        $this->error('Hash chain BROKEN:');
        foreach ($result['broken'] as $b) {
            $this->line("  seq={$b['seq']} reason={$b['reason']}");
        }
        return self::FAILURE;
    }
}
```

#### 10. Register scheduler di `routes/console.php` (Laravel 11/12/13 style)

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('ledger:anchor-daily')->dailyAt('23:55')->onOneServer();
Schedule::command('ledger:anchor-upgrade')->dailyAt('06:00')->onOneServer();
Schedule::command('ledger:verify')->weeklyOn(0, '02:00')->onOneServer(); // audit self-check mingguan
```

#### 11. `app/Http/Controllers/IntegrityController.php`

Endpoint publik untuk auditor eksternal (tanpa autentikasi, rate-limited):

```php
<?php

namespace App\Http\Controllers;

use App\Models\LogPresensi;
use App\Services\MerkleTreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrityController extends Controller
{
    public function verifyRecord(int $id, MerkleTreeService $merkle)
    {
        $row = LogPresensi::findOrFail($id);
        $date = $row->created_at->toDateString();
        $anchor = DB::table('ledger_anchor')->where('tanggal', $date)->first();

        if (!$anchor) {
            return response()->json(['status' => 'not_anchored'], 200);
        }

        // Rebuild Merkle path
        $allHashes = LogPresensi::whereDate('created_at', $date)
            ->orderBy('sequence_no')
            ->pluck('hash_current')
            ->toArray();

        $recomputedRoot = $merkle->root($allHashes);

        return response()->json([
            'record_id' => $id,
            'sequence_no' => $row->sequence_no,
            'record_hash_hex' => bin2hex($row->hash_current),
            'anchor_date' => $date,
            'merkle_root_hex' => bin2hex($anchor->merkle_root),
            'recomputed_root_hex' => bin2hex($recomputedRoot),
            'root_matches' => hash_equals($anchor->merkle_root, $recomputedRoot),
            'btc_block_height' => $anchor->btc_block_height,
            'ots_proof_available' => $anchor->ots_proof_complete !== null,
            'ots_proof_url' => url("/api/integrity/proof/{$date}.ots"),
        ]);
    }

    public function downloadProof(string $date)
    {
        $anchor = DB::table('ledger_anchor')->where('tanggal', $date)->first();
        abort_unless($anchor && $anchor->ots_proof_complete, 404);

        return response($anchor->ots_proof_complete, 200, [
            'Content-Type' => 'application/vnd.opentimestamps.ots',
            'Content-Disposition' => "attachment; filename=\"pandora-{$date}.ots\"",
        ]);
    }
}
```

#### 12. Tambah di `routes/api.php`

```php
use App\Http\Controllers\IntegrityController;

Route::prefix('integrity')->middleware('throttle:60,1')->group(function () {
    Route::get('/verify/{id}', [IntegrityController::class, 'verifyRecord']);
    Route::get('/proof/{date}.ots', [IntegrityController::class, 'downloadProof']);
});
```

### C. KONTAINER BARU: pandora-anchor (Python)

Buat folder `~/pandora/anchor-service/`:

#### 13. `anchor-service/Dockerfile`

```dockerfile
FROM python:3.12-slim

WORKDIR /app

RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    && rm -rf /var/lib/apt/lists/*

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY anchor_service.py .

EXPOSE 8700

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -fsS http://localhost:8700/health || exit 1

CMD ["uvicorn", "anchor_service:app", "--host", "0.0.0.0", "--port", "8700"]
```

#### 14. `anchor-service/requirements.txt`

```
fastapi==0.115.0
uvicorn[standard]==0.32.0
opentimestamps-client==0.7.2
pydantic==2.9.2
```

#### 15. `anchor-service/anchor_service.py`

```python
import base64
import subprocess
import tempfile
import os
from pathlib import Path
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

app = FastAPI(title="PANDORA Anchor Service")


class AnchorRequest(BaseModel):
    date: str
    merkle_root_hex: str


class UpgradeRequest(BaseModel):
    ots_proof_base64: str


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/anchor")
def anchor(req: AnchorRequest):
    """Submit Merkle root ke calendar OpenTimestamps."""
    try:
        root_bytes = bytes.fromhex(req.merkle_root_hex)
    except ValueError:
        raise HTTPException(400, "invalid hex")

    if len(root_bytes) != 32:
        raise HTTPException(400, "merkle root must be 32 bytes")

    with tempfile.TemporaryDirectory() as td:
        src = Path(td) / f"pandora-{req.date}.bin"
        src.write_bytes(root_bytes)

        # Submit ke calendar publik (default: alice + bob + finney)
        result = subprocess.run(
            ["ots", "stamp", str(src)],
            capture_output=True, text=True, timeout=60
        )
        if result.returncode != 0:
            raise HTTPException(500, f"ots stamp failed: {result.stderr}")

        ots_file = Path(str(src) + ".ots")
        if not ots_file.exists():
            raise HTTPException(500, "ots file not produced")

        proof_bytes = ots_file.read_bytes()
        return {
            "date": req.date,
            "merkle_root_hex": req.merkle_root_hex,
            "ots_proof_base64": base64.b64encode(proof_bytes).decode(),
            "calendars": ["alice.btc.calendar.opentimestamps.org",
                          "bob.btc.calendar.opentimestamps.org",
                          "finney.calendar.eternitywall.com"],
        }


@app.post("/upgrade")
def upgrade(req: UpgradeRequest):
    """Upgrade bukti incomplete ke bukti final (setelah Bitcoin konfirmasi)."""
    try:
        proof_bytes = base64.b64decode(req.ots_proof_base64)
    except Exception:
        raise HTTPException(400, "invalid base64")

    with tempfile.TemporaryDirectory() as td:
        ots = Path(td) / "proof.ots"
        ots.write_bytes(proof_bytes)
        # File dummy agar `ots upgrade` punya referensi (tidak dibaca isinya oleh OTS)
        dummy = Path(td) / "proof"
        dummy.write_bytes(b"")

        result = subprocess.run(
            ["ots", "upgrade", str(ots)],
            capture_output=True, text=True, timeout=60
        )

        upgraded = ots.read_bytes()
        is_complete = b"pending" not in result.stdout.lower() and b"pending" not in result.stderr.lower()

        return {
            "upgraded": is_complete,
            "ots_proof_base64": base64.b64encode(upgraded).decode(),
            "stdout": result.stdout[-500:],
            "stderr": result.stderr[-500:],
        }
```

### D. DOCKER COMPOSE

#### 16. Update `~/pandora/docker-compose.yml`

Tambahkan service baru (JANGAN ganti service yang sudah ada):

```yaml
  pandora-anchor:
    build:
      context: ./anchor-service
      dockerfile: Dockerfile
    container_name: pandora-anchor
    restart: unless-stopped
    networks:
      - pandora-net
    expose:
      - "8700"
    environment:
      - TZ=Asia/Makassar
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost:8700/health"]
      interval: 30s
      timeout: 5s
      retries: 3
    logging:
      driver: json-file
      options:
        max-size: "10m"
        max-file: "3"
```

Pastikan service `pandora-app` (Laravel) bisa reach `pandora-anchor:8700` — keduanya di network `pandora-net` yang sama.

### E. ENVIRONMENT

#### 17. Update `.env` di Laravel

```
ANCHOR_SERVICE_URL=http://pandora-anchor:8700
LEDGER_ADVISORY_LOCK_KEY=7842911
```

### F. PIPELINE ML — ATURAN BACA SAJA

#### 18. Update dokumentasi/konfigurasi kontainer `pandora-ml`

Pastikan user PostgreSQL yang dipakai Python FastAPI hanya punya hak **SELECT** pada `log_presensi`:

```sql
-- Jalankan manual via psql sebagai superuser
REVOKE INSERT, UPDATE, DELETE ON log_presensi FROM pandora_ml;
GRANT SELECT ON log_presensi TO pandora_ml;
GRANT SELECT, INSERT ON anomaly_flags TO pandora_ml;
GRANT SELECT, INSERT, UPDATE ON features_kehadiran TO pandora_ml;
```

Ini menjadikan aturan "append-only" ditegakkan oleh database, bukan hanya oleh kode.

### G. TESTING

#### 19. `tests/Feature/HashChainTest.php`

```php
<?php

use App\Models\LogPresensi;
use App\Services\HashChainService;

it('appends with valid hash chain', function () {
    $svc = app(HashChainService::class);

    $r1 = $svc->appendHashed([
        'nip' => '199001012020011001',
        'waktu' => now(),
        'latitude' => -2.0,
        'longitude' => 117.5,
        'status' => 'hadir',
        'device_id' => 'dev-01',
        'source' => 'test',
    ]);

    $r2 = $svc->appendHashed([
        'nip' => '199001012020011002',
        'waktu' => now(),
        'latitude' => -2.0,
        'longitude' => 117.5,
        'status' => 'hadir',
        'device_id' => 'dev-02',
        'source' => 'test',
    ]);

    expect($r2->hash_prev)->toEqual($r1->hash_current);
    expect(app(HashChainService::class)->verifyChain()['verified'])->toBeTrue();
});

it('rejects updates to log_presensi', function () {
    $svc = app(HashChainService::class);
    $row = $svc->appendHashed([
        'nip' => '199001012020011003',
        'waktu' => now(),
        'latitude' => -2.0,
        'longitude' => 117.5,
        'status' => 'hadir',
        'device_id' => 'dev-03',
        'source' => 'test',
    ]);

    expect(fn() => $row->update(['status' => 'izin']))
        ->toThrow(RuntimeException::class);
});

it('detects broken chain when hash modified', function () {
    $svc = app(HashChainService::class);
    $svc->appendHashed([
        'nip' => '199001012020011004',
        'waktu' => now(),
        'latitude' => -2.0,
        'longitude' => 117.5,
        'status' => 'hadir',
        'device_id' => 'dev-04',
        'source' => 'test',
    ]);

    // Simulasi manipulasi via raw SQL (bypass model)
    DB::statement("UPDATE log_presensi SET status = 'izin' WHERE id = 1");

    expect($svc->verifyChain()['verified'])->toBeFalse();
});
```

## LANGKAH EKSEKUSI (JALANKAN BERURUTAN)

```bash
cd ~/pandora/src

# 1. Buat migrations dan jalankan
php artisan make:migration add_hash_chain_to_log_presensi
php artisan make:migration create_ledger_anchor_table
php artisan make:migration create_anomaly_flags_table
# ... edit file migration sesuai spec di atas
php artisan migrate --force

# 2. Buat service, model, command
php artisan make:command LedgerAnchorDaily
php artisan make:command LedgerAnchorUpgrade
php artisan make:command LedgerVerify
php artisan make:controller IntegrityController
# ... isi dengan kode di atas

# 3. Build kontainer anchor
cd ~/pandora
mkdir -p anchor-service
# ... buat Dockerfile, requirements.txt, anchor_service.py

# 4. Update docker-compose.yml
# ... tambahkan service pandora-anchor

# 5. Build & up
docker compose build pandora-anchor
docker compose up -d pandora-anchor

# 6. Verifikasi anchor service
curl http://localhost:8700/health
# expect: {"status":"ok"}

# 7. Revoke write permission Python ML ke log_presensi
docker compose exec db psql -U postgres -d pandora -c "
REVOKE INSERT, UPDATE, DELETE ON log_presensi FROM pandora_ml;
GRANT SELECT ON log_presensi TO pandora_ml;
GRANT SELECT, INSERT ON anomaly_flags TO pandora_ml;
"

# 8. Test hash chain
cd ~/pandora/src
php artisan test --filter=HashChainTest

# 9. Trigger anchor manual untuk hari ini
php artisan ledger:anchor-daily

# 10. Cek hasil
docker compose exec db psql -U postgres -d pandora -c "SELECT tanggal, jumlah_record, status FROM ledger_anchor ORDER BY tanggal DESC LIMIT 5;"

# 11. Besok pagi jalankan upgrade untuk konfirmasi Bitcoin
php artisan ledger:anchor-upgrade

# 12. Verifikasi integritas seluruh chain
php artisan ledger:verify

# 13. Test endpoint verifikasi publik
curl https://pandora.kaltaraprov.go.id/api/integrity/verify/1 | jq

# 14. Commit
git add -A && git commit -m "feat(integrity): hash-chain log presensi + OpenTimestamps anchoring (Tingkat 2)"
```

## CATATAN PENTING

- **Jangan hapus atau ubah data log_presensi yang sudah ada.** Migration hanya menambah kolom nullable. Untuk baris lama tanpa hash, jalankan backfill terpisah via command khusus (opsional, di luar scope ini).
- **Advisory lock wajib** untuk mencegah race condition pada bulk insert dari sync SIMPEG.
- **Canonical serialization** harus deterministik (urutan key JSON sama) supaya hash reproducible saat verifikasi.
- **Jangan pakai auto-timestamps Laravel untuk kolom yang masuk hash** — pakai field eksplisit seperti `waktu` agar nilainya stabil.
- **Network internet keluar** ke `*.calendar.opentimestamps.org` (port 80/443) harus diizinkan di firewall kontainer `pandora-anchor`.
- **Backup `.ots` files** — walaupun kecil, ini bukti forensik yang tidak bisa diregenerasi. Ikutkan di backup rutin PostgreSQL.
- **Key rotation / audit**: tambahkan log entry setiap kali anchor berhasil/gagal untuk monitoring via Prometheus/Grafana (future work).
- **Saat pertama kali jalan di production, backfill hash untuk data historis.** Buat command `ledger:backfill` yang iterate ordered by created_at, hitung hash chain sekuensial. Jalankan sekali saat maintenance window.

---

Setelah semua selesai, total kontainer baru: **1 (pandora-anchor)**. Total tabel baru: **2 (ledger_anchor, anomaly_flags)**. Perubahan skema log_presensi: **6 kolom tambahan, semua nullable-safe**.
