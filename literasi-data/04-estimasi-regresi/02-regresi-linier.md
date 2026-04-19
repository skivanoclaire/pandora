# Regresi Linier

**Kategori:** Estimasi & Regresi | **Level:** Dasar

## Ringkasan
Regresi linier sederhana memodelkan hubungan antara satu variabel independen (X) dan satu variabel dependen (y) dengan garis lurus. Fondasi yang harus dipahami sebelum model lebih kompleks.

## Rumus
$$y = \beta_0 + \beta_1 x + \epsilon$$

- $\beta_0$ = intersep. $\beta_1$ = slope (perubahan y per unit x). $\epsilon$ = error.

## Estimasi Parameter (OLS)
Ordinary Least Squares meminimalkan jumlah kuadrat error:
$$\beta_1 = \frac{\sum (x_i - \bar{x})(y_i - \bar{y})}{\sum (x_i - \bar{x})^2}, \quad \beta_0 = \bar{y} - \beta_1 \bar{x}$$

## Asumsi Klasik (LINE)
- **L**inearity, **I**ndependence error, **N**ormality error, **E**qual variance (homoskedastisitas).

## Studi Kasus PANDORA
Hubungan jumlah hari kerja efektif dalam bulan (X) dengan total jam kerja kumulatif per pegawai (y), dari present_rekap:

$$y = 0.5 + 8.1 \cdot x$$

Interpretasi: tiap tambah 1 hari kerja, akumulasi jam kerja bertambah ~8.1 jam (sesuai present_group 8 jam/hari).

```python
from sklearn.linear_model import LinearRegression

X = df_kehadiran[['hari_kerja_efektif']]  # jumlah hari kerja per bulan
y = df_kehadiran['total_jam_kerja']       # akumulasi jam kerja
lr = LinearRegression()
lr.fit(X, y)
print(f"Slope: {lr.coef_[0]:.2f}, Intercept: {lr.intercept_:.2f}")
```

## Pitfalls
- Korelasi bukan kausalitas. Slope signifikan bukan berarti X menyebabkan y.
- Outlier ekstrem menarik garis secara dramatis.
- Jika hubungan tidak linier, regresi linier salah model — plot data dulu.

## Kaitan
- → [Regresi Linier Berganda](03-regresi-linier-berganda.md)
- → [Performance Regresi](04-performance-regresi.md)
