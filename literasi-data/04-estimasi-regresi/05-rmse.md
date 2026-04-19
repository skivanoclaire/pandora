# Root Mean Square Error (RMSE)

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
RMSE adalah akar kuadrat dari rata-rata error kuadrat. Satuan sama dengan target sehingga mudah diinterpretasi. Menghukum error besar lebih berat karena dikuadratkan.

## Rumus
$$\text{RMSE} = \sqrt{\frac{1}{n} \sum_{i=1}^{n} (y_i - \hat{y}_i)^2}$$

## Karakteristik
- Satuan sama dengan target (menit, jam, orang).
- Selalu >= 0; nol berarti prediksi sempurna.
- Lebih sensitif outlier daripada MAE.

## Studi Kasus PANDORA
Prediksi menit keterlambatan 5 pegawai dari present_rekap:

| Aktual | Prediksi | Error | Error-squared |
|--------|----------|-------|---------------|
| 5 | 4 | 1 | 1 |
| 10 | 12 | -2 | 4 |
| 0 | 3 | -3 | 9 |
| 20 | 15 | 5 | 25 |
| 2 | 1 | 1 | 1 |

MSE = (1+4+9+25+1)/5 = 8. RMSE = sqrt(8) = **2.83 menit**.

Interpretasi: prediksi menit keterlambatan meleset rata-rata ~2.83 menit.

```python
from sklearn.metrics import mean_squared_error
import numpy as np
rmse = np.sqrt(mean_squared_error(y_test, y_pred))
```

## Pitfalls
- Beda skala target = RMSE tidak bisa dibandingkan langsung antar OPD.
- Satu pegawai dengan error besar (misal fake GPS 120 menit) bisa mendominasi.
- Normalisasi dengan NRMSE jika ingin membandingkan antar variabel.

## Kaitan
- → [MSE](06-mse.md), [MAE](07-mae.md), [MAPE](08-mape.md)
- → [Performance Regresi](04-performance-regresi.md)
