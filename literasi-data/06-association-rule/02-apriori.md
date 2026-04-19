# Algoritma Apriori

**Kategori:** Association Rule | **Level:** Menengah

## Ringkasan
Apriori (Agrawal & Srikant, 1994) menemukan frequent itemsets dan membangun association rules. Prinsip inti: jika suatu itemset tidak frequent, semua supersetnya juga tidak frequent (downward closure) — memungkinkan pruning.

## Algoritma
1. Scan database → hitung support tiap item (L1 = items frequent).
2. Generate kandidat itemset ukuran k dari L(k-1).
3. Scan database → hitung support kandidat.
4. Buang kandidat dengan support < min_support → L(k).
5. Ulangi sampai tidak ada L(k) baru.
6. Dari itemsets frequent, bangun rules yang memenuhi min_confidence.

## Studi Kasus PANDORA
"Transaksi" = log kehadiran harian per OPD, "item" = status/event hari itu:

```
Hari 1 OPD-A: {Senin, Hujan, mkttw_massal, setelah_libur}
Hari 2 OPD-A: {Selasa, Cerah, tw_dominan}
Hari 3 OPD-B: {Senin, Hujan, mkttw_massal, izin_tinggi}
...
```

Setting: min_support=5%, min_confidence=60%.

```python
from mlxtend.frequent_patterns import apriori, association_rules
import pandas as pd

# One-hot encoding status kehadiran per hari per OPD
# Kolom: Senin, Selasa, ..., Hujan, Cerah, setelah_libur, mkttw_massal, tw_dominan, tk_tinggi
freq = apriori(df_onehot, min_support=0.05, use_colnames=True)
rules = association_rules(freq, metric='confidence', min_threshold=0.6)

# Filter rule yang menarik
rules_menarik = rules[rules['lift'] > 1.5].sort_values('lift', ascending=False)
print(rules_menarik[['antecedents','consequents','support','confidence','lift']].head())
```

Rule yang ditemukan:
- `{Hujan, Senin} => {mkttw_massal}` — confidence 75%, lift 1.8.
- `{setelah_libur} => {tk_tinggi}` — confidence 70%, lift 2.3.

## Pitfalls
- Lambat pada dataset besar dengan banyak item → pakai FP-Growth.
- min_support terlalu kecil → terlalu banyak rule, kebanyakan noise.
- min_support terlalu besar → kehilangan pola menarik.
- Kausalitas tidak terjamin — korelasi saja.

## Kaitan
- → [Algoritma Association Rule](01-algoritma-association-rule.md)
- → [Data Mining vs ML](../01-fondasi/06-datamining-vs-ml.md)
