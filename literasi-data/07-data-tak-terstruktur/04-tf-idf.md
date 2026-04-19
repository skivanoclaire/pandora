# TF-IDF

**Kategori:** Data Tak Terstruktur | **Level:** Menengah

## Ringkasan
TF-IDF (Term Frequency - Inverse Document Frequency) mengukur pentingnya kata dalam satu dokumen relatif terhadap seluruh korpus. Kata yang sering muncul di satu dokumen tapi jarang di dokumen lain = penting.

## Rumus
- **TF:** $\text{TF}(t, d) = \frac{\text{jumlah } t \text{ di } d}{\text{total kata di } d}$
- **IDF:** $\text{IDF}(t) = \log \frac{N}{\text{jumlah dokumen memuat } t}$
- **TF-IDF:** $\text{TF-IDF}(t, d) = \text{TF}(t, d) \cdot \text{IDF}(t)$

## Penjelasan
- TF tinggi + IDF tinggi = kata sering di dokumen INI, jarang di dokumen LAIN → **pembeda kuat**.
- Kata umum ("yang", "dan") punya IDF rendah → otomatis tertekan.

## Studi Kasus PANDORA
Korpus: 5.000 keterangan izin dari field keterangan di present_rekap. Setiap keterangan = satu dokumen.

Kata "banjir" muncul 8x dalam satu keterangan (30 kata), muncul di 40 dari 5.000 dokumen:
- TF = 8/30 = 0.267.
- IDF = log(5000/40) = log(125) = 4.83.
- TF-IDF = 1.29 (tinggi — kata pembeda kuat).

Kata "izin" muncul di 4.500 dari 5.000 dokumen:
- IDF = log(5000/4500) = 0.10 (sangat rendah — kata umum, bukan pembeda).

```python
from sklearn.feature_extraction.text import TfidfVectorizer

corpus = df_kehadiran['keterangan'].fillna('').tolist()
vec = TfidfVectorizer(max_features=3000)
X_tfidf = vec.fit_transform(corpus)

# Kata paling penting untuk klasifikasi alasan izin
feature_names = vec.get_feature_names_out()
```

## Pitfalls
- Tidak menangkap urutan/konteks kata (bag-of-words).
- Vokabulari besar → matrix sangat sparse.
- Untuk pemahaman semantik, beralih ke word embeddings (Word2Vec, BERT).

## Kaitan
- → [Tokenisasi](02-tokenisasi.md), [Stemming](03-stemming.md)
- → [Naive Bayes](../03-klasifikasi/04-naive-bayes.md)
- → [NLP](01-nlp.md)
