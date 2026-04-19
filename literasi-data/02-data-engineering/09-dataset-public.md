# Dataset Public

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Dataset publik adalah kumpulan data yang dirilis terbuka untuk riset, pembelajaran, dan benchmarking. Berguna untuk memperkaya analisis PANDORA dengan data eksternal.

## Sumber Populer

### Umum
- **Kaggle** (kaggle.com/datasets) — ribuan dataset lintas domain.
- **UCI ML Repository** — klasik untuk machine learning.
- **Google Dataset Search** — mesin pencari dataset.

### Pemerintah Indonesia
- **Satu Data Indonesia** (data.go.id) — portal resmi.
- **BPS** (bps.go.id) — statistik sosial, ekonomi, demografi.
- **BMKG** — data cuaca & iklim harian.
- **data.kaltaraprov.go.id** — portal data Kalimantan Utara.

### Dataset Pedagogis
- **MNIST/CIFAR** — computer vision.
- **Iris/Titanic** — klasifikasi tabular klasik.

## Studi Kasus PANDORA
Untuk memperkaya analisis kehadiran 6.475 pegawai, PANDORA dapat menggabungkan data eksternal:

| Sumber | Data | Kegunaan |
|--------|------|----------|
| BMKG Kaltara | Cuaca harian (hujan, suhu) | Korelasi cuaca vs keterlambatan |
| BPS | Hari libur nasional & cuti bersama | Normalisasi hari kerja efektif |
| Satu Data Kaltara | Demografi kecamatan | Konteks jarak tempuh pegawai |

```python
# Gabungkan data cuaca BMKG dengan present_rekap
df_cuaca = pd.read_csv('bmkg_kaltara_2026.csv')
df_kehadiran = df_kehadiran.merge(df_cuaca, on='tanggal', how='left')
```

## Pitfalls
- Data publik bukan berarti boleh dipakai sembarangan — selalu cek lisensi.
- Kualitas bervariasi; banyak dataset yang tidak terverifikasi.
- Format dan granularitas berbeda antar sumber perlu harmonisasi.

## Kaitan
- → [Database](10-database.md)
- → [Dataset](02-dataset.md)
