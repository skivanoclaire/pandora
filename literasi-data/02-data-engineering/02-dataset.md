# Dataset

**Kategori:** Data Engineering | **Level:** Dasar

## Ringkasan
Dataset adalah kumpulan data yang dikemas untuk satu tujuan analisis. Biasanya berbentuk tabel: baris = observasi (instance), kolom = variabel (feature).

## Penjelasan
Dataset bisa berupa CSV/Excel (tabular), image folder + label (visi), atau time series terurut waktu. Setiap dataset yang baik memiliki: skema kolom yang jelas, dokumentasi sumber, dan pemisahan train/validation/test untuk ML.

## Studi Kasus PANDORA
Dataset untuk model deteksi anomali check-in, dirakit dari present_rekap dan tabel geofence:

| Kolom | Tipe | Contoh |
|-------|------|--------|
| nip | string | "19870615200501001" |
| opd | kategori | "Dinas Kominfo" |
| tanggal | datetime | 2026-04-18 |
| jam_masuk | time | 08:05 |
| lat_berangkat | float | 3.2945 |
| long_berangkat | float | 117.6310 |
| jarak_geofence | float | 15.2 (meter) |
| velocity | float | 450.0 (km/jam) |
| is_anomaly | label | 1 |

Total: 6.475 pegawai x ~250 hari kerja = ~1,6 juta instance per tahun.

## Split Data ML
- **Train (70%)** — untuk melatih model.
- **Validation (15%)** — tuning hyperparameter.
- **Test (15%)** — evaluasi akhir, hanya dipakai sekali.

```python
from sklearn.model_selection import train_test_split
X_train, X_test, y_train, y_test = train_test_split(
    df_kehadiran[fitur], df_kehadiran['is_anomaly'], test_size=0.3, stratify=df_kehadiran['is_anomaly'])
```

## Pitfalls
- Bocornya test set ke train (data leakage) menghasilkan akurasi palsu.
- Imbalanced dataset (misal 97% normal, 3% anomali) butuh stratified split dan penanganan khusus.
- Time series harus di-split secara kronologis, bukan acak.

## Kaitan
- → [Dataset Public](09-dataset-public.md)
- → [Data Quality](04-data-quality.md)
