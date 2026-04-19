# Data, Informasi, dan Pengetahuan

**Kategori:** Fondasi | **Level:** Dasar

## Ringkasan
Piramida DIKW (Data-Information-Knowledge-Wisdom) menjelaskan bagaimana fakta mentah berubah menjadi keputusan yang berguna. Data mentah diproses menjadi informasi, dianalisis menjadi pengetahuan, lalu diterapkan menjadi kearifan.

## Penjelasan
**Data** adalah fakta mentah tanpa konteks, misalnya `08:05`. **Informasi** adalah data yang sudah diberi konteks — "Pegawai NIP 19870615 masuk pukul 08:05 pada 18 April 2026 di Dinas Kominfo". **Pengetahuan** muncul dari pola banyak informasi — "30% pegawai Dinas Kominfo rutin berstatus mkttw di hari Senin". **Kearifan** adalah kemampuan menerapkan pengetahuan untuk keputusan — "Geser jam briefing pagi agar mengurangi mkttw di awal pekan".

## Hirarki DIKW

| Tingkat | Contoh di PANDORA |
|---------|-------------------|
| Data | `jam_masuk=08:05, nip=19870615..., lat_berangkat=3.2945` |
| Informasi | "Pegawai X di OPD Dinas Kominfo check-in jam 08:05, status mkttw" |
| Pengetahuan | "30% pegawai Dinas Kominfo berstatus mkttw di awal pekan" |
| Kearifan | "Terapkan kebijakan fleksibel jam masuk untuk OPD dengan pola mkttw tinggi" |

## Studi Kasus PANDORA
Dari 3,3 juta record present_rekap, PANDORA mengubah data mentah (jam_masuk, jam_pulang, lat/long) menjadi informasi terstruktur per pegawai per hari. Analisis pola lintas waktu menghasilkan pengetahuan tentang OPD mana yang perlu perhatian khusus.

```python
df_kehadiran = pd.read_sql("SELECT nip, tanggal, jam_masuk, tw, mkttw FROM present_rekap", conn)
pola_senin = df_kehadiran[df_kehadiran['tanggal'].dt.dayofweek == 0].groupby('opd')['mkttw'].mean()
```

## Pitfalls
- Banyak sistem berhenti di level informasi (dashboard) tanpa mengekstrak pengetahuan.
- Kearifan butuh domain expert kepegawaian, tidak otomatis dari data.
- Data tanpa metadata (satuan, sumber) cepat kehilangan konteks.

## Kaitan
- → [Data](../02-data-engineering/01-data.md)
- → [Statistika Deskriptif](03-statistika-deskriptif.md)
