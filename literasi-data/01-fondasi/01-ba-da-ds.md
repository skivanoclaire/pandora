# Business Analytics, Data Analytics, dan Data Science

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Ketiga istilah memiliki fokus berbeda. Data Analytics menjawab "apa yang terjadi", Business Analytics menjawab "apa yang harus dilakukan", Data Science mencakup keduanya plus model prediktif berbasis machine learning.

## Penjelasan
**Data Analytics** berfokus pada eksplorasi data historis untuk memahami pola — melibatkan SQL, Excel, dan visualisasi. **Business Analytics** adalah penerapan Data Analytics dalam konteks keputusan organisasi; outputnya berupa rekomendasi, KPI, dan forecasting. **Data Science** menggabungkan statistik, pemrograman, dan machine learning untuk membangun model prediktif pada data yang lebih besar dan kompleks.

## Perbandingan

| Aspek | Data Analytics | Business Analytics | Data Science |
|-------|----------------|--------------------|--------------| 
| Fokus | Apa yang terjadi | Apa yang sebaiknya dilakukan | Memprediksi & mengoptimalkan |
| Alat | SQL, Excel, BI | BI, Statistik | Python/R, ML, Deep Learning |
| Output | Dashboard, laporan | Rekomendasi strategis | Model prediktif |

## Studi Kasus PANDORA
- **Data Analytics:** Dashboard tren kehadiran 6.475 pegawai di 148 OPD selama 7 hari terakhir — menampilkan status tw, mkttw, tk per hari.
- **Business Analytics:** Dari 3,3 juta record present_rekap, identifikasi 12 OPD dengan kehadiran <80% lalu rekomendasikan intervensi prioritas.
- **Data Science:** Bangun model prediksi risiko keterlambatan berdasar riwayat jam_masuk, hari_kerja, dan jarak geofence.

```python
df_kehadiran = pd.read_sql("SELECT * FROM present_rekap WHERE tanggal >= '2026-04-01'", conn)
ringkasan = df_kehadiran.groupby('opd')['tw'].mean().sort_values()
```

## Pitfalls
- Langsung lompat ke Data Science tanpa memahami data (skip Data Analytics).
- Menggunakan dashboard tanpa rekomendasi aksi = berhenti di level Data Analytics.
- Membangun model prediktif tanpa pertanyaan bisnis yang jelas.

## Kaitan
- → [Data Science vs AI](05-ds-vs-ai.md)
- → [Data Mining vs ML](06-datamining-vs-ml.md)
