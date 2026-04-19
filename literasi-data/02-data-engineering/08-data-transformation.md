# Data Transformation

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Data transformation mengubah bentuk, skala, atau representasi data agar sesuai kebutuhan algoritma. Penting karena banyak algoritma (KNN, SVM, neural network) sensitif terhadap skala.

## Teknik Utama

### Scaling / Normalisasi
- **Min-Max scaling** → rentang [0,1]: $x' = (x - x_{min})/(x_{max} - x_{min})$
- **Standardization (Z-score)** → mean 0, SD 1: $x' = (x - \mu)/\sigma$
- **Robust scaling** → pakai median & IQR, tahan outlier.

### Encoding Kategorik
- **Label encoding** → ordinal (golongan: II/a=1, III/a=2).
- **One-hot encoding** → setiap kategori jadi kolom biner.
- **Target encoding** → ganti kategori dengan mean target di grup tersebut.

### Transformasi Matematis
- **Log transform** → untuk distribusi miring: `log(menit_terlambat + 1)`.
- **Binning** → kontinu jadi ordinal bucket.

## Studi Kasus PANDORA

| Fitur present_rekap | Transformasi | Alasan |
|---------------------|--------------|--------|
| lat/long_berangkat | standardization | untuk KNN/DBSCAN jarak |
| opd (148 kategori) | target encoding | one-hot terlalu banyak kolom |
| golongan | label encoding | ordinal, urutan bermakna |
| menit_terlambat | log(x+1) | distribusi sangat miring ke kanan |
| status (tw/mkttw/tk) | one-hot | nominal, tidak ada urutan |

```python
from sklearn.preprocessing import StandardScaler, LabelEncoder
scaler = StandardScaler()
df_kehadiran[['lat_berangkat','long_berangkat']] = scaler.fit_transform(
    df_kehadiran[['lat_berangkat','long_berangkat']])
```

## Pitfalls
- Scaling dilakukan SETELAH split train/test, fit di train saja.
- Label encoding pada nominal membuat model mengira ada urutan.
- One-hot pada 148 OPD = 148 kolom baru — pertimbangkan target encoding.

## Kaitan
- → [Data Preprocessing](05-data-preprocessing.md)
- → [Feature Engineering](11-feature-engineering.md)
