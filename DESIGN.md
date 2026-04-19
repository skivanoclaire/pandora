# PANDORA — Design Document

**Portal Analitik Data Kehadiran ASN**
Pemerintah Provinsi Kalimantan Utara — Dinas Komunikasi, Informatika, Statistik, dan Persandian (DKISP)

| Atribut | Nilai |
|---|---|
| Versi dokumen | 0.3 (revisi setelah identifikasi tabel jadwal SIKARA — present_group, present_presensi, present_masuk, present_aturan) |
| Tanggal | 18 April 2026 |
| Status | Draft kerja internal |
| Mitra program | SKALA — Sinergi dan Kolaborasi untuk Akselerasi Layanan Daerah (DFAT Australia) |
| Subdomain produksi | pandora.kaltaraprov.go.id |
| Target Go-Live | Kuartal IV 2026 |
| Penyusun | Bayu Adi Hartanto (DKISP) |

> **Catatan status.** Dokumen ini adalah draft awal v0.1 yang disusun berdasarkan kerangka prompt implementasi yang sudah ada (PROMPT-CLAUDE-VM.md dan PROMPT-TINGKAT-2-INTEGRITY.md), ide penelitian Isolation Forest + DBSCAN untuk deteksi anomali kehadiran, dan hasil analisis skema database SIMPEG/SIKARA. Dokumen ini harus direvisi seiring perkembangan proyek dan akan menjadi sumber kebenaran tunggal (single source of truth) untuk seluruh tim pengembangan.

---

## 1. Ringkasan Eksekutif

PANDORA adalah portal analitik data kehadiran Aparatur Sipil Negara (ASN) di lingkungan Pemerintah Provinsi Kalimantan Utara. Portal ini dibangun untuk memberikan insight tepercaya kepada pimpinan daerah mengenai kondisi kedisiplinan ASN lintas Organisasi Perangkat Daerah (OPD), sekaligus menyediakan basis bukti berbasis data untuk proses audit.

PANDORA **bukan sistem kehadiran** — fungsi absensi tetap dijalankan oleh aplikasi SIKARA milik Badan Kepegawaian Daerah (BKD). PANDORA adalah lapisan analitik di atasnya: menarik data log absensi SIKARA ke lingkungan tersendiri, mendeteksi anomali kehadiran (penggunaan Fake GPS, manipulasi waktu perangkat, ketidaksesuaian lokasi terhadap aturan), dan menyajikan hasilnya dalam bentuk dashboard yang mudah dipahami pengambil kebijakan.

Nilai utama PANDORA ada di tiga hal:

1. **Insight cepat untuk Pimpinan** — Gubernur, Wakil Gubernur, dan Sekretaris Daerah dapat melihat kondisi kedisiplinan ASN secara ringkas lewat HP atau tablet, dengan narasi naratif yang menyorot hal-hal yang perlu perhatian.
2. **Deteksi anomali otomatis** — mengidentifikasi kemungkinan kecurangan (Fake GPS, timezone spoofing, absensi di lokasi tidak sesuai aturan hari/jam) yang sulit terdeteksi manual karena volume data.
3. **Integritas data yang dapat diaudit** — catatan anomali dan log kehadiran yang dianalisis dijamin tidak dapat dimanipulasi diam-diam melalui mekanisme hash-chain internal dan anchoring ke Bitcoin (via OpenTimestamps), sehingga bukti yang disajikan dapat dipertanggungjawabkan ke auditor eksternal.

---

## 2. Konteks Proyek

### 2.1. Kebutuhan bisnis

Sistem Kehadiran ASN (SIKARA) yang berjalan di Pemprov Kaltara sudah menerapkan verifikasi biometrik (face recognition dengan deteksi liveness) dan geolokasi. Namun sistem ini belum memiliki mekanisme analitik untuk mendeteksi pola-pola anomali dalam data log absensi secara otomatis. Deteksi manual tidak efektif karena volume data yang besar dan pola kecurangan yang semakin canggih, termasuk kombinasi Fake GPS dengan manipulasi timezone perangkat.

Tanpa sistem deteksi yang andal, kecurangan kehadiran berpotensi terus terjadi dan mengikis integritas tata kelola kepegawaian, sekaligus menimbulkan kerugian keuangan negara karena tunjangan kinerja ASN dihitung berdasarkan data kehadiran.

### 2.2. Mitra program

PANDORA dikembangkan dalam kerangka kerja sama Indonesia–Australia melalui program **SKALA** (Sinergi dan Kolaborasi untuk Akselerasi Layanan Daerah), sebuah program Pemerintah Australia (DFAT) yang mendukung penguatan pemerintahan daerah di Indonesia. Keluaran PANDORA menjadi salah satu deliverable Pemprov Kaltara dalam program ini pada tahun 2026.

### 2.3. Pengguna dan peran

Portal ini **tidak dibuka untuk seluruh ASN**. Akses dibatasi pada peran-peran berikut:

| Peran | Pengguna | Kebutuhan utama |
|---|---|---|
| **Pimpinan** (prioritas 1) | Gubernur, Wakil Gubernur, Sekretaris Daerah | Insight ringkas, mudah dipahami, mobile-first, dapat diakses dalam hitungan menit di tengah kesibukan |
| **Admin DKISP** (prioritas 2) | DKISP — Tim Koordinator SPBE Daerah | Mengelola sistem, mengatur master geofence, meninjau anomali, mengelola user PANDORA |
| **Auditor** (prioritas 3) | Inspektorat Daerah, BKD sebagai pemilik proses bisnis | Melihat laporan anomali per pegawai/unit dalam rentang waktu tertentu, verifikasi integritas data |

### 2.4. Kewenangan pengelolaan data

DKISP mengelola PANDORA sebagai penyedia layanan teknis. Data kepegawaian dan log kehadiran tetap merupakan **milik BKD sebagai pemilik proses bisnis utama**. Akses PANDORA ke database SIMPEG/SIKARA didasarkan pada kewenangan Tim Koordinator SPBE Daerah yang ditetapkan via Peraturan Gubernur dan Surat Keputusan terkait. 


---

## 3. Ruang Lingkup

### 3.1. Dalam ruang lingkup (In-scope)

1. Penarikan data log kehadiran dari SIKARA ke staging layer PANDORA (one-way, read-only dari sisi SIKARA).
2. Pengelolaan master data aturan geofence berbasis hari dan jam (misal: Lapangan Agatis hanya valid Senin apel pagi dan Selasa senam sore) — fungsi ini **belum ada** di SIKARA.
3. Feature engineering harian untuk tiga kategori anomali:
   - **Velocity anomaly** — kecepatan perpindahan lokasi antar sesi absensi yang tidak wajar (indikasi Fake GPS).
   - **Geofence compliance** — ketidaksesuaian lokasi absensi dengan aturan hari/jam yang berlaku.
   - **Temporal pattern deviation** — deviasi waktu absensi terhadap pola historis pegawai/unit (sebagai pengganti/pelengkap deviasi timezone, mengingat keterbatasan data sumber).
4. Pipeline deteksi anomali hybrid **Isolation Forest + DBSCAN** dengan output ke tabel `anomaly_flags`.
5. Dashboard eksekutif untuk Pimpinan (mobile-first, narasi naratif, ringkasan per OPD, peta panas anomali).
6. Admin console untuk DKISP (CRUD geofence, review anomali, whitelist, user management internal PANDORA).
7. Modul audit untuk Inspektorat/BKD (daftar anomali per pegawai/unit/periode, ekspor laporan, verifikasi integritas).
8. Integrity Layer: hash-chain pada log internal PANDORA + anchoring Merkle root harian ke Bitcoin via OpenTimestamps.
9. Dokumentasi lengkap: manual pengguna, manual operasi, architecture document, handover package untuk BKD.

### 3.2. Di luar ruang lingkup (Out-of-scope)

1. Modifikasi terhadap aplikasi SIKARA (frontend atau backend). PANDORA hanya membaca data, tidak menulis kembali.
2. Fitur absensi pengganti. PANDORA tidak memproses check-in/check-out.
3. Integrasi dengan tunjangan kinerja. PANDORA menyediakan data, bukan menghitung pembayaran.
4. Akses publik. PANDORA hanya untuk peran yang terdefinisi.
5. Aplikasi mobile native. Seluruh antarmuka via web responsif.

### 3.3. Asumsi

1. Akses read-only ke database SIMPEG/SIKARA melalui port 3306 dari IP VM PANDORA tetap tersedia sepanjang proyek.
2. Ada personel teknis dari pihak SIKARA yang dapat dimintai bantuan untuk pembuatan user `simpeg_api` dan verifikasi skema.
3. Tim pengembangan terdiri dari satu manusia (Bayu Adi Hartanto) dengan dukungan agent AI untuk eksekusi teknis di lingkungan VM dan untuk diskusi desain di lingkungan Claude Desktop/Cowork.
4. Infrastruktur VM PANDORA (Ubuntu 22.04) dan seluruh kontainer Docker sudah tersedia dan berjalan.

---

## 4. Arsitektur Tingkat Tinggi

### 4.1. Diagram alur data (naratif)

```
[ Database SIMPEG/SIKARA (MySQL 3306) ]
                │
                │  read-only, user simpeg_api
                │  sync tiap 15 menit
                ▼
[ Staging Layer — PostgreSQL 16 + PostGIS di VM PANDORA ]
                │
                ├──► [ Laravel 13 — Portal & Admin ]
                │         │
                │         └──► [ Blade + Tailwind + Alpine.js ]
                │                     (Landing, Login, Dashboard Eksekutif,
                │                      Admin Console, Audit Module)
                │
                ├──► [ Python FastAPI — ML Pipeline (kontainer pandora-ml) ]
                │         │
                │         ├──► Feature Engineering Harian
                │         ├──► Isolation Forest (multivariate)
                │         ├──► DBSCAN (spatial clustering)
                │         └──► Rule Engine (geofence compliance)
                │                     │
                │                     ▼
                │         [ anomaly_flags ] — tabel output analitik
                │
                └──► [ Integrity Layer ]
                          │
                          ├──► Hash-chain pada log_presensi PANDORA
                          ├──► Merkle root harian
                          └──► OpenTimestamps → Bitcoin anchoring
                                        │
                                        ▼
                          [ ledger_anchor ] — bukti anchoring

                                        │
              Cache & queue: [ Redis 7 ]
              Reverse proxy + SSL: [ Nginx ]
              Orkestrasi: [ Docker Compose ]
```

### 4.2. Stack teknologi

| Lapisan | Teknologi | Catatan |
|---|---|---|
| Backend aplikasi | Laravel 13 (PHP 8.2+) | Di `~/pandora/src` pada VM |
| Database utama | PostgreSQL 16 + ekstensi PostGIS | Mendukung query spasial untuk geofence |
| Pipeline ML | Python FastAPI (kontainer `pandora-ml`) | Scikit-learn untuk Isolation Forest & DBSCAN |
| Cache & antrean | Redis 7 | Untuk queue Laravel dan caching |
| Frontend build | Vite + Tailwind CSS + Alpine.js | Mobile-first, tanpa SPA berat |
| Reverse proxy | Nginx + SSL | Terminating TLS |
| Orkestrasi | Docker Compose | Di `~/pandora/docker-compose.yml` |
| Sumber data | MySQL (SIMPEG/SIKARA) | Akses read-only via port 3306 |
| Anchoring blockchain | OpenTimestamps | Menggunakan Bitcoin mainnet sebagai notaris timestamp |

### 4.3. Prinsip desain

1. **PANDORA tidak memiliki sumber kebenaran absensi.** SIKARA tetap pemilik data. PANDORA adalah turunan.
2. **Staging layer wajib.** Tidak ada query langsung ke SIKARA dari aplikasi. Semua lewat tabel staging PostgreSQL.
3. **Output analitik di tabel terpisah.** Hasil ML dan rule engine tidak pernah menulis ke tabel yang merepresentasikan log kehadiran.
4. **Append-only untuk log internal PANDORA.** Tabel yang menjadi basis hash-chain tidak boleh di-UPDATE atau DELETE fisik.
5. **Mobile-first.** Pimpinan sebagai pengguna prioritas kemungkinan besar mengakses dari HP.
6. **Dokumentasi sebagai kode.** Semua keputusan desain signifikan diabadikan sebagai ADR (Architecture Decision Record).

---

## 5. Sumber Data & Integrasi SIKARA

### 5.1. Tabel SIKARA/SIMPEG yang relevan

Dari analisis skema `simpegkaltara21`:

| Tabel | Peran di PANDORA |
|---|---|
| `peg_pegawai` | Master pegawai. Kolom sensitif (NIK, NPWP, `face_data`, dll) **tidak** ditarik. Hanya atribut yang relevan: `id_pegawai`, `nip`, `nama`, `id_unit` (jika ada), `status`, `bebas_lokasi`. |
| `ref_unit`, `ref_unit_ext` | Master OPD/Unit Kerja. Ditarik untuk agregasi dashboard. |
| `present_rekap` | **Sumber utama data kehadiran.** Lat/long berangkat-pulang, waktu masuk-pulang, nama lokasi, status harian. |
| `present_sikara_log` | Log mentah dengan payload JSON di kolom `data`. Perlu discovery untuk memastikan isinya (kemungkinan ada device info, accuracy, dll). |
| `present_device` | Registrasi device (IMEI). Tidak ada kolom timezone. |
| `ref_lokasi_unit` | Master geofence SIKARA (lat/long + radius). Tidak memiliki aturan hari/jam. |
| `ref_bantu_unit` | Mapping unit kerja ↔ ref_lokasi_unit. Satu OPD dapat memiliki banyak lokasi absensi valid. |
| `fake_gps` | Daftar package aplikasi Fake GPS yang dikenal. Dipakai sebagai referensi enrichment. |
| `present_group` | **Template jadwal kerja** — jam per hari-dalam-minggu (sen/sel/rab/kam/jum/sab/min × awal/akhir) dengan rentang tanggal `berlaku`–`berakhir`. Sudah mendukung multi-jadwal termasuk Ramadhan. |
| `present_presensi` | **Mapping pegawai → grup jadwal** dengan timestamp. Pegawai bisa berpindah grup seiring waktu. |
| `present_masuk` | **Override jadwal per tanggal spesifik** (cuti bersama, HUT Provinsi, acara khusus). |
| `present_aturan` | Parameter toleransi per periode (threshold keterlambatan, dll). Perlu discovery isi data. |
| `present_libur` | Daftar hari libur (tanggal + keterangan). |
| `present_ijin` | Ijin per pegawai dengan rentang tanggal (pendukung konteks review). |
| `present_maps_logs` | Log granular per sesi absensi (lat/long/jam/jamke/id_maps). Lebih detail daripada `present_rekap`. |

### 5.2. Strategi sinkronisasi

- **User MySQL baru: `simpeg_api`** dengan `GRANT SELECT` terbatas pada tabel-tabel di atas. Dibuat oleh personel teknis SIKARA.
- **Job sinkronisasi Laravel berjalan setiap 15 menit**, menarik delta berdasarkan `cdate` / `updated_at` / `created_at` terakhir yang tercatat.
- **Staging tables di PostgreSQL** menggunakan naming convention `sync_<nama_tabel_sumber>` (misal `sync_present_rekap`, `sync_peg_pegawai`).
- **Log sinkronisasi** dicatat di tabel `sync_log` (kapan mulai, kapan selesai, jumlah baris, error jika ada).

### 5.3. Discovery yang harus dilakukan lebih dulu

Sebelum Fase 1 dimulai penuh, dua discovery wajib:

1. **Isi `present_sikara_log.data`** — ambil 20 baris sampel, parse JSON, dokumentasikan skema payload sebenarnya. Kritis untuk mengetahui apakah timezone perangkat, akurasi GPS, atau device fingerprint tersedia.
2. **Kualitas `present_rekap`** — berapa persen baris punya lat/long lengkap, sebaran `jenis_presensi`, frekuensi baris per hari, pola waktu `cdate`.

Hasil discovery dicatat di `docs/discovery-sikara.md`.

### 5.4. Mekanisme jadwal kerja yang sudah ada di SIKARA

Temuan penting dari analisis skema: SIKARA sudah memiliki mekanisme lengkap untuk mengelola **jadwal kerja multi-periode** (termasuk jadwal Ramadhan dengan jam pendek). PANDORA **tidak perlu membangun logika jadwal sendiri** — cukup mengonsumsi struktur yang sudah ada.

Empat tabel yang berinteraksi:

1. **`present_group`** — template jadwal kerja per grup. Punya 18 kolom: `id_group`, `nama_group`, `berlaku` (date), `berakhir` (date), dan jam awal/akhir untuk tiap hari dalam minggu (`sen_awal`, `sen_akhir`, `sel_awal`, `sel_akhir`, ..., `min_awal`, `min_akhir`). Rentang `berlaku`–`berakhir` memungkinkan berbagai grup berjalan paralel pada periode berbeda (contoh: grup "Ramadhan 2026" dengan berlaku 17 Feb – 19 Mar 2026 dengan jam 08.00–14.00, grup "Reguler" dengan jam 07.30–16.00).

2. **`present_presensi`** — tabel mapping pegawai ke grup. Berisi `id_pegawai`, `id_group`, dan `cdate` (kapan assignment dibuat). Pegawai bisa berpindah grup seiring waktu; logika pengambilan harus memilih assignment terbaru yang relevan untuk tanggal yang sedang diproses.

3. **`present_masuk`** — override jadwal per **tanggal spesifik**. Kolom: `tanggal`, `id_group`, `status`, `masuk`, `pulang`. Digunakan untuk hari yang jam kerjanya berbeda dari template (cuti bersama, HUT Provinsi, acara khusus). Jika entri ada untuk tanggal tertentu → gunakan jam override, bukan template grup.

4. **`present_aturan`** — parameter toleransi per periode (`id_periode`, `id_tipe`, `nilai`). Kemungkinan berisi threshold keterlambatan yang mengubah klasifikasi dari TW menjadi MKTTW. Semantik lengkap perlu dikonfirmasi lewat discovery produksi.

**Algoritma resolusi jadwal efektif** untuk satu `(id_pegawai, tanggal)`:

```
FUNGSI resolve_jadwal(id_pegawai, tanggal):
    1. Jika tanggal ada di present_libur:
          return { tipe: 'libur', jam_masuk: null, jam_pulang: null }
    2. Jika present_masuk ada entri untuk (tanggal, id_group pegawai pada tanggal itu):
          return { tipe: 'override', jam_masuk, jam_pulang, id_group }
    3. Cari id_group efektif:
          group = present_presensi WHERE id_pegawai = X AND cdate <= tanggal
                                   ORDER BY cdate DESC LIMIT 1
          → id_group
    4. Cari template jadwal:
          template = present_group WHERE id_group = id_group_efektif
                                   AND berlaku <= tanggal <= berakhir
       Jika tidak ketemu: fallback ke template aktif umum atau flag error
    5. Ambil jam berdasarkan day-of-week:
          day = hari(tanggal)  // senin, selasa, dst
          jam_masuk = template[day + '_awal']
          jam_pulang = template[day + '_akhir']
       Jika jam_masuk NULL → hari ini tidak dijadwalkan kerja (libur grup).
    6. Terapkan toleransi dari present_aturan (jika relevan).
    7. return { tipe: 'template', jam_masuk, jam_pulang, id_group }
```

Semua rule engine PANDORA (deteksi keterlambatan, deteksi absensi di luar jam kerja, deteksi pulang cepat) **wajib** memanggil fungsi ini lebih dulu untuk mengetahui jadwal yang benar-benar berlaku pada baris kehadiran yang diperiksa.

---

## 6. Model Data PANDORA

### 6.1. Staging layer (mirror dari SIKARA)

Tabel-tabel di PostgreSQL dengan prefix `sync_` yang merupakan cermin tabel SIKARA terkait. Tidak diubah strukturnya agar mudah re-sync. Hanya ditambahkan metadata ringan: `synced_at`, `source_checksum`.

### 6.2. Master data asli PANDORA

**`geofence_zones`** — definisi zona geografis (boleh melampaui `ref_lokasi_unit` SIKARA).
```
id, nama_zona, polygon (PostGIS), lat_center, long_center, radius_meter, aktif, created_at, updated_at
```

**`geofence_rules`** — aturan kapan zona berlaku (jawaban atas kasus "Lapangan Agatis hanya Senin & Selasa").
```
id, geofence_zone_id, hari_of_week (0–6), jam_mulai, jam_selesai,
jenis_kegiatan (apel_pagi / senam_sore / jam_kerja / dll),
berlaku_mulai, berlaku_sampai, unit_kerja_ids (JSONB, opsional scope per OPD),
catatan, created_by, created_at, updated_at
```

**`whitelist_pegawai`** — pengecualian PANDORA-native untuk kasus yang tidak tercermin di SIKARA. Scope terbatas: sebagian besar pengecualian kehadiran (dinas luar, dispensasi) sudah ada di kolom status `present_rekap` sehingga tidak perlu diduplikasi. Tabel ini hanya untuk kasus yang tidak ter-handle SIKARA, misal pegawai dengan `bebas_lokasi=1` yang memang bertugas lapangan secara permanen.
```
id, id_pegawai, jenis_whitelist, alasan, berlaku_mulai, berlaku_sampai, created_by, created_at
```

**Catatan penting tentang kolom status di `present_rekap`:**

| Kolom | Arti | Sifat |
|---|---|---|
| `tw` | Tepat Waktu (masuk kerja) | Klasifikasi output |
| `mkttw` | Masuk Kerja Tidak Tepat Waktu | Klasifikasi output |
| `pktw` | Pulang Kerja Tepat Waktu | Klasifikasi output |
| `plc` | Pulang Lebih Cepat | Klasifikasi output |
| `tk` | Tanpa Kehadiran (tidak masuk) | Klasifikasi output |
| `ta` | Tidak Absen (pagi atau pulang) | Klasifikasi output |
| `i` | Izin | Alasan ketidakhadiran — **tidak** otomatis whitelist terhadap anomali lokasi/waktu (tidak ada larangan tetap absen saat izin). |
| `s` | Sakit | Alasan ketidakhadiran — **tidak** otomatis whitelist. |
| `c` | Cuti | Alasan ketidakhadiran — **tidak** otomatis whitelist. |
| `dl` | Dinas Luar | Alasan ketidakhadiran — **whitelist** dengan aturan khusus (lihat section 7.2). Terkait pembayaran uang harian perjalanan dinas. |
| `dsp` | Dispensasi | Alasan ketidakhadiran — **whitelist** penuh (boleh tidak absen). |
| `ll` | Libur | Klasifikasi output |
| `d` | *(belum diketahui — butuh discovery)* | Open question |

### 6.3. Feature engineering & output analitik

**`features_kehadiran_harian`** — hasil feature engineering per pegawai per hari.
```
id, id_pegawai, tanggal,
-- Fitur gerak & lokasi
velocity_berangkat_pulang (km/jam),
velocity_vs_kemarin (km/jam antar sesi berurutan),
jarak_dari_geofence_berangkat (meter),
jarak_dari_geofence_pulang (meter),
geofence_match_flag (enum: match / no_match / ambiguous / exempt),
aplikasi_fake_gps_terdeteksi (boolean, jika data tersedia),
-- Konteks jadwal efektif (hasil resolve_jadwal, lihat section 5.4)
id_group_efektif (int),             -- grup jadwal yang berlaku pada tanggal ini
sumber_jadwal (enum: libur / override / template / undefined),
jam_masuk_ekspektasi (time),
jam_pulang_ekspektasi (time),
-- Fitur temporal (dihitung RELATIF terhadap jam_masuk_ekspektasi)
deviasi_masuk_vs_jadwal_ekspektasi (menit),   -- selisih masuk aktual vs jadwal grup
deviasi_pulang_vs_jadwal_ekspektasi (menit),  -- selisih pulang aktual vs jadwal grup
deviasi_waktu_masuk_vs_median_personal (menit),
deviasi_waktu_masuk_vs_median_unit (menit),
-- Snapshot status SIKARA (kondisi saat feature dihitung)
status_sikara_tw (boolean),       -- tepat waktu
status_sikara_mkttw (boolean),    -- masuk tidak tepat waktu
status_sikara_pktw (boolean),     -- pulang tepat waktu
status_sikara_plc (boolean),      -- pulang lebih cepat
status_sikara_tk (boolean),       -- tanpa kehadiran
status_sikara_ta (boolean),       -- tidak absen
alasan_ketidakhadiran (enum: i / s / c / dl / dsp / null),
-- Hasil rule engine
rule_compliance_flag (enum: compliant / violation / no_rule_applicable / pending_status_finalization),
-- Versioning & lifecycle
status_data_final (boolean),      -- true jika data sumber di-snapshot pasca tutup-bulan
computed_at_run_id (uuid),        -- id run pipeline (harian atau bulanan)
created_at, updated_at
```

Dua kolom yang perlu perhatian khusus:

- **`status_data_final`** — ditandai `true` hanya setelah siklus bulanan selesai (mencerminkan kenyataan bahwa status `dl/i/s/c/dsp` biasanya di-input operator menjelang akhir bulan untuk keperluan pencairan TPP). Rule engine harus memperlakukan baris dengan `status_data_final=false` sebagai "tentatif".
- **`computed_at_run_id`** — karena satu `(id_pegawai, tanggal)` bisa di-recompute beberapa kali (harian awal, bulanan akhir), kolom ini menjadi jejak audit kapan baris itu terakhir di-refresh.

**`anomaly_flags`** — output dari pipeline ML + rule engine.
```
id, id_pegawai, tanggal, jenis_anomali (enum: fake_gps / geofence_violation / velocity_outlier / temporal_outlier / combination),
confidence (0–1),
metode_deteksi (enum: isolation_forest / dbscan / rule_engine / combination),
model_version,
metadata (JSONB — detail fitur yang memicu),
status_review (enum: belum_direview / valid / false_positive),
direview_oleh, direview_pada, catatan_review,
detected_at, created_at
```

### 6.4. Integrity & Audit

**`log_presensi_pandora`** — salinan append-only log presensi yang menjadi basis hash-chain.
Kolom tambahan: `hash_current`, `hash_prev`, `sequence_no`, `invalidated_at`, `invalidation_reason`, `invalidated_by`.

**`ledger_anchor`** — bukti anchoring harian ke Bitcoin.
Mengacu pada struktur yang sudah didefinisikan di PROMPT-TINGKAT-2-INTEGRITY.md (tanggal, Merkle root, ots_proof, btc_block_hash, status, dsb).

**`audit_trail_pandora`** — siapa melakukan apa di PANDORA (login, lihat data pegawai, ubah geofence, review anomali, ekspor laporan).
```
id, user_id, aksi, entitas, entitas_id, metadata, ip_address, user_agent, created_at
```

---

## 7. Pipeline Analitik

### 7.1. Alur pipeline — dua mode

Operator SIKARA umumnya menginput status formal (`dl`, `i`, `s`, `c`, `dsp`) **menjelang akhir bulan**, saat proses pencairan Tambahan Penghasilan Pegawai (TPP). Artinya pada H-1 saat pipeline harian jalan, kolom status kemungkinan besar masih kosong atau belum mencerminkan kondisi sebenarnya. Ini fakta operasional, bukan anomali.

Konsekuensinya, pipeline PANDORA harus dipisah menjadi dua mode:

#### 7.1.1. Mode harian (running setiap dini hari untuk data H-1)

Fokus: **Tingkat 1 — anomali yang tidak bergantung pada status operator.**

1. **Tarik data** dari `sync_present_rekap` untuk tanggal H-1. Sekaligus re-sync tabel-tabel jadwal (`present_group`, `present_presensi`, `present_masuk`, `present_libur`, `present_aturan`) untuk memastikan snapshot jadwal mutakhir.
2. **Resolusi jadwal efektif** — untuk tiap `(id_pegawai, tanggal)`, panggil `resolve_jadwal()` (lihat section 5.4) untuk menentukan `id_group_efektif`, `sumber_jadwal`, `jam_masuk_ekspektasi`, `jam_pulang_ekspektasi`. Simpan hasilnya di `features_kehadiran_harian`.
3. **Feature engineering** tanpa kolom status (karena belum bisa dipercaya) — lanjutkan isi `features_kehadiran_harian` dengan fitur gerak, lokasi, dan temporal. Deviasi waktu dihitung relatif terhadap jam ekspektasi grup, bukan jam kerja generik. Set `status_data_final = false`.
4. **Deteksi Tingkat 1**:
   - Velocity anomaly ekstrem (> 300 km/jam antar sesi berurutan)
   - Absensi dua lokasi dalam menit yang sama
   - Koordinat di luar NKRI
   - Package aplikasi Fake GPS terdeteksi (join dengan tabel `fake_gps` SIKARA)
   - Absensi pada `sumber_jadwal = 'libur'` (pegawai tidak dijadwalkan kerja sama sekali)
5. **Output ke `anomaly_flags`** dengan `tingkat = 1` dan `status_review = belum_direview`.
6. **Isolation Forest & DBSCAN** juga dijalankan untuk insight awal, tapi hasilnya **tidak dikirim ke laporan bulanan final** — hanya untuk dashboard eksplorasi admin DKISP.
7. **Tingkat 2 ditunda.** Rule compliance terhadap `geofence_rules` dicatat dengan flag `pending_status_finalization` jika status relevan (DL/DSP) belum ada.

Output harian: dashboard eksekutif menampilkan Tingkat 1 (hal yang pasti, tidak bergantung operator) dan indikator kuantitatif umum (persen kehadiran, tren).

#### 7.1.2. Mode bulanan (running pada tanggal 5 bulan berikutnya)

Fokus: **Tingkat 2 — rule violations formal, dijalankan setelah status SIKARA final.**

1. **Re-sync** seluruh `present_rekap` bulan lalu (untuk menangkap status yang baru di-input operator).
2. **Re-compute `features_kehadiran_harian`** untuk seluruh bulan dengan `status_data_final = true`.
3. **Rule engine — pemeriksaan compliance** dengan mempertimbangkan status:
   - Jika `dl=1` atau `dsp=1`: terapkan aturan khusus (lihat section 7.2).
   - Jika tidak ada status whitelist: cek terhadap `geofence_rules`.
4. **Isolation Forest & DBSCAN** dijalankan pada dataset final bulanan (dengan status yang sudah confirmed).
5. **Output ke `anomaly_flags`** dengan `tingkat = 2` atau `3` sesuai hasilnya.
6. **Laporan bulanan** disiapkan untuk Inspektorat/BKD.
7. **Invalidasi anomaly_flags lama** yang ternyata ter-cover oleh status baru (misal: awalnya Tingkat 1 "absen di weekend", ternyata operator input DL retroaktif → flag ditandai `false_positive_resolved_by_status_update`).

Output bulanan: laporan formal ke Inspektorat dengan data yang sudah stabil.

#### 7.1.3. Konsolidasi dan notifikasi

- Entry baru di `anomaly_flags` masuk ke Redis queue.
- Dashboard admin DKISP menampilkan antrean review.
- Laporan bulanan di-render sebagai PDF yang bisa diekspor auditor.

### 7.2. Tiered labeling, aturan DL formal, dan evaluasi

Karena PANDORA tidak punya akses ke kebenaran mutlak "ini pasti curang" (kewenangan investigasi ada di Inspektorat, bukan DKISP), pendekatan labeling tidak biner (curang/tidak) melainkan **bertingkat berdasarkan tingkat keyakinan dan konteks kasus**.

#### 7.2.1. Empat tingkat klasifikasi anomali

| Tingkat | Kriteria | Siapa yang bisa menegaskan | Deteksi dijalankan di |
|---|---|---|---|
| **1 — Physical impossibility** | Tidak mungkin terjadi secara fisik. Velocity > 300 km/jam, absensi dua lokasi menit yang sama, koordinat di luar NKRI, Fake GPS terdeteksi. | Sistem (rule deterministik). Tidak butuh review manusia untuk menegaskan validitasnya. | Pipeline harian |
| **2 — Rule violation formal** | Melanggar aturan tertulis setelah mempertimbangkan status SIKARA. Contoh: absensi di Lapangan Agatis hari Rabu (aturan hanya Senin–Selasa), absen sore di hari pertama DL, absen di hari DL berikutnya. | Sistem (rule engine) — setelah status final pasca tutup bulan. | Pipeline bulanan |
| **3 — Statistical outlier** | Anomali multivariate/spatial yang terdeteksi Isolation Forest atau DBSCAN, tapi tidak melanggar rule formal. Butuh verifikasi manusia. | Pimpinan OPD bersangkutan (konfirmasi konteks) + Inspektorat (penilaian akhir). | Pipeline bulanan |
| **4 — False positive candidate** | Terdeteksi sistem tapi kemungkinan besar ada konteks legitimate. Contoh: `bebas_lokasi=1` yang memang tugas lapangan, ada SK dinas luar retroaktif. | DKISP (tandai false positive dengan alasan). | Manual review |

#### 7.2.2. Aturan DL (formalisasi)

Berdasarkan praktik operasional yang berlaku:

- **Status DL terkait pembayaran uang harian perjalanan dinas** — operator punya insentif untuk input dengan benar, tapi biasanya di akhir bulan saat pencairan TPP.
- **Aturan waktu absensi saat DL:**

Misalkan pegawai ber-DL pada rentang tanggal `T1, T2, ..., Tn`:

| Tanggal | Sesi | Perilaku diharapkan | Pelanggaran |
|---|---|---|---|
| **T1 (hari berangkat)** | Masuk pagi | Boleh ada absensi (terlanjur absen sebelum berangkat) | Tidak ada |
| **T1 (hari berangkat)** | Pulang sore | Tidak boleh absen sore | Ada absensi sore → `anomali_dl_violation_sore_t1` |
| **T2, ..., Tn** | Masuk pagi atau pulang sore | Tidak boleh absen sama sekali | Ada absensi (masuk/pulang) → `anomali_dl_violation_hari_lanjutan` |

Pseudocode rule engine saat mode bulanan:

```
untuk tiap baris di present_rekap dengan dl=1:
    tanggal_dl_terawal = MIN(tanggal) untuk pegawai ini pada periode DL kontinyu
    
    jika baris.tanggal == tanggal_dl_terawal:  // T1
        jika ada masuk (lat_berangkat, long_berangkat, foto_berangkat):
            → compliant (boleh)
        jika ada pulang (lat_pulang, long_pulang, foto_pulang):
            → VIOLATION tingkat 2: "absen sore di hari pertama DL"
    
    jika baris.tanggal > tanggal_dl_terawal:  // T2, T3, ...
        jika ada masuk atau pulang:
            → VIOLATION tingkat 2: "absen di hari DL berikutnya"
```

**Catatan implementasi:** determinasi "periode DL kontinyu" butuh logika pengelompokan — kalau pegawai punya `dl=1` di tanggal 10, 11, 12, 15, 16, maka periode kontinyu adalah `[10,11,12]` dan `[15,16]` (terpisah oleh 13–14 non-DL), sehingga `T1` adalah tanggal 10 dan 15 masing-masing.

#### 7.2.3. Aturan DSP

Pegawai dengan `dsp=1` (dispensasi) bebas tidak absen. Tidak dihasilkan anomali terkait ketidakhadiran.

#### 7.2.4. I, S, C — bukan whitelist otomatis

Penting: status `i` (izin), `s` (sakit), `c` (cuti) **tidak** secara otomatis menjadi whitelist dari anomali. Tidak ada larangan formal bahwa pegawai yang berstatus izin/sakit/cuti tidak boleh melakukan absensi. Anomali terkait lokasi/waktu tetap diperiksa seperti biasa; status ini hanya menjadi konteks saat review manusia.

#### 7.2.5. Evaluasi model

Karena ground truth nyata baru muncul setelah Inspektorat menindaklanjuti, evaluasi dilakukan berlapis:

1. **Precision proxy** — dari anomaly_flags Tingkat 1–2 yang diteruskan ke Inspektorat, berapa yang dikonfirmasi setelah pemeriksaan. Muncul dengan delay.
2. **Recall sulit diukur langsung** — kita tidak tahu jumlah kecurangan yang luput. Proxy: **injeksi kasus sintetis** ke dataset historis (modifikasi beberapa baris dengan lat/long ekstrem atau pola yang tidak masuk akal), lalu cek apakah sistem menangkap. Metrik internal, untuk tuning.
3. **Analisis false positive** — pola yang paling sering misfire, untuk tuning threshold dan rule.

### 7.3. Pembagian peran dalam siklus review

Sistem PANDORA menyajikan kandidat anomali. . Pembagian peran yang realistis:

| Peran | Yang bisa dilakukan | Yang tidak bisa dilakukan |
|---|---|---|
| **Sistem PANDORA** | Mendeteksi Tingkat 1 otomatis; mengkategorikan Tingkat 2–4 berdasarkan rule dan ML. | Menilai niat pegawai; menuduh kecurangan. |
| **Admin DKISP** (Super Admin) | Meninjau antrean anomali; mengoreksi kategori jika sistem salah taksir (misal Tingkat 2 sebenarnya Tingkat 4 karena SK dinas luar ada di arsip); menandai false positive dengan alasan; mengelola geofence rules. | Memutuskan sanksi; menginvestigasi pegawai OPD lain. |
| **Pimpinan OPD** | Memberikan konteks kasus Tingkat 3 (confirm/deny absensi pegawainya yang terdeteksi janggal). | Menghapus anomaly_flags secara permanen. |
| **Inspektorat Daerah** | Menerima laporan bulanan Tingkat 1–2; melakukan pemeriksaan formal; memutuskan tindak lanjut (teguran, pemeriksaan, penutupan kasus). | — (kewenangan penuh di wilayah audit) |
| **BKD** (pemilik probis) | Menyusun kebijakan kepegawaian berbasis pola yang tersaji di PANDORA; memelihara master data SIMPEG/SIKARA. | — |

Alur umum: sistem mendeteksi → DKISP melakukan pra-review dan klasifikasi kategori → laporan bulanan dikirim ke Inspektorat → Inspektorat memutuskan → hasil keputusan dikembalikan ke PANDORA sebagai ground truth tertunda untuk tuning model.

---

## 8. Integrity Layer

Mengikuti rancangan yang sudah ada di PROMPT-TINGKAT-2-INTEGRITY.md, dengan beberapa penyesuaian:

- **Cakupan hash-chain:** hanya menjangkau `log_presensi_pandora` (salinan internal PANDORA), bukan tabel sumber SIKARA. Ini karena PANDORA tidak punya kontrol tulis atas SIKARA.
- **Makna anchoring:** membuktikan bahwa "pada tanggal X, PANDORA telah mencatat set log presensi ini", bukan bahwa "SIKARA jujur mencatat". Batasan ini harus eksplisit di dokumentasi auditor.


---

## 9. Antarmuka Pengguna

### 9.1. Dashboard Eksekutif (Pimpinan)

- Halaman tunggal, mobile-first, dapat diakses dalam 2–3 menit.
- Komponen utama:
  - Angka besar: kehadiran hari ini (%), tren 7 hari, jumlah OPD dengan alert.
  - Narasi otomatis singkat: *"Hari ini tingkat kehadiran 94%, turun 2% dari minggu lalu. Perhatikan: OPD X mengalami penurunan signifikan."*
  - Ringkasan per OPD (bar chart horizontal dengan warna status).
  - Peta panas anomali (PostGIS + Leaflet/MapLibre).
  - Drill-down ke daftar anomali di OPD tertentu.

### 9.2. Admin Console (DKISP)

- Sidebar multi-menu:
  - Status sinkronisasi SIKARA (kapan terakhir, error jika ada).
  - Manajemen Geofence: CRUD zona + aturan hari/jam.
  - Review Anomali: antrean `anomaly_flags` dengan status `belum_direview`, tombol tandai `valid` / `false_positive`.
  - Whitelist Pegawai.
  - Manajemen User PANDORA.
  - Audit Trail (hanya tampil, tidak bisa diubah).

### 9.3. Modul Auditor (Inspektorat/BKD)

- Tampilan read-only.
- Filter anomali berdasarkan pegawai, unit kerja, jenis anomali, rentang waktu.
- Tombol "Ekspor Laporan" → PDF berisi ringkasan + daftar rinci.
- Bagian "Verifikasi Integritas": upload file `.ots` dari hari tertentu, sistem menunjukkan status konfirmasi ke Bitcoin.

---

## 10. Keamanan & Perlindungan Data

1. **User DB read-only** di SIKARA (prinsip hak akses minimum).
2. **Tidak menyimpan data pribadi yang tidak perlu** — NIK, NPWP, foto wajah SIKARA tidak ditarik.
3. **Foto absensi** — jika dibutuhkan untuk verifikasi auditor, hanya diambil referensinya (path/URL SIKARA), tidak di-copy ke PANDORA.
4. **Audit trail** semua akses ke data pegawai spesifik.
5. **Enkripsi in-transit** — TLS 1.2+ di Nginx.
6. **Enkripsi at-rest** — harus dikonfirmasi apakah disk VM di-encrypt.
7. **Retensi data** — anomaly_flags disimpan 5 tahun (sesuai retensi kepegawaian); staging data di-rotate 90 hari.
8. **Kepatuhan UU PDP 2022** — pemrosesan data pribadi ASN didasarkan pada kewenangan operasional pemerintahan yang sah (payung hukum: Pergub dan SK Tim Koordinator SPBE).

---

## 11. Roadmap 

| Periode | Fase | Keluaran utama |
|---|---|---|
|  **Fase 1: Fondasi Data** | User `simpeg_api` di SIKARA, staging layer, job sync 15 menit, discovery `present_sikara_log.data`, master `geofence_zones` & `geofence_rules` |
|  **Fase 2: Engine Anomali** | Feature engineering harian, rule engine compliance, pipeline Isolation Forest + DBSCAN, tabel `anomaly_flags` terisi |
| **Fase 3: Dashboard Eksekutif** | UI Pimpinan mobile-first, narasi otomatis, peta panas, ringkasan OPD |
|  **Fase 4: Admin & Audit Module** | Admin console DKISP, modul read-only Inspektorat/BKD, export PDF laporan |
|  **Fase 5: Integrity Layer** | Hash-chain `log_presensi_pandora`, anchoring OpenTimestamps harian |
| O **Fase 6: UAT & Polishing** | Demo internal Pimpinan, demo SKALA, perbaikan berdasarkan feedback |
|  **Fase 7: Handover** | Manual pengguna, manual operasi, sustainability plan, handover ke BKD |

---

## 12. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| `present_sikara_log.data` ternyata tidak mengandung timezone/device info | Fitur deteksi timezone sulit dibangun | Ubah definisi fitur menjadi "deviasi pola waktu" berbasis statistik historis |
| Perubahan skema SIKARA tanpa pemberitahuan | Pipeline sync pecah | Monitoring otomatis sync error + alert ke admin |
| Dataset tidak cukup untuk training ML | Pipeline false positive tinggi | Backfill historis 6–12 bulan + manual labeling bertahap |
| Pimpinan tidak terbiasa membaca dashboard | Aplikasi dibangun tapi tidak dipakai | Dashboard mobile-first  |
| Operator SIKARA telat input status DL/DSP/I/S/C (biasanya akhir bulan saat pencairan TPP) | Pipeline harian menghasilkan banyak false positive Tingkat 2 yang akan "sembuh" setelah tutup bulan; kalau tidak ditangani, dashboard membanjiri Pimpinan dengan alarm palsu | Pipeline dibagi dua mode (harian hanya untuk Tingkat 1; Tingkat 2 ditunda ke pipeline bulanan). Flag `pending_status_finalization` agar admin DKISP tidak salah tindak lanjut. Laporan resmi ke Inspektorat hanya dari pipeline bulanan |


---

## 13. Tata Kelola Dokumentasi

- **Repositori:** GitHub privat (tersendiri dari source code `pandora/src`).
- **Struktur folder:**
  ```
  pandora-docs/
  ├── DESIGN.md               ← dokumen ini
  ├── DECISION-LOG.md         ← index ADR
  ├── OPEN-QUESTIONS.md       ← hal yang belum final
  ├── decisions/              ← ADR individual
  ├── specs/                  ← spec per fitur/sprint
  ├── discovery/              ← hasil eksplorasi data
  └── handover/               ← dokumen handover akhir ke BKD
  ```
- **Bahasa:** Indonesia baku (sesuai PUEBI) untuk dokumen utama; lampiran teknis boleh bilingual jika diminta mitra SKALA.
- **Versioning:** semantic-like (`0.1`, `0.2` untuk draft, `1.0` setelah sign-off Pimpinan).

---

## 14. Hal yang Masih Terbuka (per 18 April 2026)

1. 
2. Skema payload `present_sikara_log.data` — belum dikonfirmasi, butuh discovery di data produksi.
4. Apakah foto absensi perlu diakses dari PANDORA atau cukup referensi?
5. Rencana sustainability pasca-2026: siapa yang melanjutkan operasional?
6. **Arti kolom `d` di `present_rekap`** — belum diketahui, perlu tanya ke tim SIKARA atau dilihat via sampel data.
7. Bagaimana menangani kasus operator **lupa** input status DL/I/S/C? Apakah Pimpinan OPD bisa memberi "veto konteks" retroaktif via admin console PANDORA?
8. Tanggal cut-off pipeline bulanan — default usulan tanggal 5 bulan berikutnya. Perlu konfirmasi kapan umumnya operator BKD selesai input status untuk bulan sebelumnya.

Jawaban-jawaban akan dipindahkan ke `OPEN-QUESTIONS.md` saat repositori dokumen dibuat.

---

*Dokumen ini akan terus dikembangkan. Revisi berikutnya diharapkan setelah discovery `present_sikara_log.data` selesai dan master `geofence_rules` draft pertama disepakati.*
