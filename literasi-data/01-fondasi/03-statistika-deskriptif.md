# Statistika Deskriptif

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Statistika deskriptif merangkum dan menyajikan karakteristik data tanpa menarik kesimpulan tentang populasi yang lebih luas. Tujuannya memahami "apa yang terjadi" pada data kehadiran.

## Penjelasan
Tiga pilar utama: (1) **Ukuran pemusatan** — di mana pusat data berada (mean, median, modus). (2) **Ukuran penyebaran** — seberapa lebar data tersebar (range, variance, standar deviasi, IQR). (3) **Ukuran posisi & bentuk** — kuartil, persentil, skewness, kurtosis. Visualisasi pendukung: histogram, boxplot, tabel frekuensi.

## Studi Kasus PANDORA
Dari 6.475 pegawai aktif di 148 OPD, eksplorasi present_rekap bulan Maret 2026:

- **Mean jam_masuk** harian seluruh ASN: 07:48 — menentukan baseline kedisiplinan.
- **Median durasi kerja** (jam_pulang - jam_masuk): 8 jam 12 menit; tidak terpengaruh lembur ekstrem.
- **Modus status kehadiran**: tw (tepat waktu) — paling sering muncul.
- **SD menit_terlambat per OPD**: SD kecil = OPD konsisten disiplin.
- **Boxplot per OPD**: cepat melihat OPD dengan banyak outlier keterlambatan.

```python
df_kehadiran = pd.read_sql("SELECT * FROM present_rekap WHERE tanggal BETWEEN '2026-03-01' AND '2026-03-31'", conn)
df_kehadiran.groupby('opd')['menit_terlambat'].describe()
```

## Pitfalls
- Mean sangat sensitif terhadap outlier; gunakan median jika distribusi miring.
- Jangan bandingkan SD antar variabel berskala beda — pakai coefficient of variation (CV = SD/mean).
- Statistika deskriptif tidak boleh dipakai untuk menyimpulkan populasi — itu tugas inferensial.

## Kaitan
- → [Mean, Modus, Standar Deviasi](04-mean-modus-sd.md)
- → [Data Quality](../02-data-engineering/04-data-quality.md)
