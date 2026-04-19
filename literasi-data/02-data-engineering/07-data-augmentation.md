# Data Augmentation

**Kategori:** Data Engineering | **Level:** Menengah

## Ringkasan
Data augmentation menambah variasi data training secara sintetik agar model lebih general dan tahan variasi nyata. Umum dipakai pada citra dan teks, serta untuk menangani imbalanced class pada data tabular.

## Teknik Berdasar Jenis Data

### Citra (Computer Vision)
- Rotasi, flip, crop acak, perubahan brightness/contrast.
- Penambahan noise, blur, cutout, mixup.

### Teks (NLP)
- Sinonim replacement, back-translation, random insertion/deletion.

### Tabular
- **SMOTE** (Synthetic Minority Oversampling) untuk kelas minoritas.
- Penambahan noise gaussian pada fitur numerik.

## Studi Kasus PANDORA
**Face recognition SIKARA:** Setiap pegawai hanya punya ~10 foto enrollment. Augmentasi: rotasi ±15 derajat, brightness ±20%, sedikit blur menghasilkan 10 foto menjadi 100 efektif. Model CNN lebih tahan pencahayaan kantor pagi/sore.

**Deteksi anomali check-in (tabular):** Hanya 3% record berstatus anomali (fake GPS). SMOTE membuat sample sintetik agar model tidak bias ke kelas mayoritas.

```python
from imblearn.over_sampling import SMOTE

# Hanya 3% anomali dari 6.475 pegawai x 250 hari
smote = SMOTE(random_state=42)
X_resampled, y_resampled = smote.fit_resample(
    X_train[['lat_berangkat', 'long_berangkat', 'velocity', 'jarak_geofence']], 
    y_train
)
print(f"Sebelum: {y_train.value_counts().to_dict()}")
print(f"Sesudah: {pd.Series(y_resampled).value_counts().to_dict()}")
```

## Pitfalls
- Augmentasi tidak realistis (flip vertikal wajah) merusak performance.
- SMOTE bisa membuat sample sintetik yang tidak masuk akal pada fitur kategorik.
- Augmentasi hanya pada train set, bukan test set.

## Kaitan
- → [Computer Vision](../07-data-tak-terstruktur/05-computer-vision.md)
- → [Data Preprocessing](05-data-preprocessing.md)
