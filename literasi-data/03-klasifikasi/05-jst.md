# Jaringan Syaraf Tiruan (JST)

**Kategori:** Klasifikasi | **Level:** Menengah

## Ringkasan
Jaringan Syaraf Tiruan (Artificial Neural Network) adalah model terinspirasi neuron biologis. Tersusun dari lapisan neuron yang saling terhubung dengan bobot, dilatih melalui backpropagation untuk meminimalkan error.

## Penjelasan
Setiap neuron: $z = \sum w_i x_i + b$, lalu dipasang fungsi aktivasi (sigmoid, ReLU, tanh). Pelatihan melalui backpropagation: forward pass → hitung loss → backward pass (gradien) → update bobot.

## Fungsi Aktivasi
- **Sigmoid:** $\sigma(z) = 1/(1+e^{-z})$ — output 0-1, cocok untuk biner.
- **ReLU:** $\max(0, z)$ — paling populer di hidden layer.
- **Softmax** — output layer multikelas, menghasilkan probabilitas per kelas.

## Studi Kasus PANDORA
JST 2 hidden layer untuk klasifikasi status kehadiran pegawai menjadi 3 kelas (Disiplin / Sedang / Perlu Pembinaan) berdasar 10 fitur dari present_rekap:

```python
from sklearn.neural_network import MLPClassifier

fitur = ['rata_telat_30hari', 'jumlah_tw', 'jumlah_mkttw', 'jumlah_tk',
         'jumlah_izin', 'jumlah_sakit', 'velocity_mean', 'jarak_geofence_mean',
         'golongan_encoded', 'hari_kerja_efektif']

mlp = MLPClassifier(hidden_layer_sizes=(32, 16), activation='relu', max_iter=500)
mlp.fit(X_train[fitur], y_train)
y_pred = mlp.predict(X_test[fitur])
```

Input: 10 fitur numerik (sudah scaling). Hidden: [32, 16] dengan ReLU. Output: 3 neuron softmax.

## Pitfalls
- Butuh data banyak; overfit jika hanya sedikit pegawai per kelas.
- Sulit diinterpretasi (black box) — sulit menjelaskan ke inspektorat.
- Sensitif terhadap skala fitur dan inisialisasi bobot — selalu standardize.
- Learning rate terlalu besar → tidak konvergen; terlalu kecil → lambat.

## Kaitan
- → [Deep Learning](06-deep-learning.md)
- → [Overfitting & Underfitting](13-overfitting-underfitting.md)
