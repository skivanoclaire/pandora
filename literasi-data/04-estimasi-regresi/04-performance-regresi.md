# Performance Regresi

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
Karena target regresi kontinu, metrik mengukur seberapa jauh prediksi dari nilai sebenarnya. Beberapa metrik utama: MSE, RMSE, MAE, MAPE, R-squared.

## Perbandingan Metrik

| Metrik | Satuan | Sensitif outlier | Interpretasi |
|--------|--------|------------------|--------------|
| MAE | = target | Rendah | Rata-rata kesalahan absolut |
| MSE | target-squared | Tinggi | Rata-rata kesalahan kuadrat |
| RMSE | = target | Tinggi | Akar MSE, satuan sama target |
| MAPE | persen | Sedang | Persentase kesalahan |
| R-squared | tanpa | - | Proporsi variansi dijelaskan |

## Memilih Metrik
- Outlier = data nyata → **RMSE/MSE** (menghukum error besar).
- Outlier = anomali yang tidak ingin dikejar → **MAE**.
- Butuh angka mudah dipahami pimpinan → **MAPE**.
- Butuh goodness of fit → **R-squared**.

## Studi Kasus PANDORA
Model memprediksi total menit keterlambatan harian per OPD dari 148 OPD:

- RMSE = 18 menit → prediksi meleset ~18 menit rata-rata.
- MAE = 12 menit → error absolut rata-rata.
- MAPE = 11% → kesalahan relatif terhadap aktual.
- R-squared = 0.78 → model menjelaskan 78% variansi.

```python
from sklearn.metrics import mean_squared_error, mean_absolute_error, r2_score
import numpy as np

rmse = np.sqrt(mean_squared_error(y_test, y_pred))
mae = mean_absolute_error(y_test, y_pred)
r2 = r2_score(y_test, y_pred)
mape = np.mean(np.abs((y_test - y_pred) / y_test)) * 100
```

## Pitfalls
- MAPE meledak saat target = 0 (OPD tanpa keterlambatan) — pakai WAPE.
- R-squared bisa tinggi walau model overfit; validasi dengan test set.
- RMSE tanpa konteks skala sulit dibaca — bandingkan dengan mean target.

## Kaitan
- → [RMSE](05-rmse.md), [MSE](06-mse.md), [MAE](07-mae.md), [MAPE](08-mape.md)
- → [Algoritma Estimasi](01-algoritma-estimasi.md)
