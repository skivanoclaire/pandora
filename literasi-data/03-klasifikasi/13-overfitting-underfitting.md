# Overfitting & Underfitting

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Dua masalah utama dalam machine learning. Underfitting = model terlalu sederhana, gagal menangkap pola. Overfitting = model terlalu kompleks, menghafal data training termasuk noisenya.

## Penjelasan

| Kondisi | Training Error | Test Error | Analogi |
|---------|---------------|------------|---------|
| Underfit | Tinggi | Tinggi | Pegawai baru yang belum paham SOP |
| Good fit | Rendah | Rendah | Pegawai berpengalaman yang adaptif |
| Overfit | Sangat rendah | Tinggi | Pegawai yang hafal jadwal lama tapi gagal di jadwal baru |

## Strategi Mengatasi

### Overfitting
- Tambah data training (lebih banyak record present_rekap).
- Regularisasi (L1/L2, dropout pada JST).
- Kurangi kompleksitas model (max_depth pada decision tree).
- Early stopping dan cross-validation.

### Underfitting
- Tambah fitur (feature engineering: velocity, jarak_geofence).
- Gunakan model lebih kompleks (dari Naive Bayes ke Random Forest).
- Kurangi regularisasi. Training lebih lama.

## Studi Kasus PANDORA
Model JST deteksi fake GPS dengan 5 hidden layer pada data 6.475 pegawai:
- F1 train: 99%, F1 test: 55% → **overfit**.
- Solusi: kurangi ke 2 layer, tambah dropout 0.3, pakai early stopping.

Setelah perbaikan:
- F1 train: 82%, F1 test: 78% → **good fit**.

```python
from sklearn.neural_network import MLPClassifier
from sklearn.model_selection import cross_val_score

# Overfit: terlalu kompleks
mlp_overfit = MLPClassifier(hidden_layer_sizes=(128,64,32,16,8), max_iter=1000)

# Good fit: lebih sederhana
mlp_good = MLPClassifier(hidden_layer_sizes=(32,16), max_iter=500, early_stopping=True)
scores = cross_val_score(mlp_good, X_train, y_train, cv=5, scoring='f1')
```

## Pitfalls
- Overfit sering tidak terdeteksi jika tidak ada test set terpisah.
- Cross-validation membantu, tapi pada time series harus chronological split.
- Model yang overfit pada pola lama bisa gagal saat kebijakan jadwal berubah (present_group baru).

## Kaitan
- → [JST](05-jst.md), [Deep Learning](06-deep-learning.md)
- → [Performance Klasifikasi](07-performance-klasifikasi.md)
