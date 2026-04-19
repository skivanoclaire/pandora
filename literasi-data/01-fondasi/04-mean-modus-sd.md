# Mean, Modus, dan Standar Deviasi

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Tiga statistik paling sering dilaporkan. Mean adalah rata-rata aritmetik, modus adalah nilai paling sering muncul, standar deviasi mengukur seberapa jauh data menyebar dari mean.

## Rumus

**Mean:** $\bar{x} = \frac{1}{n}\sum_{i=1}^{n} x_i$

**Modus:** nilai dengan frekuensi tertinggi.

**Standar Deviasi:** $\sigma = \sqrt{\frac{1}{n}\sum(x_i - \bar{x})^2}$

Untuk sampel, pembagi diganti $n-1$ (koreksi Bessel).

## Interpretasi
- **Mean tinggi + SD kecil** → data konsisten di sekitar rata-rata.
- **Aturan empiris (distribusi normal):** ±1 SD menangkap 68% data, ±2 SD 95%, ±3 SD 99.7%.

## Studi Kasus PANDORA
Menit keterlambatan 11 pegawai OPD Dinas Kominfo bulan Maret 2026:
`[0, 5, 2, 10, 0, 0, 8, 3, 0, 0, 120]`

- Mean = 13.5 menit — tertarik oleh outlier 120 (kemungkinan fake GPS atau lupa checkout).
- Median = 2 menit — lebih mewakili kondisi nyata.
- Modus = 0 — mayoritas pegawai berstatus tw (tepat waktu).
- SD = 34.7 — sangat lebar karena satu outlier.

```python
import numpy as np
menit_telat = df_kehadiran[df_kehadiran['opd'] == 'Dinas Kominfo']['menit_terlambat']
print(f"Mean: {np.mean(menit_telat):.1f}, Median: {np.median(menit_telat):.1f}, SD: {np.std(menit_telat):.1f}")
```

## Pitfalls
- Mean + outlier ekstrem = angka menyesatkan; selalu sertakan median.
- SD tidak bermakna jika distribusi sangat tidak simetris.
- Modus bisa tidak unik; hati-hati saat melaporkan.

## Kaitan
- → [Statistika Deskriptif](03-statistika-deskriptif.md)
- → [Data Quality](../02-data-engineering/04-data-quality.md)
