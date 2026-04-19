# Data Science vs Artificial Intelligence

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Data Science fokus pada ekstraksi wawasan dari data (berakhir pada laporan atau model prediksi). AI fokus pada membangun sistem yang berperilaku cerdas (bisa tanpa data historis, misal rule-based). Keduanya saling melengkapi.

## Penjelasan
**Data Science** adalah disiplin lintas bidang (statistik + programming + domain knowledge) yang menjawab pertanyaan dengan data. Outputnya bisa berupa dashboard, insight, atau model prediktif.

**Artificial Intelligence** adalah cabang ilmu komputer yang menciptakan agen yang dapat memahami, belajar, dan bertindak. AI mencakup machine learning, NLP, computer vision, reasoning, dan sistem pakar. ML adalah subset AI yang belajar dari data — area tumpang tindih terbesar dengan Data Science.

## Perbandingan

| Aspek | Data Science | Artificial Intelligence |
|-------|-------------|------------------------|
| Tujuan | Ekstrak wawasan dari data | Buat sistem cerdas |
| Output | Dashboard, model, insight | Agen/sistem otomatis |
| Contoh PANDORA | Analisis pola mkttw per OPD | Face recognition SIKARA |

## Studi Kasus PANDORA
- **Data Science:** Menganalisis 3,3 juta record present_rekap dari 6.475 pegawai untuk mencari pola keterlambatan per OPD dan triwulan.
- **AI:** Sistem face recognition SIKARA di mesin presensi (computer vision); deteksi fake GPS otomatis berbasis rule geofence + velocity check.
- **Titik temu:** Model ML yang dilatih dari data kehadiran historis, lalu dideploy sebagai endpoint FastAPI untuk deteksi anomali real-time = Data Science yang menjadi AI.

```python
# Data Science: analisis pola
pola = df_kehadiran.groupby(['opd', df_kehadiran['tanggal'].dt.quarter])['mkttw'].sum()
# AI: endpoint deteksi anomali
# POST /api/analytics/anomaly → return {"is_anomaly": true, "reason": "velocity_outlier"}
```

## Pitfalls
- "Pakai AI" tanpa data berkualitas = model bias dan gagal.
- Tidak semua AI butuh ML — rule-based geofence check sudah sangat efektif.
- Jangan overengineer: jika aturan sederhana cukup, tidak perlu deep learning.

## Kaitan
- → [Data Mining vs ML](06-datamining-vs-ml.md)
- → [Deep Learning](../03-klasifikasi/06-deep-learning.md)
