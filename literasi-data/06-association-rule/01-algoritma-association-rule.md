# Algoritma Association Rule

**Kategori:** Association Rule | **Level:** Menengah

## Ringkasan
Association Rule Mining menemukan aturan if-then yang menggambarkan co-occurrence antar item dalam data. Contoh PANDORA: "Jika hari Senin DAN setelah libur, maka 72% pegawai berstatus mkttw".

## Penjelasan
Aturan berbentuk $X \Rightarrow Y$, di mana X (antecedent) dan Y (consequent) adalah itemset disjoint.

## Tiga Metrik Utama

**Support** — seberapa sering itemset muncul:
$$\text{Support}(X) = \frac{\#(X)}{N}$$

**Confidence** — seberapa sering X diikuti Y:
$$\text{Confidence}(X \Rightarrow Y) = \frac{\text{Support}(X \cup Y)}{\text{Support}(X)}$$

**Lift** — apakah Y lebih sering muncul bersama X dibanding acak:
$$\text{Lift} = \frac{\text{Confidence}(X \Rightarrow Y)}{\text{Support}(Y)}$$

Lift > 1 = positif. Lift = 1 = independen. Lift < 1 = saling menolak.

## Studi Kasus PANDORA
"Transaksi" = hari kerja per OPD. "Item" = status/kejadian yang terjadi hari itu.

Item: `mkttw_massal`, `setelah_libur`, `hujan`, `hari_Senin`, `izin_massal`, `tk_tinggi`.

Aturan ditemukan dari present_rekap:
- `{Senin, setelah_libur} => {mkttw_massal}` — support 15%, confidence 72%, lift 2.1.
- `{hujan, Jumat} => {izin_massal}` — support 8%, confidence 65%, lift 1.9.

Interpretasi: hari Senin setelah libur, 72% dari sampel mengalami keterlambatan massal — 2.1x lebih sering dari baseline.

## Pitfalls
- Support tinggi saja belum tentu menarik (mungkin trivial).
- Confidence tinggi menyesatkan jika Y memang sangat umum — selalu cek lift.
- Banyak rule dihasilkan → filter berdasar kepentingan domain kepegawaian.

## Kaitan
- → [Apriori](02-apriori.md)
- → [Data Mining vs ML](../01-fondasi/06-datamining-vs-ml.md)
