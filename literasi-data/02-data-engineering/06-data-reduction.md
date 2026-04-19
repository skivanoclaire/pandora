# Data Reduction

**Kategori:** Data Engineering | **Level:** Menengah

## Ringkasan
Data reduction mengurangi volume data tanpa kehilangan informasi signifikan, agar komputasi lebih cepat dan model tidak overfit. Dua jalur: mengurangi kolom (feature selection/extraction) dan mengurangi baris (sampling/aggregation).

## Teknik Utama

### Pengurangan Kolom
- **Feature Selection** — filter (korelasi, chi-square), wrapper (recursive elimination), embedded (Lasso).
- **Feature Extraction** — PCA, t-SNE, UMAP untuk reduksi dimensi.

### Pengurangan Baris
- **Stratified sampling** — mempertahankan proporsi kelas anomali vs normal.
- **Aggregation** — rangkum dari per-jam ke per-hari atau per-bulan.

## Studi Kasus PANDORA
Dari present_rekap, setiap pegawai bisa punya 50+ fitur turunan (per shift, per lokasi, per status). Model menjadi lambat dan berisiko overfit.

```python
from sklearn.decomposition import PCA
from sklearn.feature_selection import SelectKBest, f_classif

# Feature selection: pilih 10 fitur terkuat untuk deteksi anomali
selector = SelectKBest(f_classif, k=10)
X_selected = selector.fit_transform(X_train, y_train)

# Aggregation: dari log per jam ke ringkasan harian per OPD
df_harian = df_kehadiran.groupby(['opd', 'tanggal']).agg(
    rata_telat=('menit_terlambat', 'mean'),
    jumlah_tw=('tw', 'sum'),
    jumlah_tk=('tk', 'sum')
).reset_index()

# PCA: reduksi 50 fitur ke 5 komponen untuk visualisasi cluster 148 OPD
pca = PCA(n_components=5)
X_pca = pca.fit_transform(X_scaled)
```

## Pitfalls
- PCA menghilangkan interpretability (komponen tidak punya nama bermakna).
- Sampling tanpa stratifikasi bisa membuat kelas anomali (3%) hilang.
- Aggregation kehilangan detail waktu yang mungkin penting.

## Kaitan
- → [Data Preprocessing](05-data-preprocessing.md)
- → [Feature Engineering](11-feature-engineering.md)
