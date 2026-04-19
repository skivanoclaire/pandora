# Algoritma Estimasi

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
Algoritma estimasi adalah supervised learning yang outputnya nilai kontinu (regresi), bukan label kategori. Tujuan: memetakan fitur ke angka real — misalnya memprediksi jumlah menit keterlambatan.

## Perbandingan dengan Klasifikasi

| Aspek | Klasifikasi | Estimasi/Regresi |
|-------|-------------|------------------|
| Output | Label diskrit (anomali/normal) | Nilai kontinu (menit keterlambatan) |
| Loss umum | Cross-entropy | MSE/MAE |
| Metrik | F1-Score, PR-AUC | RMSE, MAE, MAPE |

## Algoritma Populer
- **Linear Regression** — asumsi hubungan linier, paling sederhana.
- **Ridge / Lasso** — regresi dengan regularisasi.
- **Random Forest Regressor** — ensemble, tahan noise.
- **Gradient Boosting (XGBoost, LightGBM)** — sering terbaik di kompetisi.
- **Neural Network Regressor** — untuk pola sangat kompleks.

## Studi Kasus PANDORA
Memprediksi total menit keterlambatan harian per OPD dari 148 OPD. Input: jumlah_pegawai_aktif, hari_kerja (Senin-Jumat), setelah_libur, curah_hujan. Output: total_menit_terlambat (kontinu).

```python
from sklearn.ensemble import RandomForestRegressor

fitur = ['jumlah_pegawai', 'hari_minggu', 'setelah_libur', 'curah_hujan']
model = RandomForestRegressor(n_estimators=200, random_state=42)
model.fit(X_train[fitur], y_train)  # y = total menit keterlambatan per OPD
y_pred = model.predict(X_test[fitur])
```

## Pitfalls
- Memaksa target kontinu menjadi kelas (lalu pakai klasifikasi) kehilangan informasi.
- Asumsi linieritas seringnya salah; selalu plot residu.
- Prediksi negatif (menit < 0) tidak bermakna — clip ke 0.

## Kaitan
- → [Regresi Linier](02-regresi-linier.md)
- → [Performance Regresi](04-performance-regresi.md)
