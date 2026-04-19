# Mean Absolute Error (MAE)

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
MAE adalah rata-rata nilai absolut error. Memberi bobot sama pada setiap error (tidak dikuadratkan), sehingga lebih tahan terhadap outlier dibanding MSE/RMSE.

## Rumus
$$\text{MAE} = \frac{1}{n} \sum_{i=1}^{n} |y_i - \hat{y}_i|$$

## Penjelasan
- Satuan sama dengan target (menit).
- Kurang sensitif outlier daripada RMSE.
- Interpretasi mudah: "rata-rata model meleset X menit".

## Studi Kasus PANDORA
Prediksi menit keterlambatan 5 pegawai dari present_rekap:

| Aktual | Prediksi | Abs(Error) |
|--------|----------|------------|
| 5 | 4 | 1 |
| 10 | 12 | 2 |
| 0 | 3 | 3 |
| 20 | 15 | 5 |
| 2 | 1 | 1 |

MAE = (1+2+3+5+1)/5 = **2.4 menit**.

Bandingkan RMSE = 2.83 menit. MAE lebih kecil karena outlier (error 5 menit) tidak dikuadratkan.

```python
from sklearn.metrics import mean_absolute_error
mae = mean_absolute_error(y_test, y_pred)
print(f"Rata-rata prediksi meleset {mae:.1f} menit")
```

## Kapan Pakai MAE
- Outlier dianggap anomali (fake GPS) yang tidak ingin dikejar.
- Stakeholder minta angka mudah: "rata-rata meleset 2.4 menit per pegawai".
- Semua error sama pentingnya, tidak ada yang lebih mahal.

## Pitfalls
- MAE tidak diferensiabel di 0 — bisa menyulitkan beberapa optimisasi.
- Jika error besar justru penting (keterlambatan massal), gunakan RMSE.
- Membandingkan MAE antar OPD dengan skala berbeda perlu normalisasi.

## Kaitan
- → [RMSE](05-rmse.md), [MSE](06-mse.md), [MAPE](08-mape.md)
- → [Performance Regresi](04-performance-regresi.md)
