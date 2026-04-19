# Tipe Data

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Tipe data menentukan operasi apa yang valid pada sebuah variabel. Salah memilih tipe = salah memilih algoritma. Klasifikasi utama: numerik (kontinu, diskrit) dan kategorik (nominal, ordinal).

## Klasifikasi

### Numerik
- **Kontinu** — bernilai dalam rentang tak hingga. Contoh: jarak_geofence (15.2 meter), velocity (km/jam).
- **Diskrit** — bilangan bulat. Contoh: jumlah_alpa (3 hari), jumlah_pegawai_aktif (6.475).

### Kategorik
- **Nominal** — tanpa urutan. Contoh: nama_opd, status kehadiran (tw, mkttw, tk, ta, i, s, c, dl, dsp).
- **Ordinal** — dengan urutan. Contoh: golongan kepegawaian (II/a < III/a < IV/a).

### Lain
- **Tanggal/Waktu** — perlu parsing khusus (jam_masuk, jam_pulang, tanggal).
- **Boolean** — true/false (is_anomaly, setelah_libur).
- **Teks bebas** — keterangan izin/cuti, perlu NLP.

## Studi Kasus PANDORA

| Kolom present_rekap | Tipe | Skala |
|---------------------|------|-------|
| nip | string (ID) | nominal |
| nama_opd | kategori | nominal |
| golongan | kategori | ordinal |
| jam_masuk | time | interval |
| lat_berangkat | float | rasio |
| tw | integer (0/1) | nominal (flag) |
| menit_terlambat | float | rasio |

```python
df_kehadiran['tanggal'] = pd.to_datetime(df_kehadiran['tanggal'])
df_kehadiran['status'] = df_kehadiran['status'].astype('category')
```

## Pitfalls
- Mengkode golongan sebagai angka 1-4 dan memakainya di regresi linier tanpa berpikir = salah karena jarak antar ordinal tidak setara.
- Tanggal yang diparse sebagai string membuat agregasi per bulan mustahil.
- Status kehadiran (tw, mkttw, tk) adalah nominal, bukan ordinal.

## Kaitan
- → [Data Transformation](08-data-transformation.md)
- → [Statistika Deskriptif](../01-fondasi/03-statistika-deskriptif.md)
