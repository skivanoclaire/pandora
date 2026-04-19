# Confusion Matrix

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Confusion matrix adalah tabel yang membandingkan label prediksi dengan label sebenarnya. Semua metrik klasifikasi (akurasi, presisi, recall, F1) diturunkan dari 4 angka di tabel ini.

## Struktur (Biner)

|                  | Prediksi: Positif | Prediksi: Negatif |
|------------------|-------------------|-------------------|
| **Aktual: Positif** | TP (True Positive) | FN (False Negative) |
| **Aktual: Negatif** | FP (False Positive) | TN (True Negative) |

- **TP** — anomali terdeteksi sebagai anomali (benar).
- **TN** — normal terdeteksi sebagai normal (benar).
- **FP** — normal dikira anomali (alarm palsu, Type I).
- **FN** — anomali lolos sebagai normal (bahaya, Type II).

## Studi Kasus PANDORA
Model deteksi fake GPS diuji pada 1.000 record check-in:

|                  | Prediksi: Fake GPS | Prediksi: Normal |
|------------------|--------------------|------------------|
| **Aktual: Fake GPS** | TP = 25 | FN = 5 |
| **Aktual: Normal** | FP = 15 | TN = 955 |

- 25 check-in fake GPS berhasil terdeteksi.
- 5 fake GPS lolos tidak terdeteksi (bahaya — pegawai titip absen).
- 15 check-in normal dikira fake GPS (perlu investigasi manual).
- 955 check-in normal aman terdeteksi.

```python
from sklearn.metrics import confusion_matrix, ConfusionMatrixDisplay
cm = confusion_matrix(y_test, y_pred, labels=['anomali', 'normal'])
ConfusionMatrixDisplay(cm, display_labels=['Fake GPS', 'Normal']).plot()
```

## Pitfalls
- Urutan baris/kolom tergantung library; selalu baca label dengan teliti.
- Pada kelas sangat imbalanced, normalisasi per baris agar visual tidak menyesatkan.
- Untuk multikelas (tw/mkttw/tk/ta), matriks N x N — diagonal = benar.

## Kaitan
- → [Akurasi](09-akurasi.md), [Presisi](10-presisi.md), [Recall](11-recall.md), [F1-Score](12-f1-score.md)
- → [Performance Klasifikasi](07-performance-klasifikasi.md)
