# Rand Index

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
Rand Index (RI) mengukur kesesuaian antara dua partisi data — biasanya hasil clustering algoritma vs ground truth label manual. Adjusted Rand Index (ARI) mengoreksi kebetulan acak.

## Rumus
$$\text{RI} = \frac{a + b}{\binom{n}{2}}$$

- $a$ = pasangan titik yang berada di cluster sama di kedua partisi.
- $b$ = pasangan titik yang terpisah di kedua partisi.

## Adjusted Rand Index (ARI)
$$\text{ARI} = \frac{\text{RI} - E[\text{RI}]}{1 - E[\text{RI}]}$$

Rentang: -1 s/d 1. ARI=0 = acak. ARI=1 = sempurna. ARI negatif = lebih buruk dari acak.

## Studi Kasus PANDORA
Inspektorat mengelompokkan 148 OPD menjadi 3 kategori kedisiplinan secara manual (teladan/sedang/perlu pembinaan). K-Means juga menghasilkan 3 cluster dari fitur present_rekap.

- RI = 0.82 → 82% pasangan OPD dikelompokkan sama.
- ARI = 0.68 → sangat baik, jauh di atas kebetulan acak.

```python
from sklearn.metrics import adjusted_rand_score

# label_inspektorat: pengelompokan manual oleh inspektorat
# label_kmeans: hasil K-Means pada fitur kehadiran
ari = adjusted_rand_score(label_inspektorat, label_kmeans)
print(f"ARI: {ari:.3f}")  # 0.68
```

Ini memvalidasi bahwa fitur kuantitatif dari PANDORA mampu menangkap penilaian kualitatif inspektorat.

## Pitfalls
- RI bias pada K besar (cluster kecil-kecil cenderung skor tinggi).
- Butuh label ground truth; tidak berguna pada pure unsupervised.
- ARI negatif artinya hasil lebih buruk daripada acak — perlu revisi fitur/K.

## Kaitan
- → [Performance Clustering](03-performance-clustering.md)
- → [Silhouette Score](07-silhouette.md)
