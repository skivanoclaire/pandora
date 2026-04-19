# Siklus Hidup Proyek Data (CRISP-DM)

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
CRISP-DM (Cross-Industry Standard Process for Data Mining) adalah kerangka 6 fase iteratif untuk mengelola proyek data science. Standar de facto industri yang cocok diterapkan pada proyek analitik kehadiran.

## Enam Fase

1. **Business Understanding** — Apa pertanyaan yang dijawab?
2. **Data Understanding** — Eksplorasi awal data, kualitas, sumber.
3. **Data Preparation** — Pembersihan, transformasi, feature engineering.
4. **Modeling** — Pemilihan dan training algoritma.
5. **Evaluation** — Apakah model menjawab pertanyaan bisnis?
6. **Deployment** — Integrasi ke sistem produksi.

Panah dapat balik kapan saja — proses iteratif, bukan linier.

## Studi Kasus PANDORA
Proyek "Deteksi Fake GPS pada Presensi ASN":

1. **BU:** Tujuan = mendeteksi pegawai yang memalsukan lokasi check-in agar bisa ditindaklanjuti.
2. **DU:** Eksplorasi 3,3 juta record present_rekap, cek distribusi lat/long_berangkat, identifikasi missing values dan tanggal `0000-00-00`.
3. **DP:** Bersihkan koordinat null, hitung velocity antara check-in dan check-out, hitung jarak ke geofence zone.
4. **Modeling:** Train Random Forest dan Isolation Forest pada fitur velocity, jarak_geofence, IMEI_match.
5. **Evaluation:** F1-score >= 0.80 pada data test → model layak.
6. **Deployment:** Expose via FastAPI endpoint `/api/analytics/anomaly`, tampilkan flag di dashboard PANDORA.

```python
# Fase 3: Data Preparation
df_kehadiran['velocity'] = haversine(df_kehadiran[['lat_berangkat','long_berangkat']], 
                                      df_kehadiran[['lat_pulang','long_pulang']]) / delta_jam
df_kehadiran['jarak_geofence'] = hitung_jarak_ke_geofence(df_kehadiran, df_geofence)
```

## Pitfalls
- Lompat ke modeling tanpa Business Understanding = solusi tanpa masalah.
- Data Preparation biasanya menyita 60-80% waktu — jangan diremehkan.
- Tanpa fase Deployment, model menumpuk di laptop dan tidak berdampak.

## Kaitan
- → [Data Preprocessing](../02-data-engineering/05-data-preprocessing.md)
- → [Performance Klasifikasi](../03-klasifikasi/07-performance-klasifikasi.md)
