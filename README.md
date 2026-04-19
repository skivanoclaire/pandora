# PANDORA

**Portal Analitik Data Kehadiran ASN** — Pemerintah Provinsi Kalimantan Utara

PANDORA adalah portal analitik yang menarik data kehadiran dari sistem SIKARA (Badan Kepegawaian Daerah), mendeteksi anomali secara otomatis menggunakan machine learning, dan menyajikan insight kepada pimpinan daerah melalui dashboard interaktif.

> **PANDORA bukan sistem absensi** — fungsi absensi tetap di SIKARA. PANDORA adalah lapisan analitik di atasnya.

## Arsitektur

```
Client → Nginx (:443 SSL)
           ├── /              → Laravel 13 (PHP-FPM)
           └── /api/analytics → FastAPI (Python)

Laravel ──→ PostgreSQL 16 + PostGIS
       ──→ Redis 7
       ──→ FastAPI (internal)
       ──→ pandora-anchor (OpenTimestamps)

SIKARA (MySQL) ──→ [sync tiap 15 menit] ──→ PostgreSQL (staging)
```

## Stack

| Layer | Teknologi |
|---|---|
| Web Portal | Laravel 13 (PHP 8.4) |
| Analytics/ML | Python 3.11, FastAPI, scikit-learn |
| Database | PostgreSQL 16 + PostGIS 3.4 |
| Cache & Queue | Redis 7 |
| Integrity | OpenTimestamps + Bitcoin anchoring |
| Reverse Proxy | Nginx + Let's Encrypt SSL |
| Deployment | Docker Compose (8 containers) |

## Containers

| Container | Fungsi |
|---|---|
| `pandora-app` | Laravel PHP-FPM |
| `pandora-nginx` | Reverse proxy + SSL |
| `pandora-db` | PostgreSQL + PostGIS |
| `pandora-redis` | Cache & queue |
| `pandora-analytics` | FastAPI ML pipeline |
| `pandora-queue` | Laravel queue worker |
| `pandora-scheduler` | Laravel schedule daemon |
| `pandora-anchor` | OpenTimestamps service |

## Fitur Utama

### Sinkronisasi Data
- Sync 16 tabel dari SIKARA (MySQL) ke PostgreSQL setiap 15 menit
- Delta sync untuk tabel besar (present_rekap 5M+ rows)
- Deteksi manipulasi data historis via checksum comparison
- Sanitasi tanggal invalid MySQL → PostgreSQL

### Deteksi Anomali
- **Tingkat 1 (Fisik):** Koordinat GPS identik berhari-hari (fake GPS), perpindahan mustahil
- **Tingkat 2 (Rule):** Pelanggaran aturan formal setelah mempertimbangkan status
- **Tingkat 3 (ML):** Isolation Forest (outlier multivariate) + DBSCAN (clustering spasial)
- Reverse geocoding (Nominatim) — deteksi absensi dari luar Kalimantan Utara
- Detail anomali per pegawai dengan narasi auto-generated

### Dashboard
- Stat cards: kehadiran, terlambat, tidak hadir (dihitung dari jam_masuk vs jadwal)
- Tren kehadiran (exclude weekend + hari libur nasional)
- Top 10 OPD terbaik & terburuk
- Peta anomali (Leaflet)
- Rincian per tanggal: siapa terlambat, DL/DD, cuti, sakit, tanpa keterangan
- Integrasi data ijin SIKARA (present_ijin)
- Banner otomatis saat weekend/hari libur

### Integritas Data
- Hash chain SHA-256 pada event log presensi (append-only)
- Merkle root harian di-anchor ke Bitcoin via OpenTimestamps
- File `.ots` downloadable untuk verifikasi independen di opentimestamps.org
- Verifikasi chain otomatis mingguan

### Admin
- Review anomali (valid / false positive)
- Monitoring sinkronisasi + data change alerts
- Manajemen user (admin/hr/pimpinan)
- Audit trail otomatis (middleware)
- CRUD geofence zones & rules

### Literasi Data
- 57 materi di 7 kategori (fondasi, data engineering, klasifikasi, regresi, clustering, association rule, NLP/CV)
- Studi kasus menggunakan data PANDORA
- KaTeX untuk rumus, Prism.js untuk syntax highlighting

## Data

| Tabel | Jumlah |
|---|---|
| Pegawai aktif | 6.475 |
| Instansi/OPD | 148 |
| Rekap kehadiran | 5.188.787 |
| Event log presensi | 1.145.731 |
| Anomali terdeteksi | 2.383 |
| Geofence zones | 182 |
| Geofence rules | 895 |

## Quick Start

```bash
# Clone
git clone git@github.com:skivanoclaire/pandora.git
cd pandora

# Copy environment
cp src/.env.example src/.env

# Start semua containers
docker compose up -d

# Install dependencies
docker compose exec pandora-app composer install

# Migrate database
docker compose exec pandora-app php artisan migrate --force

# Build frontend
docker compose exec pandora-app npm install && npm run build

# Sync data dari SIKARA
docker compose exec pandora-app php artisan simpeg:sync

# Jalankan pipeline analitik
docker compose exec pandora-app php artisan pipeline:daily --date=$(date +%Y-%m-%d)
```

## Scheduled Tasks

| Schedule | Command | Fungsi |
|---|---|---|
| */15 * * * * | `simpeg:sync` | Sync data SIKARA |
| 02:00 daily | `pipeline:daily` | Feature engineering + rule engine |
| 23:00 daily | `ledger:sync-presensi` | Hash chain event log |
| 23:55 daily | `ledger:anchor-daily` | Bitcoin anchoring |
| 06:00 daily | `ledger:anchor-upgrade` | Upgrade OTS proof |
| 5th monthly | `pipeline:monthly` | Pipeline bulanan (Tingkat 2+3) |
| Sunday 02:00 | `ledger:verify` | Self-check hash chain |

## Backup

```bash
# Backup manual (database + source → /home/pandora/backups/)
./scripts/backup.sh

# Upload ke Google Drive (setelah rclone dikonfigurasi)
rclone copy /home/pandora/backups/pandora_backup_*.tar.gz gdrive:PANDORA-Backups/
```

## Domain

**Production:** https://pandora.kaltaraprov.go.id

## Lisensi

Proyek internal Pemerintah Provinsi Kalimantan Utara — DKISP (Dinas Komunikasi, Informatika, Statistik, dan Persandian). Dikembangkan dalam kerangka program SKALA (DFAT Australia).

---

*Dikembangkan oleh Bayu Adi Hartanto (DKISP Kaltara) dengan dukungan AI agent.*
