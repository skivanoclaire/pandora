# Regresi Linier Berganda

**Kategori:** Estimasi & Regresi | **Level:** Menengah

## Ringkasan
Regresi linier berganda mengekstensi regresi sederhana dengan lebih dari satu variabel independen. Output tetap kontinu, tapi dipengaruhi banyak fitur sekaligus.

## Rumus
$$y = \beta_0 + \beta_1 x_1 + \beta_2 x_2 + \ldots + \beta_n x_n + \epsilon$$

Setiap $\beta_i$ menunjukkan kontribusi $x_i$ setelah mengontrol fitur lain (ceteris paribus).

## Evaluasi Model
- **R-squared** — proporsi variansi y yang dijelaskan model.
- **Adjusted R-squared** — R-squared dikoreksi jumlah fitur.
- **VIF** (Variance Inflation Factor) — deteksi multikolinearitas; VIF > 10 = masalah.

## Studi Kasus PANDORA
Memprediksi total menit keterlambatan harian per OPD dari 148 OPD:

$$y = 15 + 0.8 \cdot \text{jumlah\_pegawai} + 12.3 \cdot \text{setelah\_libur} - 1.5 \cdot \text{curah\_hujan} + 5.2 \cdot \text{hari\_senin}$$

Interpretasi:
- Setiap tambah 1 pegawai, kumulatif keterlambatan naik ~0.8 menit.
- Hari setelah libur menambah 12.3 menit total keterlambatan OPD.
- Curah hujan tinggi justru mengurangi keterlambatan? (kemungkinan: banyak yang izin sehingga yang datang justru disiplin).

```python
from sklearn.linear_model import LinearRegression
from statsmodels.stats.outliers_influence import variance_inflation_factor

lr = LinearRegression()
lr.fit(X_train[['jumlah_pegawai','setelah_libur','curah_hujan','hari_senin']], y_train)
vif = [variance_inflation_factor(X_train.values, i) for i in range(X_train.shape[1])]
```

## Pitfalls
- Multikolinearitas membuat koefisien tidak stabil dan t-test menipu.
- Fitur tidak relevan menurunkan adjusted R-squared.
- Masih terikat asumsi linier — gunakan polynomial/interaction term jika perlu.

## Kaitan
- → [Regresi Linier](02-regresi-linier.md)
- → [Feature Engineering](../02-data-engineering/11-feature-engineering.md)
