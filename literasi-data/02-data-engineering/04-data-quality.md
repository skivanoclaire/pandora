# Data Quality

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Kualitas data adalah sejauh mana data memenuhi kebutuhan penggunanya. Enam dimensi klasik: accuracy, completeness, consistency, timeliness, validity, uniqueness.

## Enam Dimensi

| Dimensi | Pertanyaan | Contoh masalah di PANDORA |
|---------|-----------|---------------------------|
| Accuracy | Apakah nilainya benar? | lat/long_berangkat palsu karena GPS spoofing |
| Completeness | Apakah tidak ada yang kosong? | jam_pulang NULL untuk 320 pegawai |
| Consistency | Tidak bertentangan antar sumber? | Nama OPD di PANDORA beda dengan SIMPEG |
| Timeliness | Diupdate tepat waktu? | Sync present_rekap dari SIMPEG tertunda 2 jam |
| Validity | Sesuai aturan/format? | tanggal = `0000-00-00`, NIP berisi huruf |
| Uniqueness | Tidak terduplikasi? | Satu pegawai double check-in karena dua IMEI |

## Mengukur Kualitas

```python
df_kehadiran.isna().sum()                              # completeness
df_kehadiran['nip'].duplicated().sum()                 # uniqueness
df_kehadiran['nip'].str.match(r'^\d{18}$').mean()      # validity
(df_kehadiran['tanggal'] == '0000-00-00').sum()         # validity: tanggal invalid
```

## Studi Kasus PANDORA
Audit kualitas present_rekap April 2026 menemukan:
- 2.1% record memiliki lat_berangkat = 0.0 (GPS tidak aktif) — masalah **completeness**.
- 48 pegawai terdaftar dengan 2 IMEI berbeda — masalah **uniqueness** di device registration.
- 15 OPD memiliki nama berbeda antara PANDORA dan SIMPEG — masalah **consistency**.

## Pitfalls
- Menganggap "data dari sistem" pasti benar — tidak, fake GPS ada.
- Menghapus semua baris NA kehilangan informasi berharga (kenapa NA?).
- Mengisi NA dengan mean tanpa berpikir menyebabkan bias.

## Kaitan
- → [Data Preprocessing](05-data-preprocessing.md)
- → [Data Transformation](08-data-transformation.md)
