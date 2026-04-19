# Silhouette Score

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
Silhouette Score mengukur seberapa mirip suatu titik dengan cluster-nya sendiri vs cluster terdekat lainnya. Rentang -1 s/d 1. Mendekati 1 = sangat baik, 0 = di perbatasan, negatif = salah cluster.

## Rumus
$$s(i) = \frac{b(i) - a(i)}{\max(a(i), b(i))}$$

- $a(i)$ = rata-rata jarak titik $i$ ke titik lain dalam cluster-nya.
- $b(i)$ = rata-rata jarak titik $i$ ke titik di cluster terdekat lainnya.

## Interpretasi

| Nilai | Arti |
|-------|------|
| 0.71 - 1.00 | Struktur cluster sangat kuat |
| 0.51 - 0.70 | Struktur cluster wajar |
| 0.26 - 0.50 | Struktur cluster lemah |
| < 0.25 | Tidak ada struktur bermakna |

## Studi Kasus PANDORA
Clustering 148 OPD berdasar fitur kehadiran dari present_rekap:

| K | Silhouette |
|---|-----------|
| 2 | 0.45 |
| 3 | **0.58** |
| 4 | 0.42 |
| 5 | 0.38 |

K=3 dipilih (struktur cluster wajar, mendekati kuat).

```python
from sklearn.metrics import silhouette_score, silhouette_samples

score = silhouette_score(X_scaled, labels)  # rata-rata keseluruhan
per_sample = silhouette_samples(X_scaled, labels)  # per OPD, untuk plot

# OPD dengan silhouette negatif → mungkin salah cluster
opd_salah = df_opd[per_sample < 0]['nama_opd'].tolist()
```

Silhouette plot per cluster membantu: bar tinggi & rata = cluster bagus; banyak bar negatif = cluster buruk.

## Pitfalls
- Komputasi O(n-squared) — lambat untuk 3,3 juta record; sample dulu.
- Bias terhadap cluster konveks/sferis.
- Titik dengan silhouette negatif = sinyal salah klaster, perlu investigasi.

## Kaitan
- → [Performance Clustering](03-performance-clustering.md)
- → [K-Means](02-kmeans.md)
- → [Davies-Bouldin Index](06-davies-bouldin.md)
