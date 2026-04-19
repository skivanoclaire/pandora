# Akurasi (Accuracy)

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Akurasi adalah proporsi prediksi yang benar dari total prediksi. Metrik paling intuitif, tetapi sering menyesatkan pada data imbalanced seperti deteksi anomali presensi.

## Rumus
$$\text{Accuracy} = \frac{TP + TN}{TP + TN + FP + FN}$$

## Studi Kasus PANDORA
Dari confusion matrix deteksi fake GPS (1.000 record):
- TP=25, TN=955, FP=15, FN=5.
- Akurasi = (25 + 955) / 1000 = **98%**.

Kedengaran bagus. **Tapi:** baseline yang selalu menjawab "Normal" memberi akurasi 970/1000 = 97%. Model kita hanya 1% lebih baik dari menebak konstan!

Ini adalah **paradoks akurasi** — pada data imbalanced (97% normal, 3% anomali), akurasi tinggi belum tentu model berguna.

## Kapan Akurasi Cocok
- Kelas seimbang (mendekati 50/50).
- Biaya FP dan FN setara.
- Pelaporan eksekutif yang sederhana.

## Kapan Tidak Cocok
- Imbalance kuat (deteksi fake GPS: 97/3).
- FP dan FN berbeda biaya (salah tuduh vs loloskan pelanggaran).

## Pitfalls
- Jangan pernah laporkan akurasi saja di kasus imbalanced.
- "Akurasi 99%" tanpa konteks distribusi kelas = tanda merah.
- Selalu bandingkan dengan baseline (prediksi kelas mayoritas).

## Kaitan
- → [Presisi](10-presisi.md), [Recall](11-recall.md), [F1-Score](12-f1-score.md)
- → [Confusion Matrix](08-confusion-matrix.md)
