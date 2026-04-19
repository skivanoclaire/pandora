# Stemming

**Kategori:** Data Tak Terstruktur | **Level:** Dasar

## Ringkasan
Stemming mereduksi kata ke bentuk dasar/akarnya dengan menghapus afiks. Tujuan: mengurangi redundansi — "keterlambatan", "terlambat", "lambat" jadi "lambat".

## Penjelasan

| Aspek | Stemming | Lemmatization |
|-------|----------|---------------|
| Metode | Aturan pemotongan afiks | Kamus & morfologi |
| Hasil | Bisa bukan kata valid | Kata dasar valid |
| Kecepatan | Cepat | Lebih lambat |
| Akurasi | Kasar | Lebih presisi |

## Stemmer Bahasa Indonesia
- **Nazief-Adriani** — algoritma klasik, pakai kamus + aturan.
- **Enhanced Confix Stripping (ECS)** — modifikasi dengan lebih banyak aturan.
- **Sastrawi** — library Python populer untuk stemming Bahasa Indonesia.

## Studi Kasus PANDORA
Stemming field keterangan izin dari present_rekap:

```python
from Sastrawi.Stemmer.StemmerFactory import StemmerFactory

stemmer = StemmerFactory().create_stemmer()

# Keterangan izin pegawai
keterangan = ["keterlambatan karena banjir", "terlambat akibat kemacetan", "izin keperluan keluarga"]

for k in keterangan:
    print(stemmer.stem(k))
# "lambat banjir"
# "lambat akibat macet"  
# "izin perlu keluarga"
```

"keterlambatan" dan "terlambat" keduanya jadi "lambat" → konsolidasi fitur untuk klasifikasi alasan ketidakhadiran. "keperluan" menjadi "perlu" → normalisasi vokabuler.

## Pitfalls
- **Over-stemming:** "pemberitahuan" dan "memberitahu" jadi "tahu" — kehilangan konteks.
- **Under-stemming:** dua bentuk kata sama tidak disamakan.
- Stemmer Inggris (Porter, Snowball) tidak cocok untuk Bahasa Indonesia.
- Untuk tugas presisi tinggi (legal, surat dinas), lebih baik lemmatization.

## Kaitan
- → [Tokenisasi](02-tokenisasi.md), [TF-IDF](04-tf-idf.md)
- → [NLP](01-nlp.md)
