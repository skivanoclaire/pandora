# Natural Language Processing (NLP)

**Kategori:** Data Tak Terstruktur | **Level:** Menengah

## Ringkasan
NLP adalah cabang AI yang membuat komputer memahami, menafsirkan, dan menghasilkan bahasa manusia. Dalam konteks PANDORA, NLP diterapkan pada teks keterangan izin/cuti dan alasan ketidakhadiran.

## Penjelasan
Pipeline NLP klasik: cleaning → lowercasing → tokenisasi → stopword removal → stemming → vektorisasi (TF-IDF, embeddings) → modeling (Naive Bayes, SVM, Transformer).

## Tugas-tugas Umum NLP

| Tugas | Contoh di PANDORA |
|-------|-------------------|
| Klasifikasi teks | Kategorisasi alasan izin (sakit/keluarga/dinas) |
| NER | Ekstrak nama OPD, tanggal dari keterangan |
| Sentiment Analysis | Prioritas keluhan pegawai (negatif → urgent) |
| Topic Modeling | Tema alasan ketidakhadiran terbanyak bulan ini |
| Summarization | Ringkasan laporan inspektorat |

## Studi Kasus PANDORA
Field `keterangan` pada present_rekap berisi teks bebas alasan izin/cuti. NLP membantu:

- **Klasifikasi otomatis:** "sakit demam anak" → kategori `sakit_keluarga`. "rapat di Jakarta" → kategori `dinas_luar`.
- **Topic modeling:** dari 1.000 keterangan izin bulan April → topik dominan: "sakit", "acara keluarga", "dinas luar kota".
- **NER:** ekstrak lokasi dinas luar dari teks bebas → validasi dengan data present_rekap.

```python
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB

vec = TfidfVectorizer(max_features=3000)
X = vec.fit_transform(df_kehadiran['keterangan'].fillna(''))
nb = MultinomialNB()
nb.fit(X_train, y_train_kategori)  # sakit/keluarga/dinas/lainnya
```

## Pitfalls
- Bahasa Indonesia memiliki morfologi rumit (afiksasi); tool English tidak langsung pakai.
- Singkatan dan bahasa daerah Kaltara perlu dictionary khusus.
- Bias dan privasi dalam data teks keterangan pegawai.

## Kaitan
- → [Tokenisasi](02-tokenisasi.md), [Stemming](03-stemming.md), [TF-IDF](04-tf-idf.md)
- → [Naive Bayes](../03-klasifikasi/04-naive-bayes.md)
