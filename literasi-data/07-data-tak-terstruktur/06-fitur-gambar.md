# Fitur Gambar

**Kategori:** Data Tak Terstruktur | **Level:** Menengah

## Ringkasan
Fitur gambar adalah representasi numerik yang mendeskripsikan karakteristik gambar (warna, tekstur, bentuk) agar dapat diolah algoritma ML. Di era deep learning fitur dipelajari otomatis, tapi fitur hand-crafted masih berguna untuk dataset kecil.

## Penjelasan

### Fitur Warna
- **Histogram warna** — distribusi intensitas per channel.
- **Moment warna** — statistik (mean, SD, skewness) per channel.

### Fitur Tekstur
- **GLCM** — statistik pasangan piksel bertetangga.
- **LBP** — pola piksel lokal. **Gabor filters** — filter berarah.

### Fitur Bentuk
- **Edge detection** (Canny, Sobel). **Hu Moments** — invarian translasi/rotasi.

### Fitur dari Deep Network
- Aktivasi CNN pretrained (ResNet, VGG) sebagai feature vector.

## Studi Kasus PANDORA
Deteksi spoofing pada foto presensi SIKARA — membedakan wajah asli dari foto layar HP:

| Fitur | Wajah Asli | Foto Layar HP |
|-------|-----------|---------------|
| Histogram R | Distribusi lebar | Puncak sempit |
| GLCM entropy | Tinggi (pori kulit) | Rendah (layar halus) |
| Edge density | Sedang | Tinggi (bingkai HP) |
| LBP variance | Tinggi | Rendah |

Kombinasi fitur ini dimasukkan ke Random Forest → klasifier anti-spoofing ringan dan cepat.

```python
import cv2
import numpy as np
from skimage.feature import graycomatrix, graycoprops, local_binary_pattern

def extract_features(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    hist = cv2.calcHist([img], [0,1,2], None, [8,8,8], [0,256]*3).flatten()
    glcm = graycomatrix(gray, [1], [0, np.pi/4], levels=16, normed=True)
    return np.concatenate([hist, graycoprops(glcm, 'contrast').flatten()])
```

## Pitfalls
- Fitur hand-crafted sensitif pencahayaan/rotasi — normalisasi penting.
- Tidak ada satu set fitur universal; pilih sesuai masalah.
- Deep learning sering mengungguli hand-crafted jika data wajah pegawai banyak.

## Kaitan
- → [Histogram](07-histogram.md), [Moment Warna](08-moment-warna.md), [GLCM](09-glcm.md)
- → [Computer Vision](05-computer-vision.md)
