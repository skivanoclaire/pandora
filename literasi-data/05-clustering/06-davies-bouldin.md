# Davies-Bouldin Index

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
Davies-Bouldin Index (DBI) mengukur rata-rata kemiripan maksimum antar cluster. Kemiripan = rasio dispersi dalam-cluster terhadap jarak antar-cluster. Semakin rendah semakin baik.

## Rumus
$$\text{DBI} = \frac{1}{k} \sum_{i=1}^{k} \max_{j \neq i} R_{ij}$$

$R_{ij} = \frac{S_i + S_j}{d(c_i, c_j)}$, di mana $S_i$ = dispersi cluster $i$, $d(c_i, c_j)$ = jarak antar centroid.

## Penjelasan
- DBI rendah → cluster ketat dan saling berjauhan.
- DBI tinggi → cluster overlap atau menyebar.
- DBI < 1 umumnya bagus, tapi konteks-dependent.

## Studi Kasus PANDORA
Clustering 148 OPD berdasar pola kehadiran dari present_rekap:

| K | DBI |
|---|-----|
| 2 | 1.20 |
| 3 | **0.72** |
| 4 | 0.95 |
| 5 | 1.10 |

K=3 punya DBI terendah → pilih K=3 (konsisten dengan CH dan Silhouette).

```python
from sklearn.metrics import davies_bouldin_score

for k in range(2, 6):
    km = KMeans(n_clusters=k, n_init=10, random_state=42)
    labels = km.fit_predict(X_scaled)
    dbi = davies_bouldin_score(X_scaled, labels)
    print(f"K={k}, DBI={dbi:.3f}")
```

## Pitfalls
- Bias terhadap cluster sferis.
- Tidak handle cluster dengan kepadatan sangat berbeda.
- Tidak ada threshold universal — gunakan untuk membandingkan antar K, bukan antar dataset.

## Kaitan
- → [Calinski-Harabasz Index](05-calinski-harabasz.md)
- → [Silhouette Score](07-silhouette.md)
- → [Performance Clustering](03-performance-clustering.md)
