# Histogram Warna

**Kategori:** Data Tak Terstruktur | **Level:** Dasar

## Ringkasan
Histogram warna adalah distribusi frekuensi intensitas piksel dalam gambar. Fitur gambar paling sederhana dan efektif untuk klasifikasi berbasis warna — termasuk deteksi lingkungan foto presensi.

## Penjelasan
Untuk gambar grayscale 8-bit (0-255), histogram adalah array 256-bin menghitung piksel per intensitas. Untuk RGB: tiga histogram terpisah (R, G, B) atau satu histogram 3D.

Langkah: (1) Konversi ke ruang warna (RGB, HSV). (2) Tentukan jumlah bin (8, 16, 64). (3) Hitung piksel per bin. (4) Normalisasi agar independen ukuran gambar.

## Manfaat
- Invarian terhadap rotasi dan translasi.
- Komputasi sangat cepat.
- Intuitif dan mudah divisualisasi.

## Studi Kasus PANDORA
Deteksi apakah foto presensi SIKARA diambil di kantor (indoor) atau di luar (outdoor):

- **Indoor kantor:** histogram condong ke nilai tengah (lampu neon hangat, pencahayaan stabil).
- **Outdoor:** histogram lebih lebar, puncak di area terang (sinar matahari).
- **Foto layar HP (spoofing):** histogram sempit dengan puncak tajam (backlight layar).

Feature vector: 48 dimensi (16 bin x 3 channel RGB).

```python
import cv2

img_checkin = cv2.imread('foto_presensi.jpg')
hist = cv2.calcHist([img_checkin], [0,1,2], None, [16,16,16], [0,256]*3)
hist = cv2.normalize(hist, hist).flatten()  # 4096 → normalisasi
```

Gabungkan histogram dari ribuan foto presensi pegawai → train Random Forest untuk klasifikasi indoor/outdoor/spoofing.

## Pitfalls
- Tidak menangkap informasi spasial ("di mana" warnanya).
- Sensitif terhadap pencahayaan — normalisasi atau pakai HSV.
- Dua gambar sangat berbeda bisa punya histogram identik.

## Kaitan
- → [Moment Warna](08-moment-warna.md), [GLCM](09-glcm.md)
- → [Fitur Gambar](06-fitur-gambar.md)
