# PANDORA — Log Perbaikan & Progres

> Terakhir diperbarui: 19 April 2026

---

## 1. Fix Error Dashboard — Type Mismatch `smallint = boolean`

**Tanggal:** 18 April 2026  
**Status:** ✅ Selesai

**Masalah:**  
Kolom status kehadiran (`tw`, `mkttw`, `tk`, dll) di tabel `sync_present_rekap` sudah diubah ke `smallint` via migrasi `2026_04_18_000006_change_rekap_status_to_smallint.php`, tetapi query di controller masih menggunakan perbandingan `= true` (boolean). PostgreSQL tidak melakukan implicit cast antara `smallint` dan `boolean`.

**Error log:**
```
SQLSTATE[42883]: Undefined function: 7 ERROR: operator does not exist: smallint = boolean
```

**File yang diubah:**
- `src/app/Http/Controllers/DashboardController.php` — 6 tempat `= true` → `= 1`
- `src/app/Http/Controllers/AnalitikController.php` — 3 tempat `= true` → `= 1`
- `src/app/Http/Controllers/KehadiranController.php` — 9 tempat `= true` → `= 1`

---

## 2. Fix Sync `ref_unit` — Column Mapping Salah & Data Tidak Difilter

**Tanggal:** 18–19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
1. Column mapping salah — source SIKARA menggunakan nama kolom `unit`, `id_par_unit`, `level_unit`, tetapi mapping di `SimpegSyncService` menggunakan `nama_unit`, `parent_id`, `level`. Akibatnya semua data ter-sync sebagai NULL.
2. Tidak ada filter — 1.966 unit (termasuk unit non-aktif, duplikat, dan "Belum Ada Unit Organisasi") ter-sync, padahal yang valid hanya perangkat daerah aktif.

**Referensi:** File `Master-Data-Instansi-20260416-135411.pdf` dari Sistem E-Layanan DKISP (139 instansi valid: Induk Perangkat Daerah, Cabang Perangkat Daerah, Sekolah).

**Perbaikan:**
- Fix kolom_map: `unit` → `nama_unit`, `id_par_unit` → `parent_id`, `level_unit` → `level`
- Tambah filter `WHERE aktif = 1`
- Migrasi baru: `2026_04_19_000001_make_sync_ref_unit_nama_nullable.php`

**Hasil:** 148 instansi aktif ter-sync (termasuk beberapa yang baru ditambahkan setelah PDF digenerate).

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — fix `kolom_map` dan tambah `where`
- `src/database/migrations/2026_04_19_000001_make_sync_ref_unit_nama_nullable.php` — kolom `nama_unit` dijadikan nullable

---

## 3. Fix Sync `peg_pegawai` — Hanya Pegawai Aktif

**Tanggal:** 18–19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
- Sync mengambil semua 7.420 pegawai tanpa filter status (termasuk pegawai meninggal dunia, pensiun, mutasi keluar, pemberhentian).
- Contoh: Waluya Sejati (id_pegawai: 2960) adalah pegawai meninggal dunia tapi masih ter-sync.
- Kolom `id_unit` tidak ada di tabel sumber `peg_pegawai` — harusnya diambil dari `peg_jabatan`.
- Jumlah pegawai aktif seharusnya: **6.475**.

**Perbaikan:**
- Join `peg_jabatan` (jabatan aktif, `status = 1`) dengan `ref_status_pegawai` (status kepegawaian aktif, `tipe = 1`) dan `ref_unit` (unit aktif, `aktif = 1`)
- `id_unit` dan `status` diambil dari `peg_jabatan`, bukan dari `peg_pegawai`
- Tambah fitur `joins`, `where`, dan `kolom_map_join` di sync engine

**Hasil:** 6.475 pegawai aktif (PNS, CPNS, PPPK, PPPK Paruh Waktu, Pegawai Dipekerjakan). Waluya Sejati dan pegawai non-aktif lainnya tidak lagi ter-sync.

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — ubah konfigurasi sync `peg_pegawai` dan tambah dukungan join

---

## 4. Fix Column Mapping Seluruh Tabel Staging

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
Banyak tabel sync memiliki column mapping yang tidak sesuai dengan nama kolom di database sumber SIKARA (MySQL). Ini menyebabkan data NULL atau error saat sync.

**Tabel yang diperbaiki:**

| Tabel Sumber | Kolom Salah (mapping lama) | Kolom Benar (sumber SIKARA) |
|---|---|---|
| `ref_unit` | `nama_unit`, `parent_id`, `level` | `unit`, `id_par_unit`, `level_unit` |
| `ref_unit_ext` | `nama_unit_ext`, `alamat`, `telepon` | _(tidak ada — hanya `id_unit_ext`, `id_unit`)_ |
| `present_sikara_log` | `nip`, `cdate` | _(nip tidak ada)_, `created_at` |
| `present_device` | `imei`, `nama_device`, `model`, `cdate` | `imei_code`, _(tidak ada)_, _(tidak ada)_, `created_at` |
| `ref_bantu_unit` | `id_lokasi` | `id_ref_lokasi_unit` |
| `present_ijin` | `tanggal_mulai`, `tanggal_selesai`, `jenis_ijin`, `keterangan` | `mulai`, `berakhir`, `tipe_ijin`, `ket` |
| `present_maps_logs` | `latitude`, `longitude`, `tanggal`, `cdate` | `lat`, `lang`, `tgl`, _(tidak ada)_ |
| `fake_gps` | `nama_aplikasi` | _(tidak ada)_ |

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — seluruh `kolom_map` disesuaikan

---

## 5. Sanitasi Tanggal MySQL → PostgreSQL

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
Database sumber SIKARA menggunakan MySQL yang mengizinkan tanggal invalid seperti `0000-00-00` dan `2020-10-00`. PostgreSQL menolak tanggal-tanggal ini dengan error `Datetime field overflow`.

**Perbaikan:**
Menambahkan method `sanitizeValue()` di `SimpegSyncService` yang:
- Mendeteksi string yang berbentuk tanggal
- Mengkonversi tanggal dengan bulan/hari `00` menjadi `null`
- Memvalidasi tanggal via `strtotime()`

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — tambah method `sanitizeValue()`

---

## 6. Peningkatan Sync Engine

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Perubahan arsitektur pada `SimpegSyncService`:**

`buildSyncConfigs()` sekarang mendukung konfigurasi tambahan per tabel:

| Config Key | Tipe | Fungsi |
|---|---|---|
| `where` | `array` | Filter WHERE tambahan, format: `[['kolom', 'operator', 'value']]` |
| `joins` | `array` | JOIN antar tabel sumber, format: `[['table' => ..., 'first' => ..., 'second' => ...]]` |
| `kolom_map_join` | `array` | Mapping kolom dari tabel yang di-join ke kolom tujuan |

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — method `pullDelta()` dan `mapRow()` dimodifikasi

---

## 7. Hasil Sinkronisasi

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai (present_rekap masih berjalan — upsert data baru)

| Tabel | Rows | Status |
|---|---|---|
| `sync_peg_pegawai` | 6.475 | ✅ |
| `sync_ref_unit` | 148 | ✅ |
| `sync_ref_unit_ext` | 1 | ✅ |
| `sync_ref_lokasi_unit` | 182 | ✅ |
| `sync_ref_bantu_unit` | 549 | ✅ |
| `sync_present_rekap` | 3.372.287+ | 🔄 (upsert berjalan) |
| `sync_present_sikara_log` | 1.145.731 | ✅ |
| `sync_present_presensi` | 82.912 | ✅ |
| `sync_present_group` | 76 | ✅ |
| `sync_present_masuk` | 16.214 | ✅ |
| `sync_present_aturan` | 16 | ✅ |
| `sync_present_libur` | 203 | ✅ |
| `sync_present_ijin` | 501.514 | ✅ |
| `sync_present_device` | 1 | ✅ |
| `sync_present_maps_logs` | 131 | ✅ |
| `sync_fake_gps` | 3 | ✅ |

---

## 8. Delta Sync — Percepatan Sinkronisasi Tabel Besar

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
`present_rekap` (1.4M+ rows) membutuhkan 3+ jam untuk sync karena selalu menarik semua data dari awal. Database sumber tidak memiliki kolom `updated_at`.

**Perbaikan:**
Menambahkan konfigurasi `sync_window_days` di `SimpegSyncService`. Jika ada sync sebelumnya yang berhasil, hanya data dalam window N hari terakhir yang ditarik (bukan seluruh tabel).

- `present_rekap`: `sync_window_days = 45` (bulan ini + bulan lalu untuk update retroaktif)
- `present_maps_logs`: `sync_window_days = 45`
- `present_sikara_log`: sudah menggunakan delta via `kolom_delta = 'created_at'`

**Estimasi percepatan:** Dari ~3 jam menjadi ~5–10 menit per sync berikutnya.

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — tambah logika `sync_window_days` di `pullDelta()`

---

## 9. Deteksi Manipulasi Data Sumber

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Masalah:**
Jika data di SIMPEG dimanipulasi (misalnya mengubah status kehadiran historis), PANDORA langsung tertimpa tanpa jejak. `source_checksum` sudah dihitung tapi tidak pernah dibandingkan.

**Perbaikan:**
Menambahkan mekanisme deteksi perubahan data historis di `SimpegSyncService`:
- Sebelum upsert, query checksum existing untuk batch yang akan di-insert
- Setelah upsert, bandingkan checksum lama vs baru
- Jika data berusia > 30 hari berubah: log sebagai `warning`
- Jika data berusia > 60 hari berubah: log sebagai `critical`
- Perubahan dicatat di tabel `sync_data_changes` (tabel_sumber, pk_value, tanggal, old_checksum, new_checksum, severity)

**File yang dibuat:**
- `src/database/migrations/2026_04_19_000002_create_sync_data_changes_table.php`
- `src/app/Models/Staging/SyncDataChange.php`

**File yang diubah:**
- `src/app/Services/SimpegSyncService.php` — method `batchUpsert()` dan `detectDataChanges()`

---

## 10. Integrity Layer — Hash Chain + OpenTimestamps + Bitcoin Anchoring

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Arsitektur:**
```
sync_present_rekap ──→ log_presensi_pandora (append-only + hash chain)
                              │
                              ▼
                       Merkle Root harian
                              │
                              ▼
                    pandora-anchor container
                    (Python + opentimestamps-client)
                              │
                              ▼
                     OTS Calendar Servers
                     (alice, bob, finney)
                              │
                              ▼
                     Bitcoin Blockchain
                              │
                              ▼
                     ledger_anchor table
                     (.ots proof tersimpan)
```

**Komponen yang dibuat:**

### Services
| File | Fungsi |
|---|---|
| `src/app/Services/HashChainService.php` | Hash chain SHA-256 dengan advisory lock PostgreSQL. Method: `appendBatch()`, `verifyChain()` |
| `src/app/Services/MerkleTreeService.php` | Perhitungan Merkle root dari hash harian |

### Commands (Artisan)
| Command | Schedule | Fungsi |
|---|---|---|
| `ledger:anchor-daily` | Setiap hari 23:55 | Hitung Merkle root harian, kirim ke anchor service |
| `ledger:anchor-upgrade` | Setiap hari 06:00 | Upgrade proof OTS setelah Bitcoin konfirmasi (~12–24 jam) |
| `ledger:verify` | Setiap Minggu 02:00 | Self-check integritas seluruh hash chain |

### Container Baru
| Container | Stack | Port | Fungsi |
|---|---|---|---|
| `pandora-anchor` | Python 3.12 + FastAPI + opentimestamps-client | 8700 (internal) | Endpoint `/anchor`, `/upgrade`, `/verify` untuk operasi OTS |

### Routes (Web)
| Route | Fungsi |
|---|---|
| `GET /integritas` | Daftar anchor harian dengan status (pending/anchored/confirmed) |
| `GET /integritas/download/{date}.ots` | Download file `.ots` untuk verifikasi manual di opentimestamps.org |
| `GET /integritas/verify/{date}` | API verifikasi integritas tanggal tertentu (chain + Merkle + BTC status) |

### Alur Kerja
1. Data presensi di-sync dari SIKARA → `sync_present_rekap`
2. Data disalin ke `log_presensi_pandora` (append-only, hash chain)
3. Setiap malam (23:55), `ledger:anchor-daily` menghitung Merkle root dan mengirim ke `pandora-anchor`
4. `pandora-anchor` men-stamp ke OTS calendar servers, menyimpan proof incomplete
5. Keesokan pagi (06:00), `ledger:anchor-upgrade` meng-upgrade proof setelah Bitcoin mengkonfirmasi block
6. File `.ots` bisa didownload dari dashboard dan diverifikasi di https://opentimestamps.org
7. Setiap Minggu, `ledger:verify` menjalankan self-check hash chain

### Verifikasi Manual oleh Auditor
1. Download file `.ots` dari halaman `/integritas`
2. Buka https://opentimestamps.org
3. Upload file `.ots`
4. Website menampilkan konfirmasi bahwa hash ter-anchor di Bitcoin block tertentu
5. Bandingkan Merkle root yang ditampilkan dengan yang ada di PANDORA

**File yang dibuat:**
- `src/app/Services/HashChainService.php`
- `src/app/Services/MerkleTreeService.php`
- `src/app/Console/Commands/LedgerAnchorDaily.php`
- `src/app/Console/Commands/LedgerAnchorUpgrade.php`
- `src/app/Console/Commands/LedgerVerify.php`
- `src/app/Http/Controllers/IntegrityController.php`
- `anchor-service/Dockerfile`
- `anchor-service/requirements.txt`
- `anchor-service/anchor_service.py`

**File yang diubah:**
- `docker-compose.yml` — tambah service `pandora-anchor`
- `src/routes/web.php` — tambah routes `/integritas`
- `src/routes/console.php` — aktifkan schedule ledger commands

---

## 11. Geofence Zones & Rules — Populated dari SIKARA

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Perbaikan:**
- `geofence_zones`: 182 zona dari `sync_ref_lokasi_unit`, termasuk PostGIS polygon (circular buffer dari lat/long + radius)
- `geofence_rules`: 895 aturan jam kerja (5 hari × 179 zona aktif) berdasarkan jadwal aktif grup 96 (Pasca Ramadhan 2026: Sen-Kam 07:30-16:00, Jum 07:30-16:30)

---

## 12. Log Presensi & Hash Chain — Wiring ke Sync

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Perbaikan:**
- Command baru `ledger:sync-presensi` — menyalin data `sync_present_rekap` ke `log_presensi_pandora` dengan hash chain SHA-256
- Column `metadata` (jsonb) ditambahkan ke `log_presensi_pandora` untuk tracking `id_rekap` (cegah duplikat)
- Column type `hash_current`, `hash_prev` diubah dari `bytea` ke `varchar(64)` — fix encoding mismatch
- Column type `merkle_root` di `ledger_anchor` juga diubah ke `varchar(64)`
- Normalisasi datetime di `canonicalPayload()` agar hash konsisten antara insert dan verify
- Schedule: `ledger:sync-presensi --days=2` setiap hari jam 23:00

**Hasil:** 19.250 rows di `log_presensi_pandora`, hash chain **VALID**

**File yang dibuat:**
- `src/app/Console/Commands/LogPresensiSync.php`

**File yang diubah:**
- `src/app/Services/HashChainService.php` — normalisasi `canonicalPayload()`
- `src/app/Console/Commands/LedgerAnchorDaily.php` — fix column `jumlah_record`, tambah `sequence_start/end`
- `src/routes/console.php` — tambah schedule `ledger:sync-presensi`

---

## 13. Pipeline Analitik — Feature Engineering + Deteksi Anomali

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai (4 hari data)

**Hasil eksekusi pipeline harian (14-17 April 2026):**

| Tanggal | Features | Anomali T1 (Rule) | Anomali T3 (ML) |
|---|---|---|---|
| 14 Apr | 5.015 | 122 | 251 IF + 0 DBSCAN |
| 15 Apr | 4.851 | 119 | 243 IF + 116 DBSCAN |
| 16 Apr | 4.765 | 141 | 239 IF + 167 DBSCAN |
| 17 Apr | 5.000 | 100 | 250 IF + 635 DBSCAN |

**Total:** 19.631 features, 2.383 anomali (482 fake GPS Tingkat 1, 1.901 statistical Tingkat 3)

---

## 14. Bitcoin Anchoring — 4 Hari Ter-anchor

**Tanggal:** 19 April 2026  
**Status:** ✅ Anchored (menunggu konfirmasi Bitcoin ~12-24 jam)

| Tanggal | Records | Merkle Root | Status |
|---|---|---|---|
| 14 Apr | 4.890 | `4f9f6353...` | anchored |
| 15 Apr | 4.741 | `bc0e2b29...` | anchored |
| 16 Apr | 4.675 | `c3c2f6b5...` | anchored |
| 17 Apr | 4.944 | `d107c667...` | anchored |

File `.ots` tersedia untuk download di `/integritas`

---

## 15. Literasi Data — Modul Pembelajaran

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

- 57 materi di 7 kategori, semua dengan studi kasus PANDORA
- Menu sidebar + 3 halaman (index, category, show)
- KaTeX untuk rumus, Prism.js untuk syntax highlighting
- Scroll progress bar, animasi entrance, navigasi prev/next

**File yang dibuat:**
- `src/app/Http/Controllers/LiterasiDataController.php`
- `src/resources/views/literasi-data/index.blade.php`
- `src/resources/views/literasi-data/category.blade.php`
- `src/resources/views/literasi-data/show.blade.php`
- 57 file markdown di `literasi-data/`

---

## 16. Anomaly Review Workflow

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Fitur:**
- Tombol "Review" di setiap anomali yang belum direview di halaman `/analitik/anomali`
- Dropdown Alpine.js dengan textarea catatan + dua tombol: "Valid" (konfirmasi anomali) dan "False Positive"
- PATCH `/analitik/anomali/{id}/review` — update status_review, direview_oleh, direview_pada, catatan_review
- Otomatis dicatat ke audit trail

**File yang diubah:**
- `src/app/Http/Controllers/AnalitikController.php` — tambah `reviewAnomali()`
- `src/resources/views/analitik/anomali.blade.php` — tambah kolom Aksi + review form
- `src/routes/web.php` — tambah PATCH route

---

## 17. Sinkronisasi Monitoring

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Fitur halaman `/sinkronisasi`:**
- 4 stat cards: total tabel, sync terakhir, data change alerts, critical alerts
- Tabel status sync per tabel: status (badge), rows fetched/inserted/updated, waktu, durasi
- Tabel data change alerts (deteksi manipulasi) dengan severity badges
- Riwayat sync 50 terakhir (collapsible)
- Audit trail otomatis

**File yang diubah:**
- `src/app/Http/Controllers/SinkronisasiController.php` — implementasi penuh
- `src/resources/views/sinkronisasi/index.blade.php` — redesign dari placeholder

---

## 18. Audit Trail — Middleware Otomatis

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Fitur:**
- Middleware `LogAuditTrail` mencatat semua akses halaman authenticated secara otomatis
- Mapping URL path ke nama aksi (lihat_dashboard, lihat_master_data, lihat_kehadiran, dll)
- Terdaftar di web middleware group — tidak perlu tambah manual per controller
- Audit review anomali juga tercatat via `AuditTrail::catat()`

**File yang dibuat:**
- `src/app/Http/Middleware/LogAuditTrail.php`

**File yang diubah:**
- `src/bootstrap/app.php` — register middleware

---

## 19. User Management

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

**Fitur halaman `/pengaturan/users` (admin only):**
- Tabel daftar user: Nama, NIP, Email, Role (badge warna), Created At, Aksi
- Tambah user baru via modal Alpine.js
- Edit user inline
- Hapus user dengan konfirmasi (tidak bisa hapus diri sendiri)
- 3 role: admin (merah), hr (biru), pimpinan (emas)
- Semua mutasi tercatat di audit trail

**Halaman `/pengaturan` (settings dashboard):**
- Stats: total user, total audit entries, waktu audit terakhir
- Link ke manajemen user dan integritas

**File yang dibuat:**
- `src/app/Http/Controllers/UserController.php`
- `src/resources/views/pengaturan/users.blade.php`

**File yang diubah:**
- `src/app/Http/Controllers/PengaturanController.php` — dari stub ke implementasi
- `src/resources/views/pengaturan/index.blade.php` — dari placeholder ke dashboard
- `src/routes/web.php` — tambah routes user CRUD

---

## 20. Fase 6: UAT & Polishing

**Tanggal:** 19 April 2026  
**Status:** ✅ Selesai

### End-to-End Test Results
- **14/14 halaman** render tanpa error
- **7/7 container** running dan healthy
- **7/7 artisan commands** terdaftar
- **7/7 scheduled jobs** aktif dengan cron yang benar
- **4/4 service classes** bisa di-instantiate
- **Hash chain VALID** — 19.250 records tanpa kerusakan
- **Nginx** syntax ok
- **Vite build** manifest ada
- **Anchor service** health ok
- **Analytics service** health ok

### Polish yang Dilakukan
- Flash messages global (success + error) di layout — otomatis tampil di semua halaman dengan auto-dismiss 5 detik
- Landing page stats dikoreksi: 6.000+ → 6.475 ASN, 139 → 148 Instansi
- View cache tested — semua blade templates compile tanpa error

### Status Akhir Proyek

| Komponen | Jumlah |
|---|---|
| Pegawai aktif | 6.475 |
| Instansi/OPD | 148 |
| Rekap kehadiran | 5.188.787 |
| Anomali terdeteksi | 2.383 |
| Features harian | 19.631 |
| Hash chain records | 19.250 |
| Bitcoin anchors | 4 hari |
| Geofence zones | 182 |
| Geofence rules | 895 |
| Audit trail entries | 20+ |
| Users | 3 |
| Containers | 7 (semua healthy) |
| Scheduled jobs | 7 |
| Halaman UI | 14+ |
| Materi literasi data | 57 |
