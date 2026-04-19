# Performance Klasifikasi

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
Setelah model klasifikasi dilatih, kita harus mengukur performanya. Tidak ada satu metrik yang cocok untuk semua kasus — pemilihan bergantung pada konteks dan keseimbangan kelas.

## Penjelasan
Pada data imbalanced seperti deteksi anomali PANDORA (97% normal, 3% anomali), akurasi saja sangat menyesatkan. Perlu kombinasi metrik yang tepat.

## Metrik Utama

| Metrik | Cocok untuk | Konteks PANDORA |
|--------|-------------|-----------------|
| Akurasi | Kelas seimbang | Tidak cocok untuk deteksi anomali |
| Presisi | FP mahal | Jangan salah tuduh pegawai disiplin |
| Recall | FN mahal | Jangan lewatkan fake GPS |
| F1-Score | Trade-off P & R | Metrik utama deteksi anomali |
| PR-AUC | Imbalance kuat | Lebih informatif dari ROC-AUC |

## Studi Kasus PANDORA
Model deteksi fake GPS pada check-in 6.475 pegawai. Kelas anomali hanya 3%:

- Akurasi 97% bisa diperoleh hanya dengan selalu menebak "normal" → tidak berguna.
- **Recall** prioritas: jangan sampai pegawai yang fake GPS terlewat.
- **Presisi** juga penting: jangan salah tuduh pegawai yang memang dinas luar.
- Laporkan: Precision, Recall, F1, dan PR-AUC.

```python
from sklearn.metrics import classification_report, average_precision_score

print(classification_report(y_test, y_pred, target_names=['Normal', 'Anomali']))
pr_auc = average_precision_score(y_test, y_proba[:, 1])
print(f"PR-AUC: {pr_auc:.3f}")
```

## Pitfalls
- Metrik tunggal sering menyesatkan — selalu laporkan beberapa metrik.
- Test set bocor ke train → angka palsu.
- Pilih metrik SEBELUM training, bukan setelah lihat hasil.

## Kaitan
- → [Confusion Matrix](08-confusion-matrix.md)
- → [Akurasi](09-akurasi.md), [Presisi](10-presisi.md), [Recall](11-recall.md), [F1-Score](12-f1-score.md)
