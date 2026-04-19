# Isolation Forest

**Kategori:** Clustering / Anomaly Detection | **Level:** Menengah

## Ringkasan
Isolation Forest mendeteksi anomali dengan prinsip: **data anomali lebih mudah diisolasi**. Algoritma membuat pohon keputusan acak dan mengukur berapa langkah yang diperlukan untuk mengisolasi satu data point. Anomali butuh langkah lebih sedikit.

## Penjelasan
Bayangkan Anda punya segerombolan titik data. Titik yang berada di tengah kerumunan butuh banyak "potongan" (split) untuk dipisahkan dari yang lain. Tapi titik yang jauh dari kerumunan bisa dipisahkan hanya dengan satu atau dua potongan. Isolation Forest memanfaatkan sifat ini — semakin cepat sebuah titik terisolasi, semakin besar kemungkinan ia anomali.

Berbeda dengan metode lain yang memodelkan "apa itu normal", Isolation Forest langsung memodelkan "apa itu anomali". Ini membuatnya sangat efisien untuk dataset besar.

## Algoritma
1. Ambil sampel acak dari dataset.
2. Bangun pohon: pilih fitur acak, pilih nilai split acak, bagi data.
3. Ulangi split hingga setiap titik terisolasi (atau kedalaman maksimum).
4. Hitung **path length** rata-rata setiap titik di semua pohon.
5. Titik dengan path length pendek = **anomali** (mudah diisolasi).

## Anomaly Score
$$s(x) = 2^{-\frac{E[h(x)]}{c(n)}}$$

- $h(x)$ = rata-rata path length titik x di semua pohon
- $c(n)$ = rata-rata path length di BST dengan n data (normalisasi)
- Skor mendekati 1 → anomali, mendekati 0.5 → normal, mendekati 0 → sangat padat (pasti normal)

## Studi Kasus PANDORA
Isolation Forest digunakan PANDORA untuk mendeteksi pegawai dengan **kombinasi fitur tidak biasa** — bukan satu aturan yang dilanggar, tapi gabungan dari beberapa indikator.

```python
from sklearn.ensemble import IsolationForest

# Fitur per pegawai per hari
features = ['velocity_berangkat_pulang', 'jarak_dari_geofence_berangkat',
            'jarak_dari_geofence_pulang', 'deviasi_masuk_vs_jadwal_ekspektasi',
            'deviasi_waktu_masuk_vs_median_personal']

X = df_features[features].fillna(0).values

# contamination=0.05 artinya kita ekspektasi ~5% data adalah anomali
clf = IsolationForest(contamination=0.05, n_estimators=100, random_state=42)
df_features['is_anomaly'] = clf.fit_predict(X)  # -1 = anomali, 1 = normal
df_features['if_score'] = clf.decision_function(X)  # semakin negatif = semakin anomali

anomali = df_features[df_features['is_anomaly'] == -1]
print(f"Anomali terdeteksi: {len(anomali)}")  # ~250 per hari
```

Contoh temuan: Pegawai dengan jarak geofence 55km + deviasi masuk -35 menit + velocity 6 km/jam. Masing-masing fitur tidak langgar aturan, tapi kombinasinya sangat tidak biasa.

## Isolation Forest vs DBSCAN di PANDORA
| Aspek | Isolation Forest | DBSCAN |
|-------|-----------------|--------|
| Jenis deteksi | Multivariate (banyak fitur) | Spasial (lokasi) |
| Input | Fitur numerik (velocity, jarak, waktu) | Koordinat lat/long |
| Output | Outlier score per titik | Cluster + noise points |
| Cocok untuk | "Kombinasi fiturnya aneh" | "Lokasinya terisolasi" |

## Pitfalls
- Parameter `contamination` harus di-set manual — terlalu tinggi = banyak false positive.
- Tidak menjelaskan **mengapa** titik itu anomali (black box). PANDORA mengatasi ini dengan menampilkan fitur mana yang paling menyimpang.
- Sensitif terhadap fitur yang skalanya sangat berbeda — perlu StandardScaler.
- Jumlah pohon (`n_estimators`) 100-300 biasanya cukup.

## Kaitan
- → [DBSCAN](08-dbscan.md)
- → [Algoritma Clustering](01-algoritma-clustering.md)
- → [Performance Clustering](03-performance-clustering.md)
