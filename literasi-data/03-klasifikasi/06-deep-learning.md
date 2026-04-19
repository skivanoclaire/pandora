# Deep Learning

**Kategori:** Klasifikasi | **Level:** Lanjut

## Ringkasan
Deep Learning menggunakan jaringan syaraf dengan banyak lapisan tersembunyi. Dengan kapasitas representasi besar, deep learning mendominasi tugas pada citra, suara, dan teks.

## Penjelasan
Setiap lapisan mempelajari abstraksi lebih tinggi. Lapisan awal menangkap pola sederhana (edge, frekuensi), lapisan dalam menangkap pola kompleks (wajah, struktur kalimat). Membutuhkan GPU dan data besar, atau transfer learning dari model pretrained.

## Arsitektur Populer

| Arsitektur | Domain | Contoh di PANDORA |
|------------|--------|-------------------|
| CNN | Citra | Face recognition SIKARA |
| LSTM/GRU | Time series | Prediksi pola kehadiran 7 hari |
| Transformer | Teks | Klasifikasi keterangan izin/cuti |
| Autoencoder | Anomaly detection | Deteksi pola check-in tidak wajar |

## Studi Kasus PANDORA
- **CNN** untuk face recognition SIKARA di mesin presensi — verifikasi identitas pegawai saat check-in, menggantikan fingerprint.
- **LSTM** untuk memprediksi jumlah pegawai yang akan berstatus mkttw per OPD dalam 7 hari ke depan.
- **Autoencoder** untuk anomaly detection: latih model dengan pola check-in normal, lalu flagging record yang reconstruction error-nya tinggi sebagai potensi fake GPS.

```python
from tensorflow.keras.models import Sequential
from tensorflow.keras.layers import Dense

# Autoencoder untuk deteksi anomali check-in
autoencoder = Sequential([
    Dense(32, activation='relu', input_shape=(6,)),  # lat, long, velocity, jarak, jam, imei
    Dense(8, activation='relu'),   # bottleneck
    Dense(32, activation='relu'),
    Dense(6, activation='sigmoid')
])
autoencoder.compile(optimizer='adam', loss='mse')
autoencoder.fit(X_normal, X_normal, epochs=50, batch_size=256)
```

## Pitfalls
- Overengineering: deep learning untuk masalah kecil = pemborosan. Coba klasik dulu.
- Butuh data dan compute besar — 6.475 pegawai mungkin cukup untuk tabular, kurang untuk vision tanpa augmentasi.
- Sulit dijelaskan ke non-teknis (black box).
- Privacy concern pada data wajah — patuhi UU PDP.

## Kaitan
- → [JST](05-jst.md)
- → [Computer Vision](../07-data-tak-terstruktur/05-computer-vision.md)
- → [Data Augmentation](../02-data-engineering/07-data-augmentation.md)
