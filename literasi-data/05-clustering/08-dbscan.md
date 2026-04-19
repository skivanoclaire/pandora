# DBSCAN (Density-Based Spatial Clustering)

**Kategori:** Clustering | **Level:** Menengah

## Ringkasan
DBSCAN mengelompokkan data berdasarkan **kepadatan** — titik yang berdekatan dan padat membentuk cluster, sementara titik terisolasi ditandai sebagai **noise**. Tidak perlu menentukan jumlah cluster di awal.

## Penjelasan
DBSCAN bekerja dengan dua parameter: **eps** (radius pencarian) dan **min_samples** (minimum titik dalam radius agar dianggap padat). Titik dengan >= min_samples tetangga dalam radius eps adalah *core point*. Titik bukan core tapi dalam radius core adalah *border point*. Sisanya adalah **noise point** — inilah yang paling berguna untuk deteksi anomali.

## Algoritma
1. Pilih titik acak yang belum dikunjungi.
2. Hitung tetangga dalam radius eps.
3. Jika tetangga >= min_samples → bentuk cluster baru, ekspansi ke tetangga.
4. Jika tetangga < min_samples → tandai sebagai noise.
5. Ulangi hingga semua titik dikunjungi.

## Studi Kasus PANDORA
DBSCAN digunakan PANDORA untuk clustering **lokasi check-in** pegawai. Titik-titik check-in yang berdekatan membentuk cluster (kantor OPD). Titik terisolasi (noise) = pegawai absen dari lokasi tidak biasa.

```python
from sklearn.cluster import DBSCAN

X = df_kehadiran[['lat_berangkat', 'long_berangkat']].dropna().values

# eps=0.01 derajat ≈ 1.1km, min_samples=5
db = DBSCAN(eps=0.01, min_samples=5).fit(X)

noise_mask = db.labels_ == -1
print(f"Cluster: {len(set(db.labels_)) - 1}")  # 433
print(f"Noise points: {noise_mask.sum()}")       # 635
```

Noise points (label = -1) masuk ke halaman Clustering PANDORA sebagai anomali spasial.

## DBSCAN vs K-Means
| Aspek | DBSCAN | K-Means |
|-------|--------|---------|
| Jumlah cluster | Otomatis | Harus ditentukan |
| Bentuk cluster | Bebas | Hanya bola/bulat |
| Deteksi outlier | Otomatis (noise) | Tidak ada |
| Kecepatan | Lambat (O(n²)) | Cepat |

## Pitfalls
- Sensitif terhadap eps dan min_samples — terlalu kecil = semua noise, terlalu besar = satu cluster.
- Tidak bagus untuk data dengan kepadatan sangat bervariasi.
- Untuk data geospasial, gunakan `haversine` metric agar jarak akurat.

## Kaitan
- → [Algoritma Clustering](01-algoritma-clustering.md)
- → [Silhouette Score](07-silhouette.md)
- → [Isolation Forest](09-isolation-forest.md)
