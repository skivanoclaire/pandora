# Data Preprocessing

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Data preprocessing adalah serangkaian langkah membersihkan dan menyiapkan data mentah agar siap dipakai algoritma. Dalam proyek nyata, fase ini menyita 60-80% waktu.

## Langkah Umum
1. **Data cleaning** — tangani missing values, outlier, duplikat.
2. **Data integration** — gabungkan data dari SIMPEG, PANDORA, BMKG.
3. **Data transformation** — normalisasi, encoding, scaling.
4. **Data reduction** — PCA, sampling, feature selection.
5. **Data discretization** — ubah menit_terlambat kontinu menjadi kategori (ringan/sedang/berat).

## Teknik Missing Values
- **Drop row** — jika missing sedikit (<5%).
- **Imputation** — isi dengan mean/median/modus.
- **Flag** — tambah kolom `is_missing_jam_pulang` sebagai sinyal.

## Studi Kasus PANDORA
Preprocessing present_rekap sebelum training model deteksi anomali:

```python
import pandas as pd

df_kehadiran = pd.read_sql("SELECT * FROM present_rekap", conn)

# Hapus duplikat check-in per pegawai per hari
df_kehadiran.drop_duplicates(['nip', 'tanggal'], inplace=True)

# Tangani tanggal invalid dari SIMPEG
df_kehadiran['tanggal'] = pd.to_datetime(df_kehadiran['tanggal'], errors='coerce')
df_kehadiran = df_kehadiran.dropna(subset=['tanggal'])

# Tangani koordinat null (GPS tidak aktif)
df_kehadiran = df_kehadiran[df_kehadiran['lat_berangkat'] != 0.0]

# Hapus outlier: velocity > 500 km/jam (tidak realistis)
df_kehadiran = df_kehadiran[df_kehadiran['velocity'] < 500]
```

## Pitfalls
- Preprocess train+test bersama-sama = data leakage. Selalu fit di train, apply di test.
- Mengisi NA sebelum split data = leakage pada statistik (mean/median).
- Menghapus semua record dengan lat/long null mungkin menghilangkan pola penting.

## Kaitan
- → [Data Quality](04-data-quality.md)
- → [Data Transformation](08-data-transformation.md)
- → [Data Reduction](06-data-reduction.md)
