# K-Nearest Neighbour (KNN)

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
KNN adalah algoritma klasifikasi berbasis jarak: untuk memprediksi label data baru, cari K tetangga terdekat dari data training, lalu ambil mayoritas label mereka. Sederhana dan intuitif.

## Algoritma
1. Tentukan K (jumlah tetangga, misal 5).
2. Hitung jarak data baru ke semua data training (Euclidean, Manhattan, dll).
3. Ambil K tetangga terdekat.
4. Kelas mayoritas = prediksi.

**Rumus Jarak Euclidean:** $d(p,q) = \sqrt{\sum_{i=1}^{n}(p_i - q_i)^2}$

## Memilih K
- K kecil (1) → sensitif noise, overfit. K besar → underfit.
- Rule of thumb: K = sqrt(N), coba nilai ganjil.
- Cross-validation untuk K optimal.

## Studi Kasus PANDORA
Deteksi anomali check-in: titik baru dengan fitur (lat=3.2945, long=117.6310, velocity=450, jarak_geofence=2500m). Cari 5 tetangga terdekat di data historis present_rekap → 4 dari 5 berlabel "anomali" → prediksi: **anomali (fake GPS)**.

```python
from sklearn.neighbors import KNeighborsClassifier
from sklearn.preprocessing import StandardScaler

scaler = StandardScaler()
X_train_scaled = scaler.fit_transform(X_train[['lat_berangkat','long_berangkat','velocity','jarak_geofence']])
X_test_scaled = scaler.transform(X_test[['lat_berangkat','long_berangkat','velocity','jarak_geofence']])

knn = KNeighborsClassifier(n_neighbors=5)
knn.fit(X_train_scaled, y_train)
y_pred = knn.predict(X_test_scaled)
```

**Penting:** selalu scaling fitur sebelum KNN — jarak Euclidean sensitif skala.

## Pitfalls
- Tanpa scaling, fitur long_berangkat (ratusan) mendominasi fitur jarak_geofence (puluhan meter).
- Lambat saat prediksi pada 3,3 juta record — gunakan KD-Tree atau Ball-Tree.
- Curse of dimensionality: semua titik jadi "sama jauh" di dimensi tinggi.

## Kaitan
- → [Algoritma Klasifikasi](01-algoritma-klasifikasi.md)
- → [Data Transformation](../02-data-engineering/08-data-transformation.md)
