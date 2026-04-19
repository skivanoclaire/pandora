# Performance Clustering

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
Berbeda dengan klasifikasi, clustering tanpa label membuat evaluasi lebih tricky. Dua jenis metrik: internal (tidak butuh label) dan eksternal (butuh ground truth).

## Metrik Internal (tanpa label)

| Metrik | Semakin ... semakin baik | Rentang |
|--------|--------------------------|---------|
| Silhouette Score | Tinggi | -1 s/d 1 |
| Davies-Bouldin | Rendah | 0 s/d inf |
| Calinski-Harabasz | Tinggi | 0 s/d inf |
| WCSS/Inertia | Rendah | 0 s/d inf |

## Metrik Eksternal (butuh label)
- **Adjusted Rand Index (ARI)** — kesesuaian dengan label manual. ARI=0 acak, ARI=1 sempurna.
- **Normalized Mutual Information (NMI).**

## Studi Kasus PANDORA
Cluster 148 OPD berdasar pola kehadiran dari present_rekap, bandingkan K:

| K | Silhouette | Davies-Bouldin | Calinski-Harabasz |
|---|-----------|----------------|-------------------|
| 2 | 0.45 | 1.20 | 820 |
| 3 | **0.58** | **0.72** | **1420** |
| 4 | 0.42 | 0.95 | 1280 |
| 5 | 0.38 | 1.10 | 1100 |

K=3 konsisten terbaik di ketiga metrik. Validasi: inspektorat mengelompokkan OPD secara manual → ARI = 0.68 (sangat mirip algoritma).

```python
from sklearn.metrics import silhouette_score, davies_bouldin_score, calinski_harabasz_score

sil = silhouette_score(X_scaled, labels)
dbi = davies_bouldin_score(X_scaled, labels)
ch = calinski_harabasz_score(X_scaled, labels)
```

## Pitfalls
- Metrik internal bias ke cluster sferis → misleading untuk DBSCAN.
- Tanpa label, tetap butuh domain expert kepegawaian untuk validasi interpretasi.
- WCSS selalu turun saat K naik — jangan pilih K terbesar.

## Kaitan
- → [Silhouette Score](07-silhouette.md), [Davies-Bouldin](06-davies-bouldin.md), [Calinski-Harabasz](05-calinski-harabasz.md), [Rand Index](04-rand-index.md)
