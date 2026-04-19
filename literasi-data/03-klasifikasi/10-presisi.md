# Presisi (Precision)

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Presisi adalah proporsi prediksi positif yang benar-benar positif. Menjawab: "Dari semua yang model bilang anomali, berapa yang benar?".

## Rumus
$$\text{Precision} = \frac{TP}{TP + FP}$$

## Penjelasan
- Presisi tinggi → sedikit alarm palsu (FP rendah).
- Presisi rendah → banyak pegawai normal dikira anomali.

## Studi Kasus PANDORA
Dari confusion matrix deteksi fake GPS (TP=25, FP=15):
- Presisi = 25 / (25 + 15) = **62.5%**.

Artinya: dari 40 check-in yang diflag "Fake GPS", hanya 25 yang benar-benar palsu. 15 sisanya adalah pegawai normal yang salah ditandai.

**Implikasi:** jika flag anomali langsung dipakai untuk teguran formal, presisi 62.5% berarti ~38% pegawai ditegur tanpa dasar. Naikkan threshold prediksi sampai presisi acceptable, atau gunakan flag sebagai "perlu investigasi manual".

## Kapan Presisi Penting
- Biaya FP tinggi: menuduh pegawai disiplin sebagai pelanggar.
- Keputusan formal/administratif berdasar prediksi model.
- Lebih baik lewatkan beberapa anomali daripada salah tuduh.

## Pitfalls
- Mengejar presisi 100% berarti hampir tidak pernah memprediksi positif.
- Presisi tinggi + recall rendah = banyak anomali yang terlewat.
- Selalu laporkan bersama recall untuk gambaran lengkap.

## Kaitan
- → [Recall](11-recall.md)
- → [F1-Score](12-f1-score.md)
- → [Confusion Matrix](08-confusion-matrix.md)
