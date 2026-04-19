# Mean Square Error (MSE)

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
MSE adalah rata-rata kuadrat error. Loss function default banyak algoritma regresi karena diferensiabel dan menghukum error besar secara kuadratik.

## Rumus
$$\text{MSE} = \frac{1}{n} \sum_{i=1}^{n} (y_i - \hat{y}_i)^2$$

## Penjelasan
- Satuan = target-squared (kurang intuitif, misal "menit-kuadrat").
- Sangat sensitif outlier karena error dikuadratkan.
- Sering dipakai sebagai loss training; RMSE untuk pelaporan karena satuan sama.
- Hubungan: RMSE = sqrt(MSE).

## Studi Kasus PANDORA
Dari model prediksi menit keterlambatan per pegawai: MSE = 8 menit-kuadrat.

Interpretasi langsung sulit ("kuadrat menit"?), tetapi berguna sebagai loss saat training model deteksi keterlambatan:

```python
from sklearn.metrics import mean_squared_error

# Prediksi menit keterlambatan dari fitur present_rekap
mse = mean_squared_error(y_test, y_pred)
print(f"MSE: {mse:.2f} menit²")

# Pada gradient descent, gradien MSE bersih:
# dMSE/dy_pred = -2/n * (y - y_pred)
```

MSE menjadi pilihan default untuk neural network regresi (prediksi jumlah pegawai mkttw per hari) karena turunannya smooth.

## Pitfalls
- Satu pegawai dengan error besar (fake GPS = outlier) bisa mendominasi MSE total.
- Tidak cocok jika prioritas adalah error median — gunakan MAE.
- Satuan kuadrat membuat komunikasi ke stakeholder sulit.

## Kaitan
- → [RMSE](05-rmse.md), [MAE](07-mae.md)
- → [Performance Regresi](04-performance-regresi.md)
