# Algoritma Klasifikasi

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Algoritma klasifikasi adalah metode supervised learning yang mempelajari pemetaan dari fitur input ke label kelas diskrit. Setelah dilatih dengan data berlabel, model dapat memprediksi label untuk data baru.

## Penjelasan
Alur umum: data berlabel (X, y) → split train/test → training model → prediksi pada test → evaluasi metrik → deploy. Jenis klasifikasi: **biner** (anomali/normal), **multikelas** (tw/mkttw/tk/ta), **multilabel** (satu check-in bisa punya beberapa flag sekaligus).

## Algoritma Populer

| Algoritma | Kekuatan | Kelemahan |
|-----------|----------|-----------|
| KNN | Sederhana, non-parametric | Lambat pada dataset besar |
| Decision Tree (C4.5) | Interpretable, bisa dijelaskan | Overfit, tidak stabil |
| Naive Bayes | Cepat, baik untuk teks | Asumsi independensi |
| JST / Neural Network | Kuat untuk pola kompleks | Butuh banyak data |
| Random Forest | Akurat, tahan noise | Sulit diinterpretasi |
| Gradient Boosting | Sering terbaik di kompetisi | Tuning kompleks |

## Studi Kasus PANDORA
**Target:** Klasifikasi check-in sebagai "Anomali" (fake GPS) atau "Normal" berdasar data present_rekap 6.475 pegawai.

**Fitur:** lat_berangkat, long_berangkat, jam_masuk, velocity, jarak_dari_geofence, IMEI_match.

```python
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import cross_val_score

fitur = ['lat_berangkat', 'long_berangkat', 'velocity', 'jarak_geofence', 'imei_match']
X = df_kehadiran[fitur]
y = df_kehadiran['is_anomaly']

model = RandomForestClassifier(n_estimators=100, random_state=42)
scores = cross_val_score(model, X, y, cv=5, scoring='f1')
print(f"F1 rata-rata: {scores.mean():.3f}")
```

## Pitfalls
- Memilih akurasi sebagai metrik pada data imbalanced (97% normal) = menyesatkan.
- Selalu bandingkan dengan baseline (prediksi kelas mayoritas).
- Pilih metrik SEBELUM training, bukan setelah lihat hasil.

## Kaitan
- → [Performance Klasifikasi](07-performance-klasifikasi.md)
- → [KNN](02-knn.md), [C4.5](03-c45.md), [Naive Bayes](04-naive-bayes.md)
