# Algoritma Clustering

**Kategori:** Clustering | **Level:** Dasar

## Ringkasan
Clustering membagi data menjadi kelompok (cluster) sehingga item dalam satu cluster mirip dan berbeda dengan cluster lain — tanpa label training. Termasuk unsupervised learning.

## Penjelasan
Berbeda dari klasifikasi yang memerlukan data berlabel, clustering menemukan struktur tersembunyi secara otomatis. Hasilnya perlu interpretasi domain expert untuk memberi makna pada setiap cluster.

## Jenis Algoritma

| Kategori | Algoritma | Karakteristik |
|----------|-----------|---------------|
| Partitional | K-Means, K-Medoids | Tentukan K di awal |
| Hierarchical | Agglomerative | Dendogram, tidak perlu K |
| Density-based | DBSCAN, OPTICS | Menangani outlier, cluster bentuk bebas |
| Model-based | Gaussian Mixture | Probabilistik, soft assignment |

## Studi Kasus PANDORA
Segmentasi 148 OPD berdasar pola kehadiran bulanan dari present_rekap:
- Fitur: rata_kehadiran_tw, sd_keterlambatan, rasio_tk, rasio_izin.
- K-Means K=3 menghasilkan:
  - **Cluster A (85 OPD):** kehadiran tinggi, disiplin → "OPD teladan".
  - **Cluster B (48 OPD):** kehadiran sedang, volatil → "perlu monitoring".
  - **Cluster C (15 OPD):** kehadiran rendah → "prioritas intervensi".

```python
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler

X_scaled = StandardScaler().fit_transform(df_opd[['rata_tw','sd_telat','rasio_tk','rasio_izin']])
km = KMeans(n_clusters=3, n_init=10, random_state=42)
df_opd['cluster'] = km.fit_predict(X_scaled)
```

## Pitfalls
- Clustering tanpa scaling = hasil bias ke fitur berskala besar.
- K-Means asumsi cluster sferis; gunakan DBSCAN untuk deteksi anomali spasial.
- Cluster tanpa interpretasi domain = hanya angka tanpa guna.

## Kaitan
- → [K-Means](02-kmeans.md)
- → [Performance Clustering](03-performance-clustering.md)
