# Data Mining vs Machine Learning

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Data Mining menemukan pola tersembunyi dari data besar. Machine Learning membangun model yang belajar dari data untuk prediksi. Data Mining sering memakai algoritma ML, tapi tujuannya berbeda: discovery vs prediction.

## Penjelasan
**Data Mining** menjawab "pola apa yang ada dalam data ini?" — outputnya berupa aturan, asosiasi, atau cluster yang ditafsirkan manusia. **Machine Learning** menjawab "apa yang akan terjadi berdasarkan data masa lalu?" — outputnya model yang memprediksi pada data baru.

## Perbandingan

| Aspek | Data Mining | Machine Learning |
|-------|-------------|------------------|
| Tujuan | Menemukan pola tersembunyi | Membangun model prediktif |
| Output | Insight, aturan, cluster | Model yang bisa diquery |
| Pendekatan | Eksploratoris | Konvergen ke fungsi |
| Contoh PANDORA | Apriori pada pola keterlambatan | Klasifikasi fake GPS |

## Studi Kasus PANDORA
- **Data Mining:** Jalankan Apriori pada present_rekap 6.475 pegawai → ditemukan aturan `{Senin, setelah_libur} => {mkttw}` dengan confidence 72% dan lift 2.1.
- **Machine Learning:** Latih model klasifikasi yang memprediksi apakah sebuah check-in adalah fake GPS berdasar fitur lat/long_berangkat, velocity, jarak_dari_geofence, dan IMEI.

```python
# Data Mining: temukan pola co-occurrence status
from mlxtend.frequent_patterns import apriori
freq = apriori(df_status_onehot, min_support=0.05, use_colnames=True)

# ML: prediksi anomali check-in
from sklearn.ensemble import RandomForestClassifier
model = RandomForestClassifier()
model.fit(X_train[['lat', 'long', 'velocity', 'jarak_geofence']], y_train)
```

## Pitfalls
- Sering tertukar — di praktik, pipeline sering pakai keduanya berurutan.
- Data Mining tanpa validasi statistik = penemuan pola palsu (spurious patterns).
- ML tanpa cukup data berkualitas = model tidak reliable.

## Kaitan
- → [Algoritma Klasifikasi](../03-klasifikasi/01-algoritma-klasifikasi.md)
- → [Apriori](../06-association-rule/02-apriori.md)
