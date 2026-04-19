# Computer Vision

**Kategori:** Data Tak Terstruktur | **Level:** Menengah

## Ringkasan
Computer Vision (CV) membuat komputer "melihat" dan memahami gambar/video. Dalam konteks PANDORA dan SIKARA, CV diterapkan untuk verifikasi wajah pegawai saat presensi dan deteksi spoofing.

## Penjelasan
Gambar digital = matriks piksel. Grayscale: 1 channel (0-255). RGB: 3 channel. Pendekatan bisa klasik (fitur hand-crafted + ML) atau deep learning (CNN belajar fitur otomatis).

## Tugas Utama CV

| Tugas | Contoh di PANDORA/SIKARA |
|-------|--------------------------|
| Face Recognition | Verifikasi identitas pegawai saat check-in |
| Anti-Spoofing | Deteksi foto layar HP vs wajah asli |
| OCR | Ekstrak teks dari scan surat izin |
| Object Detection | Deteksi masker pada foto presensi |

## Studi Kasus PANDORA
**Face recognition SIKARA** untuk presensi 6.475 pegawai:
- Enrollment: setiap pegawai difoto beberapa kali dari berbagai sudut.
- Verifikasi: saat check-in, foto selfie dicocokkan dengan database.
- Anti-spoofing: model CNN mendeteksi apakah foto dari layar HP (spoofing) atau wajah asli.

**Deteksi anomali foto presensi:** pegawai yang check-in di dua lokasi berbeda dalam waktu singkat bisa dideteksi dari metadata foto (lat/long) + face match.

```python
import cv2
# Deteksi wajah pada foto presensi
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
gray = cv2.cvtColor(img_checkin, cv2.COLOR_BGR2GRAY)
faces = face_cascade.detectMultiScale(gray, 1.3, 5)
# faces kosong → foto tidak valid, perlu investigasi
```

## Pitfalls
- Privacy — data wajah sangat sensitif; patuhi UU PDP.
- Bias — model bisa berperforma buruk pada subgroup tertentu.
- Pencahayaan kantor pagi/sore sangat berbeda — perlu data augmentation.
- Butuh banyak data berlabel; gunakan transfer learning (pretrained model).

## Kaitan
- → [Fitur Gambar](06-fitur-gambar.md), [Histogram](07-histogram.md), [Moment Warna](08-moment-warna.md), [GLCM](09-glcm.md)
- → [Deep Learning](../03-klasifikasi/06-deep-learning.md)
