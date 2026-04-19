# Recall (Sensitivity)

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Recall adalah proporsi kasus positif yang berhasil ditangkap model. Menjawab: "Dari semua yang sebenarnya anomali, berapa yang model temukan?".

## Rumus
$$\text{Recall} = \frac{TP}{TP + FN}$$

## Penjelasan
- Recall tinggi → sedikit kasus anomali yang terlewat.
- Recall rendah → banyak fake GPS yang lolos tidak terdeteksi.

## Studi Kasus PANDORA
Dari confusion matrix deteksi fake GPS (TP=25, FN=5):
- Recall = 25 / (25 + 5) = **83.3%**.

Artinya: dari 30 check-in yang benar-benar fake GPS, model menangkap 25 (83.3%) tetapi melewatkan 5 (16.7%).

**Implikasi:** 5 pegawai yang fake GPS lolos tanpa terdeteksi. Untuk program penegakan disiplin, apakah kita terima 5 pelanggar lolos? Jika tidak, turunkan threshold → recall naik, tapi presisi turun.

## Kapan Recall Penting
- Biaya FN tinggi: pelanggaran keamanan/kedisiplinan terlewat.
- Screening awal: lebih baik investigasi lebih banyak daripada melewatkan pelanggar.
- Deteksi anomali geofence: satu fake GPS yang lolos bisa jadi preseden.

## Trade-off
- Recall naik = presisi turun. Atur threshold sesuai prioritas:
  - Screening: utamakan recall.
  - Teguran formal: utamakan presisi.
  - Keseimbangan: F1-Score.

## Pitfalls
- Recall 100% mudah dicapai dengan memprediksi SEMUA sebagai anomali (tapi presisi hancur).
- Dalam multikelas (tw/mkttw/tk/ta), laporkan recall per kelas dan rata-rata macro/weighted.

## Kaitan
- → [Presisi](10-presisi.md)
- → [F1-Score](12-f1-score.md)
- → [Confusion Matrix](08-confusion-matrix.md)
