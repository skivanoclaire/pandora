# K-Means Clustering

**Kategori:** Clustering | **Level:** Dasar

## Ringkasan
K-Means adalah algoritma clustering paling populer. Membagi data ke K cluster dengan meminimalkan jarak titik ke centroid cluster (within-cluster sum of squares).

## Algoritma (Lloyd's)
1. Pilih K centroid awal (acak atau k-means++).
2. **Assign:** tiap titik ditempatkan di cluster centroid terdekat.
3. **Update:** centroid dihitung ulang sebagai mean titik dalam cluster.
4. Ulangi 2-3 hingga konvergen.

## Fungsi Objektif
$$J = \sum_{i=1}^{K} \sum_{x \in C_i} \|x - \mu_i\|^2$$

## Memilih K
- **Elbow method:** plot WCSS vs K, cari "siku".
- **Silhouette score:** maksimalkan rata-rata silhouette.
- **Domain knowledge:** pegawai bisa dibagi 3 kategori (disiplin/sedang/buruk).

## Studi Kasus PANDORA
Cluster 6.475 pegawai berdasar 4 fitur dari present_rekap:

```python
from sklearn.cluster import KMeans
from sklearn.preprocessing import StandardScaler

fitur = ['rata_telat_30hari', 'jumlah_tk', 'jumlah_izin', 'rata_durasi_kerja']
X_scaled = StandardScaler().fit_transform(df_pegawai[fitur])
km = KMeans(n_clusters=3, n_init=10, random_state=42)
df_pegawai['cluster'] = km.fit_predict(X_scaled)
```

Profiling hasil:
- **Cluster 0 (n=4.800):** pegawai disiplin standar, rata_telat < 5 menit.
- **Cluster 1 (n=1.400):** pegawai sering izin/sakit tapi datang tepat waktu.
- **Cluster 2 (n=275):** kandidat tk/alpa kronis → prioritas pembinaan.

Juga bisa dipakai DBSCAN pada koordinat lat/long_berangkat untuk mendeteksi titik check-in anomali yang tidak membentuk cluster normal di area kantor.

## Pitfalls
- Centroid awal acak → hasil berbeda tiap run. Gunakan `n_init > 1` atau `k-means++`.
- Sensitif outlier (centroid tertarik).
- Hanya cocok cluster sferis; gunakan DBSCAN untuk cluster bentuk bebas.
- K harus ditentukan sebelumnya.

## Kaitan
- → [Algoritma Clustering](01-algoritma-clustering.md)
- → [Silhouette Score](07-silhouette.md)
- → [Davies-Bouldin Index](06-davies-bouldin.md)
