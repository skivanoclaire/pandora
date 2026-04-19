# F1-Score

**Kategori:** Klasifikasi | **Level:** Dasar

## Ringkasan
F1-Score adalah mean harmonik dari presisi dan recall. Memberi satu angka yang menyeimbangkan keduanya. Sangat berguna pada data imbalanced ketika akurasi menyesatkan.

## Rumus
$$F_1 = 2 \cdot \frac{\text{Precision} \cdot \text{Recall}}{\text{Precision} + \text{Recall}}$$

Bentuk umum: $F_\beta = (1 + \beta^2) \cdot \frac{P \cdot R}{\beta^2 \cdot P + R}$
- beta=1: seimbang. beta=2: recall 2x lebih penting. beta=0.5: presisi 2x lebih penting.

## Studi Kasus PANDORA
Dari deteksi fake GPS: Precision=62.5%, Recall=83.3%.
- F1 = 2 x (0.625 x 0.833) / (0.625 + 0.833) = **71.4%**.

Dibanding akurasi 98%, F1 memberikan gambaran lebih jujur tentang performa pada kelas anomali.

Untuk PANDORA, gunakan **F2-score** (recall lebih penting) karena melewatkan fake GPS lebih berbahaya daripada salah flag:

```python
from sklearn.metrics import fbeta_score, f1_score

f1 = f1_score(y_test, y_pred)
f2 = fbeta_score(y_test, y_pred, beta=2)  # recall lebih penting
print(f"F1: {f1:.3f}, F2: {f2:.3f}")
```

## Variasi Multikelas
- **Macro-F1** — rata-rata F1 per kelas (tw, mkttw, tk, ta setara).
- **Weighted-F1** — tertimbang frekuensi kelas.
- **Micro-F1** — agregat TP/FP/FN, didominasi kelas mayoritas.

## Pitfalls
- F1 menghukum ketidakseimbangan P dan R (mean harmonik, bukan aritmetik).
- F1 tinggi tidak selalu berarti model baik — selalu lihat P dan R terpisah.
- Untuk kasus PANDORA, pertimbangkan F2 karena recall lebih kritis.

## Kaitan
- → [Presisi](10-presisi.md), [Recall](11-recall.md)
- → [Confusion Matrix](08-confusion-matrix.md)
