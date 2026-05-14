"""
Rule Engine untuk deteksi anomali berbasis aturan.

Tingkat 1 — Physical impossibility (pipeline harian):
  - Velocity > 300 km/jam antar sesi berurutan
  - Absensi dua lokasi dalam menit yang sama
  - Koordinat di luar NKRI
  - Package Fake GPS terdeteksi (join dgn sync_fake_gps)
  - Absensi pada hari libur — DINONAKTIFKAN (bukan anomali)
  - Koordinat berangkat dan pulang identik pada hari yang sama (Fake GPS)
  - Koordinat berulang identik antar hari (Fake GPS statis, presisi penuh)

Tingkat 2 — Rule violation formal (pipeline bulanan):
  - Geofence violation (absensi di lokasi tidak sesuai aturan hari/jam)
  - DL violation: absen sore di hari pertama DL, atau absen di hari DL berikutnya
  - Geofence rules: absensi di zona terbatas di luar hari/jam yang diizinkan
    (misal: Lapangan AGATIS hanya Senin apel pagi & Selasa senam)
"""

from datetime import date, datetime, timedelta
from typing import Optional

from sqlalchemy.orm import Session

from models.staging import (
    SyncPresentRekap, SyncPegPegawai, SyncRefBantuUnit, SyncRefLokasiUnit,
    SyncRefUnit, GeofenceZone, GeofenceRule,
)
from models.analytics import AnomalyFlag, FeatureKehadiranHarian
from services.feature_engineering import haversine_km, _is_wfa


# Batas NKRI (bounding box kasar)
NKRI_LAT_MIN = -11.0
NKRI_LAT_MAX = 6.5
NKRI_LON_MIN = 95.0
NKRI_LON_MAX = 141.5

VELOCITY_THRESHOLD_KMH = 300.0

# Deteksi koordinat berulang (Fake GPS statis)
# GPS hardware asli selalu punya jitter natural ~5-15 meter.
# Koordinat yang persis identik sampai desimal terakhir yang tercatat
# menunjukkan lokasi di-set manual, bukan dari hardware GPS.
# Perbandingan menggunakan presisi penuh dari data SIKARA (tanpa pembulatan).
COORD_REPEAT_WINDOW_DAYS = 7     # Periksa N hari terakhir
COORD_REPEAT_MIN_DAYS = 3        # Minimum hari berulang untuk trigger

# Whitelist nama lokasi presensi yang dikecualikan dari rule geofence Tingkat 2.
# Lokasi-lokasi ini adalah area gedung utama Kantor Gubernur Kaltara dengan
# koordinat geofence yang tumpang tindih / tidak akurat (banyak false positive).
LOKASI_WHITELIST_GEOFENCE = frozenset({
    "KANTOR GUBERNUR",
    "KANTOR GUBERNUR (KANAN)",
    "KANTOR GUBERNUR (TENGAH)",
    "KANTOR GUBERNUR BARU",
    "KANTOR GUBERNUR BARU (KANAN)",
    "KANTOR GUBERNUR GEDUNG BARU(TENGAH)",
    "GEDUNG TENGAH KANTOR GUBERNUR",
    "GEDUNG BELAKANG KANTOR GUBERNUR",
    "LAPANGAN APEL KANTOR GUBERNUR",
    "LOBBY UTAMA KANTOR GUBERNUR",
    "GEDUNG GADIS KANAN",
    "GEDUNG GADIS KIRI",
    "GEDUNG GADIS TENGAH",
    "GEDUNG TENGAH KANTOR GADIS 2",
    "GEDUNG BELAKANG KANTOR GADIS 2",
})


def _lokasi_whitelisted(rekap) -> bool:
    """True jika nama lokasi berangkat ATAU pulang ada di whitelist geofence."""
    if rekap is None:
        return False
    nama_b = (rekap.nama_lokasi_berangkat or "").strip().upper()
    nama_p = (rekap.nama_lokasi_pulang or "").strip().upper()
    return nama_b in LOKASI_WHITELIST_GEOFENCE or nama_p in LOKASI_WHITELIST_GEOFENCE


def run_rules_tingkat1(db: Session, tanggal: date) -> dict:
    """
    Jalankan deteksi Tingkat 1 untuk satu tanggal.
    Tidak bergantung pada status operator (DL/I/S/C belum dipercaya).

    Returns: dict statistik {total_checked, anomalies_found, details: [...]}
    """
    features = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal == tanggal,
    ).all()

    rekaps = {
        r.id_pegawai: r
        for r in db.query(SyncPresentRekap).filter(
            SyncPresentRekap.tanggal == tanggal,
        ).all()
    }

    now = datetime.utcnow()
    anomalies_found = 0
    details = []

    # Pre-fetch: pegawai fingerprint (sumber_presensi dari features)
    fingerprint_pids = {
        f.id_pegawai for f in features if f.sumber_presensi == "fingerprint"
    }

    # Pre-fetch: unit yang beroperasi 24 jam
    pegawai_units = {
        p.id_pegawai: p.id_unit
        for p in db.query(SyncPegPegawai).filter(
            SyncPegPegawai.id_pegawai.in_(list(rekaps.keys())),
        ).all()
    }
    unit_24_jam_ids = {
        u.id_unit
        for u in db.query(SyncRefUnit).filter(
            SyncRefUnit.operasi_24_jam == True,
        ).all()
    }

    for feat in features:
        pid = feat.id_pegawai
        rekap = rekaps.get(pid)
        if not rekap:
            continue

        is_fingerprint = pid in fingerprint_pids
        is_unit_24_jam = pegawai_units.get(pid) in unit_24_jam_ids
        is_wfa = _is_wfa(rekap.nama_lokasi_berangkat) or _is_wfa(rekap.nama_lokasi_pulang)

        # --- Rule 1: Velocity ekstrem ---
        # Skip untuk fingerprint (tidak ada koordinat) dan WFA (boleh dari manapun)
        if not is_fingerprint and not is_wfa and feat.velocity_berangkat_pulang is not None:
            vel = float(feat.velocity_berangkat_pulang)
            if vel > VELOCITY_THRESHOLD_KMH:
                anomalies_found += _insert_anomaly(db, pid, tanggal, now,
                    jenis="velocity_outlier",
                    confidence=min(1.0, vel / 1000),
                    tingkat=1,
                    metode="rule_engine",
                    metadata={"velocity_kmh": vel, "rule": "velocity_berangkat_pulang > 300"},
                )
                details.append(f"pid={pid}: velocity {vel:.0f} km/h")

        if not is_fingerprint and not is_wfa and feat.velocity_vs_kemarin is not None:
            vel = float(feat.velocity_vs_kemarin)
            if vel > VELOCITY_THRESHOLD_KMH:
                anomalies_found += _insert_anomaly(db, pid, tanggal, now,
                    jenis="velocity_outlier",
                    confidence=min(1.0, vel / 1000),
                    tingkat=1,
                    metode="rule_engine",
                    metadata={"velocity_kmh": vel, "rule": "velocity_vs_kemarin > 300"},
                )
                details.append(f"pid={pid}: velocity_kemarin {vel:.0f} km/h")

        # --- Rule 2: Koordinat di luar NKRI --- (skip WFA: boleh dari manapun)
        if not is_wfa:
            for prefix, lat_col, lon_col in [
                ("berangkat", rekap.lat_berangkat, rekap.long_berangkat),
                ("pulang", rekap.lat_pulang, rekap.long_pulang),
            ]:
                if lat_col is not None and lon_col is not None:
                    lat, lon = float(lat_col), float(lon_col)
                    if not (NKRI_LAT_MIN <= lat <= NKRI_LAT_MAX and NKRI_LON_MIN <= lon <= NKRI_LON_MAX):
                        anomalies_found += _insert_anomaly(db, pid, tanggal, now,
                            jenis="fake_gps",
                            confidence=0.99,
                            tingkat=1,
                            metode="rule_engine",
                            metadata={
                                "lat": lat, "lon": lon, "sesi": prefix,
                                "rule": "koordinat_luar_nkri",
                            },
                        )
                        details.append(f"pid={pid}: koordinat luar NKRI ({lat},{lon})")

        # --- Rule 3: Absensi pada hari libur --- DINONAKTIFKAN
        # Hari libur nasional yang tercatat di SIKARA tidak lagi di-flag.
        # Pegawai yang tetap hadir di hari libur bukan anomali — mereka
        # hadir secara sukarela atau ada kegiatan resmi.

        # --- Rule 4: Dua lokasi terlalu jauh dalam satu hari (berangkat vs pulang) ---
        # Skip WFA: boleh dari manapun, jarak jauh wajar
        if not is_wfa and (rekap.lat_berangkat and rekap.long_berangkat
                and rekap.lat_pulang and rekap.long_pulang
                and rekap.jam_masuk and rekap.jam_pulang):
            dist = haversine_km(
                float(rekap.lat_berangkat), float(rekap.long_berangkat),
                float(rekap.lat_pulang), float(rekap.long_pulang),
            )
            # Jarak > 100km antara lokasi berangkat dan pulang
            # dengan selisih waktu < 30 menit → fisik tidak mungkin
            from services.feature_engineering import time_to_minutes
            delta_min = abs(time_to_minutes(rekap.jam_pulang) - time_to_minutes(rekap.jam_masuk))
            if dist > 100 and delta_min < 30:
                anomalies_found += _insert_anomaly(db, pid, tanggal, now,
                    jenis="fake_gps",
                    confidence=0.95,
                    tingkat=1,
                    metode="rule_engine",
                    metadata={
                        "rule": "dua_lokasi_jauh_waktu_dekat",
                        "jarak_km": round(dist, 2),
                        "delta_menit": round(delta_min, 1),
                    },
                )
                details.append(f"pid={pid}: {dist:.0f}km dalam {delta_min:.0f} menit")

    # --- Rule 5: Koordinat berangkat vs pulang identik (Fake GPS statis) ---
    # GPS hardware selalu punya jitter natural beberapa meter.
    # Jika koordinat berangkat dan pulang pada hari yang sama persis identik
    # sampai desimal terakhir yang tercatat, menunjukkan fake GPS.
    # Skip pegawai fingerprint — mereka tidak punya koordinat
    for feat in features:
        pid = feat.id_pegawai
        if pid in fingerprint_pids:
            continue
        rekap = rekaps.get(pid)
        if not rekap:
            continue
        anomalies_found += _check_same_day_identical_coordinates(
            db, pid, tanggal, rekap, now, details,
        )

    # --- Rule 6: Koordinat berulang identik antar hari (Fake GPS statis) ---
    # Jika koordinat (presisi penuh tanpa pembulatan) persis sama selama 3+ hari,
    # sangat mungkin koordinat di-set manual (Fake GPS).
    # Skip pegawai fingerprint — mereka tidak punya koordinat
    non_fingerprint_pids = [pid for pid in rekaps.keys() if pid not in fingerprint_pids]
    anomalies_found += _check_repeated_coordinates(
        db, tanggal, pegawai_ids=non_fingerprint_pids, now=now, details=details,
    )

    db.commit()
    return {"total_checked": len(features), "anomalies_found": anomalies_found, "details": details}


def _check_same_day_identical_coordinates(
    db: Session,
    pid: int,
    tanggal: date,
    rekap: SyncPresentRekap,
    now: datetime,
    details: list[str],
) -> int:
    """
    Deteksi koordinat berangkat dan pulang yang persis identik pada hari yang sama.

    GPS hardware asli selalu punya jitter natural beberapa meter, sehingga
    koordinat pagi dan sore tidak mungkin persis sama sampai desimal terakhir.
    Perbandingan dilakukan pada presisi penuh dari data SIKARA tanpa pembulatan.
    """
    if (rekap.lat_berangkat is None or rekap.long_berangkat is None
            or rekap.lat_pulang is None or rekap.long_pulang is None):
        return 0

    # Bandingkan nilai Decimal langsung tanpa pembulatan
    # Numeric(10,7) dari database menjaga presisi penuh dari SIKARA
    if (rekap.lat_berangkat == rekap.lat_pulang
            and rekap.long_berangkat == rekap.long_pulang):
        details.append(
            f"pid={pid}: koordinat berangkat dan pulang identik "
            f"({rekap.lat_berangkat},{rekap.long_berangkat})"
        )
        return _insert_anomaly(db, pid, tanggal, now,
            jenis="fake_gps",
            confidence=0.95,
            tingkat=1,
            metode="rule_engine",
            metadata={
                "rule": "koordinat_berangkat_pulang_identik",
                "lat_berangkat": str(rekap.lat_berangkat),
                "lon_berangkat": str(rekap.long_berangkat),
                "lat_pulang": str(rekap.lat_pulang),
                "lon_pulang": str(rekap.long_pulang),
            },
        )
    return 0


def _check_repeated_coordinates(
    db: Session,
    tanggal: date,
    pegawai_ids: list[int],
    now: datetime,
    details: list[str],
) -> int:
    """
    Deteksi koordinat absensi yang berulang di titik persis sama antar hari.

    Logika:
    - Ambil rekap N hari terakhir per pegawai
    - Bandingkan koordinat pada presisi penuh dari SIKARA (tanpa pembulatan)
    - Jika titik yang sama muncul >= COORD_REPEAT_MIN_DAYS kali → flag
    - Cek terpisah untuk sesi berangkat dan sesi pulang
    """
    from collections import Counter

    window_start = tanggal - timedelta(days=COORD_REPEAT_WINDOW_DAYS - 1)
    anomalies = 0

    # Batch fetch: semua rekap dalam window untuk pegawai yang absen hari ini
    history = db.query(SyncPresentRekap).filter(
        SyncPresentRekap.id_pegawai.in_(pegawai_ids),
        SyncPresentRekap.tanggal.between(window_start, tanggal),
    ).all()

    # Kelompokkan per pegawai
    from collections import defaultdict
    per_pegawai: dict[int, list] = defaultdict(list)
    for r in history:
        per_pegawai[r.id_pegawai].append(r)

    for pid, rows in per_pegawai.items():
        # --- Cek sesi berangkat ---
        berangkat_coords = Counter()
        berangkat_dates: dict[str, list[str]] = defaultdict(list)
        for r in rows:
            if r.lat_berangkat is not None and r.long_berangkat is not None:
                # Gunakan presisi penuh dari SIKARA (tanpa pembulatan)
                key = (str(r.lat_berangkat), str(r.long_berangkat))
                berangkat_coords[key] += 1
                berangkat_dates[key].append(str(r.tanggal))

        for coord, count in berangkat_coords.items():
            if count >= COORD_REPEAT_MIN_DAYS:
                # Pastikan hari ini termasuk dalam titik berulang ini
                rekap_today = next((r for r in rows if r.tanggal == tanggal), None)
                if rekap_today and rekap_today.lat_berangkat is not None:
                    today_key = (str(rekap_today.lat_berangkat), str(rekap_today.long_berangkat))
                    if today_key == coord:
                        anomalies += _insert_anomaly(db, pid, tanggal, now,
                            jenis="fake_gps",
                            confidence=min(0.95, 0.60 + (count / COORD_REPEAT_WINDOW_DAYS) * 0.35),
                            tingkat=1,
                            metode="rule_engine",
                            metadata={
                                "rule": "koordinat_berulang_identik",
                                "sesi": "berangkat",
                                "lat": coord[0],  # presisi penuh
                                "lon": coord[1],  # presisi penuh
                                "jumlah_hari": count,
                                "window_hari": COORD_REPEAT_WINDOW_DAYS,
                                "tanggal_kemunculan": berangkat_dates[coord],
                            },
                        )
                        details.append(
                            f"pid={pid}: koordinat berangkat identik {count}x "
                            f"dalam {COORD_REPEAT_WINDOW_DAYS} hari ({coord[0]},{coord[1]})"
                        )

        # --- Cek sesi pulang ---
        pulang_coords = Counter()
        pulang_dates: dict[str, list[str]] = defaultdict(list)
        for r in rows:
            if r.lat_pulang is not None and r.long_pulang is not None:
                # Gunakan presisi penuh dari SIKARA (tanpa pembulatan)
                key = (str(r.lat_pulang), str(r.long_pulang))
                pulang_coords[key] += 1
                pulang_dates[key].append(str(r.tanggal))

        for coord, count in pulang_coords.items():
            if count >= COORD_REPEAT_MIN_DAYS:
                rekap_today = next((r for r in rows if r.tanggal == tanggal), None)
                if rekap_today and rekap_today.lat_pulang is not None:
                    today_key = (str(rekap_today.lat_pulang), str(rekap_today.long_pulang))
                    if today_key == coord:
                        anomalies += _insert_anomaly(db, pid, tanggal, now,
                            jenis="fake_gps",
                            confidence=min(0.95, 0.60 + (count / COORD_REPEAT_WINDOW_DAYS) * 0.35),
                            tingkat=1,
                            metode="rule_engine",
                            metadata={
                                "rule": "koordinat_berulang_identik",
                                "sesi": "pulang",
                                "lat": coord[0],  # presisi penuh
                                "lon": coord[1],  # presisi penuh
                                "jumlah_hari": count,
                                "window_hari": COORD_REPEAT_WINDOW_DAYS,
                                "tanggal_kemunculan": pulang_dates[coord],
                            },
                        )
                        details.append(
                            f"pid={pid}: koordinat pulang identik {count}x "
                            f"dalam {COORD_REPEAT_WINDOW_DAYS} hari ({coord[0]},{coord[1]})"
                        )

    return anomalies


def run_rules_tingkat2(db: Session, bulan: int, tahun: int) -> dict:
    """
    Jalankan deteksi Tingkat 2 untuk satu bulan (setelah status SIKARA final).
    Termasuk DL violations dan geofence compliance.

    Returns: dict statistik
    """
    from calendar import monthrange

    _, last_day = monthrange(tahun, bulan)
    tgl_awal = date(tahun, bulan, 1)
    tgl_akhir = date(tahun, bulan, last_day)

    now = datetime.utcnow()
    anomalies_found = 0
    details = []

    # ==========================================
    # A. DL VIOLATIONS (DESIGN.md section 7.2.2)
    # ==========================================
    rekaps_dl = db.query(SyncPresentRekap).filter(
        SyncPresentRekap.tanggal.between(tgl_awal, tgl_akhir),
        SyncPresentRekap.dl == 1,
    ).order_by(
        SyncPresentRekap.id_pegawai,
        SyncPresentRekap.tanggal,
    ).all()

    # Kelompokkan DL per pegawai, identifikasi periode kontinyu
    dl_per_pegawai: dict[int, list[date]] = {}
    for r in rekaps_dl:
        dl_per_pegawai.setdefault(r.id_pegawai, []).append(r.tanggal)

    for pid, tanggal_list in dl_per_pegawai.items():
        tanggal_list.sort()
        periodes = _group_continuous_dates(tanggal_list)

        for periode in periodes:
            t1 = periode[0]  # Hari pertama DL

            for tgl in periode:
                rekap = next(
                    (r for r in rekaps_dl if r.id_pegawai == pid and r.tanggal == tgl),
                    None,
                )
                if not rekap:
                    continue

                if tgl == t1:
                    # T1: boleh masuk pagi, TIDAK boleh pulang sore
                    if rekap.jam_pulang is not None or rekap.lat_pulang is not None:
                        anomalies_found += _insert_anomaly(db, pid, tgl, now,
                            jenis="geofence_violation",
                            confidence=0.85,
                            tingkat=2,
                            metode="rule_engine",
                            metadata={
                                "rule": "dl_violation_sore_t1",
                                "periode_dl": [str(d) for d in periode],
                            },
                        )
                        details.append(f"pid={pid}: absen sore di T1 DL ({tgl})")
                else:
                    # T2+: TIDAK boleh absen sama sekali
                    has_masuk = rekap.jam_masuk is not None or rekap.lat_berangkat is not None
                    has_pulang = rekap.jam_pulang is not None or rekap.lat_pulang is not None
                    if has_masuk or has_pulang:
                        anomalies_found += _insert_anomaly(db, pid, tgl, now,
                            jenis="geofence_violation",
                            confidence=0.85,
                            tingkat=2,
                            metode="rule_engine",
                            metadata={
                                "rule": "dl_violation_hari_lanjutan",
                                "has_masuk": has_masuk,
                                "has_pulang": has_pulang,
                                "periode_dl": [str(d) for d in periode],
                            },
                        )
                        details.append(f"pid={pid}: absen di DL lanjutan ({tgl})")

    # ==========================================
    # B. GEOFENCE COMPLIANCE (dari features)
    # Skip pegawai fingerprint — tidak ada koordinat untuk validasi geofence
    # ==========================================
    features_bulan = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal.between(tgl_awal, tgl_akhir),
        FeatureKehadiranHarian.status_data_final == True,
        FeatureKehadiranHarian.geofence_match_flag == "no_match",
        FeatureKehadiranHarian.sumber_presensi != "fingerprint",
    ).all()

    for feat in features_bulan:
        pid = feat.id_pegawai
        rekap = db.query(SyncPresentRekap).filter(
            SyncPresentRekap.id_pegawai == pid,
            SyncPresentRekap.tanggal == feat.tanggal,
        ).first()

        # Skip jika DSP (dispensasi = whitelist penuh)
        if rekap and rekap.dsp:
            continue

        # Skip jika DL (sudah di-handle di atas)
        if rekap and rekap.dl:
            continue

        # Skip lokasi gedung utama Kantor Gubernur (geofence tidak akurat)
        if _lokasi_whitelisted(rekap):
            continue

        anomalies_found += _insert_anomaly(db, pid, feat.tanggal, now,
            jenis="geofence_violation",
            confidence=0.75,
            tingkat=2,
            metode="rule_engine",
            metadata={
                "rule": "geofence_no_match_final",
                "jarak_berangkat_m": float(feat.jarak_dari_geofence_berangkat) if feat.jarak_dari_geofence_berangkat else None,
                "jarak_pulang_m": float(feat.jarak_dari_geofence_pulang) if feat.jarak_dari_geofence_pulang else None,
            },
        )
        details.append(f"pid={pid}: geofence no_match ({feat.tanggal})")

    # ==========================================
    # C. ABSEN DI LOKASI UNIT LAIN (bukan unit sendiri)
    # ==========================================
    anomalies_found += _check_absen_lokasi_unit_lain(
        db, tgl_awal, tgl_akhir, now, details,
    )

    # ==========================================
    # D. GEOFENCE RULES — validasi hari/jam zona terbatas
    # (misal: Lapangan AGATIS hanya Senin apel pagi & Selasa senam)
    # ==========================================
    anomalies_found += _check_geofence_rules_compliance(
        db, tgl_awal, tgl_akhir, now, details,
    )

    # ==========================================
    # E. INVALIDASI anomaly lama yang ter-resolve
    # ==========================================
    _invalidate_resolved_anomalies(db, tgl_awal, tgl_akhir)

    db.commit()
    return {"anomalies_found": anomalies_found, "details": details}


def _check_absen_lokasi_unit_lain(
    db: Session,
    tgl_awal: date,
    tgl_akhir: date,
    now: datetime,
    details: list[str],
) -> int:
    """
    Deteksi pegawai yang absen di lokasi milik unit lain.

    Alur:
    1. Ambil semua rekap bulan ini yang geofence_match_flag = 'no_match'
       (artinya koordinat tidak cocok dengan lokasi unit sendiri)
    2. Untuk tiap rekap tersebut, cek apakah koordinat cocok dengan
       lokasi unit LAIN → jika ya, berarti pegawai "nebeng" absen
       di lokasi yang bukan haknya
    3. Skip pegawai bebas_lokasi dan status DSP/DL

    Ini berbeda dari geofence_no_match_final:
    - no_match = tidak cocok di mana pun
    - lokasi_unit_lain = cocok, tapi di lokasi unit yang salah
    """
    from collections import defaultdict

    anomalies = 0

    # Ambil features yang no_match (sudah pasti bukan lokasi unit sendiri)
    # Skip fingerprint — tidak ada koordinat untuk validasi
    features_no_match = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal.between(tgl_awal, tgl_akhir),
        FeatureKehadiranHarian.status_data_final == True,
        FeatureKehadiranHarian.geofence_match_flag == "no_match",
        FeatureKehadiranHarian.sumber_presensi != "fingerprint",
    ).all()

    if not features_no_match:
        return 0

    pegawai_ids = list({f.id_pegawai for f in features_no_match})

    # Pre-fetch data pegawai (unit + bebas_lokasi)
    pegawai_map = {
        p.id_pegawai: p
        for p in db.query(SyncPegPegawai).filter(
            SyncPegPegawai.id_pegawai.in_(pegawai_ids),
        ).all()
    }

    # Pre-fetch SEMUA lokasi (untuk cross-check terhadap unit lain)
    all_lokasi = {
        l.id_lokasi: l
        for l in db.query(SyncRefLokasiUnit).filter(
            SyncRefLokasiUnit.aktif == True,
        ).all()
    }

    # Pre-fetch SEMUA mapping unit → lokasi
    all_bantu = db.query(SyncRefBantuUnit).all()
    lokasi_to_units: dict[int, set[int]] = defaultdict(set)
    for b in all_bantu:
        lokasi_to_units[b.id_lokasi].add(b.id_unit)

    for feat in features_no_match:
        pid = feat.id_pegawai
        peg = pegawai_map.get(pid)
        if not peg:
            continue

        # Skip bebas_lokasi
        if peg.bebas_lokasi:
            continue

        unit_sendiri = peg.id_unit

        # Ambil rekap untuk cek status & koordinat
        rekap = db.query(SyncPresentRekap).filter(
            SyncPresentRekap.id_pegawai == pid,
            SyncPresentRekap.tanggal == feat.tanggal,
        ).first()

        if not rekap:
            continue

        # Skip DSP dan DL
        if rekap.dsp or rekap.dl:
            continue

        # Skip lokasi gedung utama Kantor Gubernur (geofence tidak akurat)
        if _lokasi_whitelisted(rekap):
            continue

        # Cek tiap sesi: apakah koordinat cocok dengan lokasi unit LAIN
        for sesi, lat, lon in [
            ("berangkat", rekap.lat_berangkat, rekap.long_berangkat),
            ("pulang", rekap.lat_pulang, rekap.long_pulang),
        ]:
            if lat is None or lon is None:
                continue

            lat_f, lon_f = float(lat), float(lon)

            # Cek semua lokasi — cari yang cocok
            for lok_id, lok in all_lokasi.items():
                if lok.latitude is None or lok.longitude is None:
                    continue

                radius = lok.radius or 100
                dist_m = haversine_km(lat_f, lon_f, float(lok.latitude), float(lok.longitude)) * 1000

                if dist_m <= radius:
                    # Koordinat cocok dengan lokasi ini — cek apakah milik unit lain
                    unit_pemilik = lokasi_to_units.get(lok_id, set())

                    if unit_sendiri and unit_sendiri not in unit_pemilik and unit_pemilik:
                        anomalies += _insert_anomaly(db, pid, feat.tanggal, now,
                            jenis="geofence_violation",
                            confidence=0.80,
                            tingkat=2,
                            metode="rule_engine",
                            metadata={
                                "rule": "absen_lokasi_unit_lain",
                                "sesi": sesi,
                                "lat": lat_f,
                                "lon": lon_f,
                                "lokasi_terdeteksi": lok.nama_lokasi,
                                "id_lokasi": lok_id,
                                "unit_pemilik_lokasi": list(unit_pemilik),
                                "unit_pegawai": unit_sendiri,
                                "jarak_meter": round(dist_m, 1),
                            },
                        )
                        details.append(
                            f"pid={pid}: absen {sesi} di lokasi "
                            f"'{lok.nama_lokasi}' milik unit {unit_pemilik}, "
                            f"bukan unit sendiri ({unit_sendiri}) ({feat.tanggal})"
                        )
                        break  # Satu match per sesi cukup

    return anomalies


def _check_geofence_rules_compliance(
    db: Session,
    tgl_awal: date,
    tgl_akhir: date,
    now: datetime,
    details: list[str],
) -> int:
    """
    Deteksi absensi di zona geofence yang punya aturan hari/jam tertentu,
    tapi dilakukan di luar hari/jam yang diizinkan.

    Contoh: Lapangan AGATIS hanya valid Senin (apel pagi) & Selasa (senam).
    Pegawai yang absen masuk di AGATIS hari Rabu → anomali.

    Alur:
    1. Ambil semua geofence_zones aktif yang punya geofence_rules
    2. Untuk setiap tanggal dalam range, ambil rekap yang punya koordinat
    3. Cek apakah koordinat jatuh di dalam zona (lat_center/radius)
    4. Jika ya, cek apakah hari dan jam sesuai geofence_rules
    5. Jika tidak sesuai → flag anomali
    """
    from collections import defaultdict

    anomalies = 0

    # 1. Ambil semua zona aktif
    zones = db.query(GeofenceZone).filter(
        GeofenceZone.aktif == True,
    ).all()

    if not zones:
        return 0

    zone_ids = [z.id for z in zones]

    # 2. Ambil rules KHUSUS (bukan jam_kerja) untuk zona-zona ini
    # Zona dengan hanya 'jam_kerja' = kantor biasa, tidak perlu dicek.
    # Yang dicek: zona dengan kegiatan terbatas (apel_pagi, senam_pagi, dll)
    rules = db.query(GeofenceRule).filter(
        GeofenceRule.geofence_zone_id.in_(zone_ids),
        GeofenceRule.jenis_kegiatan != "jam_kerja",
    ).all()

    if not rules:
        return 0

    # Kelompokkan rules per zona
    rules_per_zone: dict[int, list] = defaultdict(list)
    for rule in rules:
        rules_per_zone[rule.geofence_zone_id].append(rule)

    # Hanya proses zona yang punya rules khusus (bukan jam_kerja)
    zones_with_rules = [z for z in zones if z.id in rules_per_zone]
    if not zones_with_rules:
        return 0

    # 3. Ambil semua rekap dalam periode yang punya koordinat berangkat
    rekaps = db.query(SyncPresentRekap).filter(
        SyncPresentRekap.tanggal.between(tgl_awal, tgl_akhir),
    ).all()

    # Pre-fetch pegawai unit untuk scope checking
    pegawai_ids = list({r.id_pegawai for r in rekaps})
    pegawai_units = {
        p.id_pegawai: p.id_unit
        for p in db.query(SyncPegPegawai).filter(
            SyncPegPegawai.id_pegawai.in_(pegawai_ids),
        ).all()
    } if pegawai_ids else {}

    # Pre-fetch: pegawai fingerprint (tidak punya koordinat)
    pegawai_pernah_coords = set()
    if pegawai_ids:
        rows_coords = db.query(SyncPresentRekap.id_pegawai).filter(
            SyncPresentRekap.id_pegawai.in_(pegawai_ids),
            SyncPresentRekap.lat_berangkat.isnot(None),
        ).distinct().all()
        pegawai_pernah_coords = {r[0] for r in rows_coords}

    # Pre-fetch geofence_match_flag per (pid, tanggal) — kalau pegawai sudah
    # 'match' dengan geofence unit-nya, dia VALID absen di sana walaupun
    # koordinatnya kebetulan jatuh di radius zona terbatas yang tumpang tindih
    # (mis. LAPANGAN AGATIS overlap dengan KANTOR GUBERNUR).
    feature_match_flags: dict[tuple, str] = {}
    if pegawai_ids:
        feat_rows = db.query(
            FeatureKehadiranHarian.id_pegawai,
            FeatureKehadiranHarian.tanggal,
            FeatureKehadiranHarian.geofence_match_flag,
        ).filter(
            FeatureKehadiranHarian.id_pegawai.in_(pegawai_ids),
            FeatureKehadiranHarian.tanggal.between(tgl_awal, tgl_akhir),
            FeatureKehadiranHarian.status_data_final == True,
        ).all()
        for pid_f, tgl_f, flag in feat_rows:
            feature_match_flags[(pid_f, tgl_f)] = flag

    for rekap in rekaps:
        pid = rekap.id_pegawai

        # Skip fingerprint — tidak ada koordinat
        if pid not in pegawai_pernah_coords:
            continue

        # Skip DSP dan DL
        if rekap.dsp or rekap.dl:
            continue

        # Skip lokasi gedung utama Kantor Gubernur (geofence tidak akurat)
        if _lokasi_whitelisted(rekap):
            continue

        # Skip kalau pegawai sudah 'match' dengan geofence unit valid-nya
        # (zona terbatas overlap dengan zona kantor unit pegawai)
        if feature_match_flags.get((pid, rekap.tanggal)) == "match":
            continue

        unit_pegawai = pegawai_units.get(pid)

        # Cek tiap sesi (berangkat dan pulang)
        for sesi, lat, lon, jam in [
            ("berangkat", rekap.lat_berangkat, rekap.long_berangkat, rekap.jam_masuk),
            ("pulang", rekap.lat_pulang, rekap.long_pulang, rekap.jam_pulang),
        ]:
            if lat is None or lon is None:
                continue

            lat_f, lon_f = float(lat), float(lon)

            for zone in zones_with_rules:
                if zone.lat_center is None or zone.long_center is None:
                    continue

                # Cek apakah koordinat dalam radius zona
                radius_m = zone.radius_meter or 100
                dist_m = haversine_km(
                    lat_f, lon_f,
                    float(zone.lat_center), float(zone.long_center),
                ) * 1000

                if dist_m > radius_m:
                    continue

                # Koordinat dalam zona — cek apakah ada rule yang mengizinkan
                zone_rules = rules_per_zone[zone.id]

                # Konversi hari: Python weekday() = 0=Monday ... 6=Sunday
                # Database hari_of_week = 0=Minggu, 1=Senin, ..., 6=Sabtu
                py_weekday = rekap.tanggal.weekday()  # 0=Mon, 6=Sun
                db_day = (py_weekday + 1) % 7  # konversi ke 0=Minggu, 1=Senin, ...

                allowed = False
                for rule in zone_rules:
                    # Cek berlaku_mulai / berlaku_sampai
                    if rule.berlaku_mulai and rekap.tanggal < rule.berlaku_mulai:
                        continue
                    if rule.berlaku_sampai and rekap.tanggal > rule.berlaku_sampai:
                        continue

                    # Cek scope unit (jika diset)
                    if rule.unit_kerja_ids and unit_pegawai:
                        if unit_pegawai not in rule.unit_kerja_ids:
                            continue

                    # Cek hari
                    if rule.hari_of_week != db_day:
                        continue

                    # Cek jam (jika pegawai punya data jam)
                    if jam is not None and rule.jam_mulai and rule.jam_selesai:
                        if rule.jam_mulai <= jam <= rule.jam_selesai:
                            allowed = True
                            break
                    else:
                        # Tidak ada data jam atau rule tidak punya jam → hari cocok = allowed
                        allowed = True
                        break

                if not allowed:
                    # Kumpulkan hari-hari yang diizinkan untuk pesan detail
                    hari_nama = {
                        0: "Minggu", 1: "Senin", 2: "Selasa", 3: "Rabu",
                        4: "Kamis", 5: "Jumat", 6: "Sabtu",
                    }
                    hari_allowed = sorted({r.hari_of_week for r in zone_rules})
                    hari_str = ", ".join(hari_nama.get(h, str(h)) for h in hari_allowed)
                    hari_absen = hari_nama.get(db_day, str(db_day))

                    kegiatan = list({r.jenis_kegiatan for r in zone_rules if r.jenis_kegiatan})

                    anomalies += _insert_anomaly(db, pid, rekap.tanggal, now,
                        jenis="geofence_violation",
                        confidence=0.85,
                        tingkat=2,
                        metode="rule_engine",
                        metadata={
                            "rule": "geofence_rule_hari_jam",
                            "sesi": sesi,
                            "zona": zone.nama_zona,
                            "zona_id": zone.id,
                            "hari_absen": hari_absen,
                            "hari_diizinkan": hari_str,
                            "jenis_kegiatan": kegiatan,
                            "jarak_meter": round(dist_m, 1),
                        },
                    )
                    details.append(
                        f"pid={pid}: absen {sesi} di '{zone.nama_zona}' "
                        f"hari {hari_absen} (hanya boleh {hari_str}) "
                        f"({rekap.tanggal})"
                    )
                    break  # Satu flag per sesi per zona cukup

    return anomalies


def _group_continuous_dates(dates: list[date]) -> list[list[date]]:
    """Kelompokkan tanggal-tanggal yang berurutan menjadi periode kontinyu."""
    if not dates:
        return []

    periodes = [[dates[0]]]
    for i in range(1, len(dates)):
        if (dates[i] - dates[i - 1]).days == 1:
            periodes[-1].append(dates[i])
        else:
            periodes.append([dates[i]])

    return periodes


def _lookup_pegawai_snapshot(db: Session, id_pegawai: int) -> tuple:
    """Lookup nama/nip/nama_unit pegawai untuk denormalisasi ke anomaly_flags.

    Hasil di-cache di session.info supaya tidak N+1 dalam satu pipeline run.
    Jika pegawai tidak ditemukan (mis. id_pegawai=0), return (None, None, None).
    """
    cache = db.info.setdefault("_pegawai_snapshot_cache", {})
    if id_pegawai in cache:
        return cache[id_pegawai]

    row = (
        db.query(SyncPegPegawai.nama, SyncPegPegawai.nip, SyncRefUnit.nama_unit)
        .outerjoin(SyncRefUnit, SyncPegPegawai.id_unit == SyncRefUnit.id_unit)
        .filter(SyncPegPegawai.id_pegawai == id_pegawai)
        .first()
    )
    snapshot = (row.nama, row.nip, row.nama_unit) if row else (None, None, None)
    cache[id_pegawai] = snapshot
    return snapshot


def _insert_anomaly(
    db: Session,
    id_pegawai: int,
    tanggal: date,
    now: datetime,
    jenis: str,
    confidence: float,
    tingkat: int,
    metode: str,
    metadata: dict,
) -> int:
    """Insert anomaly flag jika belum ada duplikat. Returns 1 jika inserted, 0 jika skip."""
    if not id_pegawai or id_pegawai <= 0:
        return 0

    existing = db.query(AnomalyFlag).filter(
        AnomalyFlag.id_pegawai == id_pegawai,
        AnomalyFlag.tanggal == tanggal,
        AnomalyFlag.jenis_anomali == jenis,
        AnomalyFlag.detail_metadata.contains({"rule": metadata.get("rule", "")}),
    ).first()

    if existing:
        return 0

    nama, nip, nama_unit = _lookup_pegawai_snapshot(db, id_pegawai)

    db.add(AnomalyFlag(
        id_pegawai=id_pegawai,
        nama_snapshot=nama,
        nip_snapshot=nip,
        nama_unit_snapshot=nama_unit,
        tanggal=tanggal,
        jenis_anomali=jenis,
        confidence=confidence,
        tingkat=tingkat,
        metode_deteksi=metode,
        model_version="rule_v1.0",
        detail_metadata=metadata,
        status_review="belum_direview",
        detected_at=now,
        created_at=now,
        updated_at=now,
    ))
    return 1


def _invalidate_resolved_anomalies(db: Session, tgl_awal: date, tgl_akhir: date):
    """
    Tandai anomaly flags lama yang ter-resolve oleh status baru.
    Contoh: Tingkat 1 'absen di weekend' ternyata DSP retroaktif.
    """
    # Cari anomaly belum_direview yang pegawainya sekarang punya status DSP
    pending = db.query(AnomalyFlag).filter(
        AnomalyFlag.tanggal.between(tgl_awal, tgl_akhir),
        AnomalyFlag.status_review == "belum_direview",
    ).all()

    for af in pending:
        rekap = db.query(SyncPresentRekap).filter(
            SyncPresentRekap.id_pegawai == af.id_pegawai,
            SyncPresentRekap.tanggal == af.tanggal,
        ).first()

        if not rekap:
            continue

        if rekap.dsp:
            af.status_review = "false_positive_resolved_by_status_update"
            af.catatan_review = "Auto-resolved: status DSP diinput retroaktif"
            af.updated_at = datetime.utcnow()
            continue

        # Auto-resolve anomali berbasis lokasi untuk hari WFA
        # WFA = boleh dari manapun, hanya fake GPS (koordinat identik) yang tetap berlaku
        is_wfa = _is_wfa(rekap.nama_lokasi_berangkat) or _is_wfa(rekap.nama_lokasi_pulang)
        if is_wfa:
            rule = (af.detail_metadata or {}).get("rule", "")
            should_resolve = False

            if af.jenis_anomali == "geofence_violation":
                should_resolve = True
            elif af.jenis_anomali == "velocity_outlier":
                should_resolve = True
            elif af.jenis_anomali == "fake_gps" and rule in ("koordinat_luar_nkri", "dua_lokasi_jauh_waktu_dekat"):
                should_resolve = True
            elif af.jenis_anomali == "combination":
                should_resolve = True

            if should_resolve:
                # WFA = pengecualian kebijakan, bukan kegagalan model (model BENAR
                # mendeteksi outlier; kebijakan WFA yang mengizinkannya).
                af.status_review = "policy_exception"
                af.catatan_review = "Auto-resolved: hari WFA (Work From Anywhere)"
                af.updated_at = datetime.utcnow()
