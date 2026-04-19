# Data

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Data adalah fakta mentah yang dapat direkam, disimpan, dan diolah. Bisa berupa angka, teks, gambar, koordinat GPS, atau sinyal sensor — apapun yang dapat di-encode secara digital.

## Penjelasan
Data tanpa konteks hanyalah simbol. Angka `08:05` bisa berarti waktu check-in, kode, atau durasi — bergantung skema. Karena itu data selalu hadir dalam struktur (kolom, label, tipe).

Berdasarkan struktur:
- **Terstruktur** — tabel relasional PostgreSQL PANDORA (present_rekap, pegawai).
- **Semi-terstruktur** — JSON response dari API mesin presensi.
- **Tak terstruktur** — foto selfie pegawai saat check-in, scan dokumen SK.

Berdasarkan sumber:
- **Internal:** PANDORA, SIMPEG (MySQL), SIKARA.
- **Eksternal:** BMKG (cuaca), BPS, API hari libur nasional.

## Studi Kasus PANDORA
Satu baris dari tabel present_rekap adalah "data":
```
{nip: "19870615...", tanggal: "2026-04-18", jam_masuk: "08:05",
 lat_berangkat: 3.2945, long_berangkat: 117.6310, tw: 0, mkttw: 1}
```
3,3 juta baris seperti ini dari 6.475 pegawai di 148 OPD selama bertahun-tahun membentuk basis analitik PANDORA.

## Pitfalls
- Data bukan kebenaran — bisa salah (fake GPS), terlambat (sync delay), atau terduplikasi.
- Data tanpa metadata (tipe, satuan, sumber) cepat membingungkan.
- Tanggal `0000-00-00` dari SIMPEG harus ditangani sebagai null.

## Kaitan
- → [Dataset](02-dataset.md)
- → [Tipe Data](03-tipe-data.md)
- → [Data, Informasi, Pengetahuan](../01-fondasi/02-data-informasi-pengetahuan.md)
