# GLCM (Gray-Level Co-occurrence Matrix)

**Kategori:** Data Tak Terstruktur | **Level:** Lanjut

## Ringkasan
GLCM mendeskripsikan frekuensi pasangan piksel bertetangga dengan intensitas tertentu. Dari GLCM diturunkan fitur tekstur (Haralick features) yang berguna untuk klasifikasi berbasis pola permukaan — termasuk deteksi spoofing presensi.

## Penjelasan
GLCM ukuran G x G di mana entri (i,j) = berapa kali piksel bernilai i berpasangan dengan piksel bernilai j pada offset tertentu. Parameter: jarak d (umumnya 1), sudut theta (0, 45, 90, 135 derajat).

## Fitur Haralick Utama

- **Contrast** — variasi intensitas lokal: $\sum (i-j)^2 \cdot P(i,j)$
- **Energy** — keseragaman tekstur: $\sum P(i,j)^2$
- **Homogeneity** — kedekatan elemen ke diagonal: $\sum \frac{P(i,j)}{1 + (i-j)^2}$
- **Entropy** — keacakan tekstur.
- **Correlation** — hubungan linier antar piksel.

## Studi Kasus PANDORA
Deteksi spoofing foto presensi SIKARA — membedakan wajah asli dari foto layar HP:

- **Kulit asli:** tekstur mikro (pori, rambut halus) → entropy tinggi, homogeneity rendah.
- **Foto layar HP:** permukaan halus tanpa tekstur mikro → homogeneity tinggi, entropy rendah.

Ekstraksi GLCM (4 sudut x 5 fitur) = 20 fitur untuk klasifier anti-spoofing.

```python
from skimage.feature import graycomatrix, graycoprops
import numpy as np

gray_checkin = cv2.cvtColor(img_checkin, cv2.COLOR_BGR2GRAY)
# Quantize ke 16 level untuk efisiensi
gray_q = (gray_checkin // 16).astype(np.uint8)
glcm = graycomatrix(gray_q, distances=[1],
                    angles=[0, np.pi/4, np.pi/2, 3*np.pi/4],
                    levels=16, symmetric=True, normed=True)
contrast = graycoprops(glcm, 'contrast').flatten()
homogeneity = graycoprops(glcm, 'homogeneity').flatten()
entropy_vals = graycoprops(glcm, 'energy').flatten()
```

## Pitfalls
- Gambar harus grayscale & didiskritisasi levelnya (quantization).
- Ukuran G besar → matriks sparse, komputasi berat.
- Sensitif terhadap rotasi; rata-ratakan banyak sudut untuk mitigasi.
- Pencahayaan berbeda (pagi/sore) mempengaruhi GLCM — normalisasi dulu.

## Kaitan
- → [Histogram](07-histogram.md), [Moment Warna](08-moment-warna.md)
- → [Fitur Gambar](06-fitur-gambar.md)
- → [Computer Vision](05-computer-vision.md)
