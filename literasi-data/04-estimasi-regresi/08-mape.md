# Mean Absolute Percentage Error (MAPE)

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
MAPE adalah rata-rata error absolut dinyatakan dalam persen dari nilai aktual. Mudah dikomunikasikan ke pimpinan karena bersatuan persen.

## Rumus
$$\text{MAPE} = \frac{100\%}{n} \sum_{i=1}^{n} \left| \frac{y_i - \hat{y}_i}{y_i} \right|$$

## Penjelasan
- Satuan persen, skala bebas — bisa dibandingkan antar OPD.
- Intuitif: "meleset 10% rata-rata dari nilai sebenarnya".

## Studi Kasus PANDORA
Prediksi total jam kerja bulanan per OPD dari present_rekap:

| OPD | Aktual (jam) | Prediksi (jam) | Abs(Error)/Aktual |
|-----|-------------|----------------|-------------------|
| Dinas Kominfo | 160 | 150 | 6.25% |
| BPKAD | 170 | 175 | 2.94% |
| Dinas Kesehatan | 155 | 160 | 3.23% |
| Inspektorat | 165 | 158 | 4.24% |

MAPE = (6.25+2.94+3.23+4.24)/4 = **4.17%**.

Interpretasi: model forecasting jam kerja meleset rata-rata 4.17% — sangat baik.

```python
import numpy as np
mape = np.mean(np.abs((y_test - y_pred) / y_test)) * 100
print(f"MAPE: {mape:.2f}%")
```

## Kriteria Umum MAPE

| MAPE | Interpretasi |
|------|--------------|
| <10% | Sangat akurat |
| 10-20% | Baik |
| 20-50% | Cukup |
| >50% | Tidak akurat |

## Pitfalls
- **Meledak saat y=0**: OPD yang total keterlambatannya 0 menit → division by zero. Pakai WAPE atau SMAPE.
- Asimetris: under-predict dan over-predict dihukum berbeda secara persentase.
- Tidak cocok untuk target yang bisa negatif atau nol.

## Kaitan
- → [MAE](07-mae.md), [RMSE](05-rmse.md)
- → [Performance Regresi](04-performance-regresi.md)
