# Calinski-Harabasz Index

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
Calinski-Harabasz Index (CH, Variance Ratio Criterion) mengukur rasio dispersi antar-cluster terhadap dispersi dalam-cluster. Semakin tinggi semakin baik — cluster ketat internal dan saling berjauhan.

## Rumus
$$\text{CH} = \frac{\text{tr}(B_k)}{\text{tr}(W_k)} \cdot \frac{n - k}{k - 1}$$

- $B_k$ = dispersi antar-cluster (between). $W_k$ = dispersi dalam-cluster (within).
- $n$ = total observasi, $k$ = jumlah cluster.

## Penjelasan
Pembilang tinggi = cluster berjauhan. Penyebut rendah = titik dalam cluster mampat. Faktor $(n-k)/(k-1)$ mengoreksi jumlah cluster.

## Studi Kasus PANDORA
Clustering 6.475 pegawai berdasar pola kehadiran dari present_rekap, bandingkan K:

| K | CH Index |
|---|---------|
| 2 | 820 |
| 3 | **1420** |
| 4 | 1280 |
| 5 | 1100 |

K=3 memberi CH tertinggi → pilih K=3 (pegawai disiplin / sedang / perlu pembinaan).

```python
from sklearn.metrics import calinski_harabasz_score

# Evaluasi beberapa K
for k in range(2, 6):
    km = KMeans(n_clusters=k, n_init=10, random_state=42)
    labels = km.fit_predict(X_scaled)
    ch = calinski_harabasz_score(X_scaled, labels)
    print(f"K={k}, CH={ch:.0f}")
```

## Pitfalls
- Cenderung favor cluster sferis — kurang cocok untuk DBSCAN pada deteksi anomali spasial.
- Sensitif terhadap scaling fitur.
- Tidak ada threshold absolut "baik/buruk" — hanya relatif antar K.

## Kaitan
- → [Performance Clustering](03-performance-clustering.md)
- → [Davies-Bouldin Index](06-davies-bouldin.md)
- → [Silhouette Score](07-silhouette.md)
