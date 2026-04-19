# Feature Engineering

**Kategori:** Data Engineering | **Level:** Menengah

## Ringkasan
Feature engineering adalah seni menciptakan fitur baru dari data mentah agar model dapat belajar pola lebih baik. Sering lebih berdampak pada performa daripada pemilihan algoritma.

## Teknik Umum

### Dari Tanggal
- Ekstrak: hari dalam minggu, apakah Senin, apakah setelah libur, minggu ke-N.

### Agregasi Time-window
- Rolling mean keterlambatan 7 hari terakhir, frekuensi alpa 30 hari.

### Spatial/Geofence
- Jarak haversine antara lat/long_berangkat ke centroid geofence zone.
- Velocity: jarak check-in ke check-out dibagi selisih waktu.

### Rasio
- `jumlah_tw / total_hari_kerja` → tingkat kedisiplinan.

## Studi Kasus PANDORA
Fitur mentah dari present_rekap: `nip, tanggal, jam_masuk, jam_pulang, lat/long_berangkat, lat/long_pulang`.

Fitur rekayasa untuk model deteksi anomali:

```python
import numpy as np

# Jarak dari geofence (meter)
df_kehadiran['jarak_geofence'] = haversine_vectorized(
    df_kehadiran['lat_berangkat'], df_kehadiran['long_berangkat'],
    df_geofence['lat_center'], df_geofence['long_center']) * 1000

# Velocity antara check-in dan check-out (km/jam)
df_kehadiran['velocity'] = df_kehadiran['jarak_checkin_checkout'] / df_kehadiran['delta_jam']

# Rolling rata-rata keterlambatan 7 hari
df_kehadiran['telat_7hari'] = df_kehadiran.groupby('nip')['menit_terlambat'].transform(
    lambda x: x.rolling(7, min_periods=1).mean())

# Flag hari setelah libur
df_kehadiran['setelah_libur'] = df_kehadiran['tanggal'].apply(is_after_holiday)

# Rata-rata keterlambatan OPD (target encoding)
df_kehadiran['rata_telat_opd'] = df_kehadiran.groupby('opd')['menit_terlambat'].transform('mean')
```

## Pitfalls
- Target encoding tanpa cross-validation menyebabkan data leakage.
- Fitur masa depan bocor ke masa lalu (look-ahead bias) pada time series.
- Velocity = 0 (check-in/out di lokasi sama) bisa jadi valid atau anomali.
- Fitur terlalu banyak tanpa selection → curse of dimensionality.

## Kaitan
- → [Data Transformation](08-data-transformation.md)
- → [Data Reduction](06-data-reduction.md)
