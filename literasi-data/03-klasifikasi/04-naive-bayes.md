# Naive Bayes

**Kategori:** Klasifikasi | **Level:** Menengah

## Ringkasan
Naive Bayes adalah klasifier probabilistik berbasis Teorema Bayes dengan asumsi "naif" bahwa semua fitur saling independen. Sederhana, cepat, dan sering akurat — terutama untuk klasifikasi teks.

## Rumus
$$P(C|X) = \frac{P(X|C) \cdot P(C)}{P(X)}$$

Asumsi independensi: $P(X|C) = \prod_{i=1}^{n} P(x_i | C)$

## Varian
- **Gaussian NB** — fitur numerik, asumsi distribusi normal.
- **Multinomial NB** — fitur diskrit (count), populer untuk teks/TF-IDF.
- **Bernoulli NB** — fitur biner (ada/tidak).

## Studi Kasus PANDORA
Memprediksi apakah pegawai akan berstatus mkttw besok berdasar fitur kategorik:

```
P(mkttw | Senin, Hujan, OPD=Dinas_Kominfo) = 
  P(Senin|mkttw) x P(Hujan|mkttw) x P(Dinas_Kominfo|mkttw) x P(mkttw) / Z
```

Dari data historis present_rekap 6.475 pegawai: P(mkttw|Senin, Hujan) = 0.72 → sistem memberi flag peringatan dini.

```python
from sklearn.naive_bayes import GaussianNB

fitur = ['hari_minggu', 'cuaca_encoded', 'opd_encoded', 'golongan_encoded']
nb = GaussianNB()
nb.fit(X_train[fitur], y_train)
proba = nb.predict_proba(X_test[fitur])
```

Juga cocok untuk klasifikasi teks keterangan izin/cuti: "sakit demam" vs "keperluan keluarga" vs "dinas luar kota" menggunakan Multinomial NB + TF-IDF.

## Pitfalls
- Asumsi independensi sering tidak realistis (umur & golongan berkorelasi), namun model tetap bekerja.
- Probabilitas nol pada fitur yang belum pernah muncul → pakai Laplace smoothing.
- Tidak cocok jika ada fitur yang sangat dependent satu sama lain.

## Kaitan
- → [TF-IDF](../07-data-tak-terstruktur/04-tf-idf.md)
- → [Algoritma Klasifikasi](01-algoritma-klasifikasi.md)
