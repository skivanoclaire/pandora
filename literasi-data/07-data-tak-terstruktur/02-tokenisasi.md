# Tokenisasi

**Kategori:** Data Tak Terstruktur | **Level:** Dasar

## Ringkasan
Tokenisasi memecah teks menjadi unit bermakna (token) — bisa kata, karakter, atau sub-kata. Langkah pertama dalam hampir semua pipeline NLP.

## Jenis Tokenisasi

### Word-level
```
"Pegawai izin sakit di rumah sakit Tarakan."
→ ["Pegawai", "izin", "sakit", "di", "rumah", "sakit", "Tarakan", "."]
```

### Subword (BPE, WordPiece)
```
"keterlambatan" → ["keter", "##lambat", "##an"]
```
Keunggulan: menangani out-of-vocabulary (OOV).

## Tantangan Bahasa Indonesia
- Afiksasi: "keterlambatan" = ke + terlambat + an.
- Singkatan: "yg", "dgn", "tdk", "krn" dalam keterangan izin.
- Nama tempat dengan spasi: "Tanjung Selor", "Nunukan Selatan".

## Studi Kasus PANDORA
Tokenisasi field keterangan izin dari present_rekap:

```python
from nltk.tokenize import word_tokenize

keterangan = "Izin sakit anak rawat inap di RSUD Tarakan"
tokens = word_tokenize(keterangan.lower())
# ['izin', 'sakit', 'anak', 'rawat', 'inap', 'di', 'rsud', 'tarakan']

# Stopword removal
stopwords_id = {'di', 'ke', 'dari', 'yang', 'dan', 'ini', 'itu'}
tokens_clean = [t for t in tokens if t not in stopwords_id]
# ['izin', 'sakit', 'anak', 'rawat', 'inap', 'rsud', 'tarakan']
```

Setelah bersih, token siap untuk vektorisasi TF-IDF dan klasifikasi kategori izin.

## Pitfalls
- Simple split pada spasi gagal pada emotikon, URL, singkatan.
- Encoding UTF-8 harus benar; karakter Indonesia bisa rusak di encoding lain.
- Jangan buang stopword jika konteks penting (misal "tidak sakit" vs "sakit").

## Kaitan
- → [Stemming](03-stemming.md), [TF-IDF](04-tf-idf.md)
- → [NLP](01-nlp.md)
