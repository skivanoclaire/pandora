# Database

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Database adalah sistem terstruktur untuk menyimpan dan mengelola data secara efisien dan konsisten. PANDORA menggunakan PostgreSQL 16 + PostGIS 3.4 sebagai database utama, dengan data sumber dari MySQL SIMPEG.

## Jenis Database

### Relasional (SQL)
- PostgreSQL, MySQL, MariaDB — data dalam tabel dengan skema tetap, bahasa query SQL.
- Cocok untuk data transaksional dengan relasi kuat (pegawai, OPD, kehadiran).

### NoSQL
- **Document**: MongoDB (JSON-like). **Key-value**: Redis (cache PANDORA). **Graph**: Neo4j.
- Cocok untuk data semi-terstruktur atau volume sangat besar.

## SQL Contoh PANDORA

```sql
SELECT opd, 
       COUNT(CASE WHEN tw = 1 THEN 1 END) AS jumlah_tw,
       COUNT(CASE WHEN mkttw = 1 THEN 1 END) AS jumlah_mkttw,
       COUNT(CASE WHEN tk = 1 THEN 1 END) AS jumlah_tk
FROM present_rekap
WHERE tanggal BETWEEN '2026-04-01' AND '2026-04-18'
GROUP BY opd
ORDER BY jumlah_tk DESC;
```

## Studi Kasus PANDORA
Arsitektur database PANDORA:
- **PostgreSQL** (pandora-db): tabel utama present_rekap (3,3 juta+ record), pegawai (6.475), opd (148), geofence_zone, device_registration.
- **Redis** (pandora-redis): cache dashboard dan antrian queue Laravel.
- **MySQL SIMPEG** (sumber): data master kepegawaian yang disinkronisasi ke PostgreSQL.

Relasi: `present_rekap.nip → pegawai.nip`, `pegawai.opd_id → opd.id`, `device_registration.nip → pegawai.nip`.

Pipeline ETL: MySQL SIMPEG → sync job Laravel → PostgreSQL PANDORA.

## Pitfalls
- Query tanpa index pada present_rekap (3,3 juta baris) = sangat lambat. Index pada (nip, tanggal).
- Perbedaan format tanggal MySQL vs PostgreSQL perlu penanganan saat sync.
- NoSQL bukan "pasti lebih cepat" — pilih sesuai pola query.

## Kaitan
- → [Data](01-data.md)
- → [Dataset](02-dataset.md)
