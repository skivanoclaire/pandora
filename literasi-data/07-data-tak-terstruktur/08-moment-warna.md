# Moment Warna

**Kategori:** Data Tak Terstruktur | **Level:** Menengah

## Ringkasan
Moment warna adalah ukuran statistik (mean, standar deviasi, skewness) dari distribusi piksel per channel warna. Cara kompak mendeskripsikan warna gambar dalam 9 angka saja (3 statistik x 3 channel RGB).

## Rumus

**Mean:** $M_i = \frac{1}{N} \sum_{j=1}^{N} p_{ij}$

**Standar Deviasi:** $\sigma_i = \sqrt{\frac{1}{N} \sum (p_{ij} - M_i)^2}$

**Skewness:** $s_i = \sqrt[3]{\frac{1}{N} \sum (p_{ij} - M_i)^3}$

Untuk RGB → 9 fitur total (3 moment x 3 channel).

## Studi Kasus PANDORA
Klasifikasi cepat foto presensi SIKARA: outdoor / indoor / spoofing (foto layar):

| Kategori | Mean R | SD R | Skew R |
|----------|--------|------|--------|
| Outdoor cerah | 180 | 45 | -0.8 |
| Indoor kantor | 120 | 30 | 0.2 |
| Foto layar HP | 95 | 25 | 0.6 |

9 fitur moment warna sebagai input ke KNN atau Random Forest → klasifier ringan & cepat untuk pre-screening sebelum face recognition.

```python
import cv2
import numpy as np

def color_moments(img_checkin):
    feats = []
    for ch in cv2.split(img_checkin):  # B, G, R
        feats.extend([
            np.mean(ch),
            np.std(ch),
            np.cbrt(np.mean((ch - np.mean(ch))**3))
        ])
    return np.array(feats)  # 9 fitur

# Ekstrak dari semua foto presensi
moments = np.array([color_moments(cv2.imread(f)) for f in foto_presensi])
```

## Pitfalls
- Hanya menangkap statistik global → kehilangan detail spasial.
- Skewness peka outlier piksel (sangat terang/gelap).
- Dua gambar dengan distribusi warna sama tapi konten beda dianggap sama.
- Lebih ringkas dari histogram tapi kurang diskriminatif.

## Kaitan
- → [Histogram](07-histogram.md), [GLCM](09-glcm.md)
- → [Fitur Gambar](06-fitur-gambar.md)
