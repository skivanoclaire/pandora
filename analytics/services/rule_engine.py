"""
Rule Engine untuk deteksi anomali berbasis aturan.

Tingkat 1 — Physical impossibility (pipeline harian):
  - Velocity > 300 km/jam antar sesi berurutan
  - Absensi dua lokasi dalam menit yang sama
  - Koordinat di luar NKRI
  - Package Fake GPS terdeteksi (join dgn sync_fake_gps)
  - Absensi pada hari libur (sumber_jadwal = 'libur')
  - Koordinat berulang identik (indikasi Fake GPS statis)

Tingkat 2 — Rule violation formal (pipeline bulanan):
  - Geofence violation (absensi di lokasi tidak sesuai aturan hari/jam)
  - DL violation: absen sore di hari pertama DL, atau absen di hari DL berikutnya
"""

from datetime import date, datetime, timedelta
from typing import Optional

from sqlalchemy.orm import Session

from models.staging import (
    SyncPresentRekap, SyncPegPegawai, SyncRefBantuUnit, SyncRefLokasiUnit,
)
from models.analytics import AnomalyFlag, FeatureKehadiranHarian
from services.feature_engineering import haversine_km


# Batas NKRI (bounding box kasar)
NKRI_LAT_MIN = -11.0
NKRI_LAT_MAX = 6.5
NKRI_LON_MIN = 95.0
NKRI_LON_MAX = 141.5

VELOCITY_THRESHOLD_KMH = 300.0

# Deteksi koordinat berulang (Fake GPS statis)
# GPS hardware asli selalu punya jitter natural ~5-15 meter.
# Koordinat identik sampai 5 digit desimal (~1.1 meter) selama beberapa hari
# menunjukkan lokasi di-set manual, bukan dari hardware GPS.
COORD_REPEAT_WINDOW_DAYS = 7     # Periksa N hari terakhir
COORD_REPEAT_MIN_DAYS = 3        # Minimum hari berulang untuk trigger
COORD_PRECISION_DIGITS = 5       # Presisi pembulatan (5 digit ≈ 1.1 meter)


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

    for feat in features:
        pid = feat.id_pegawai
        rekap = rekaps.get(pid)
        if not rekap:
            continue

        # --- Rule 1: Velocity ekstrem ---
        if feat.velocity_berangkat_pulang is not None:
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

        if feat.velocity_vs_kemarin is not None:
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

        # --- Rule 2: Koordinat di luar NKRI ---
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

        # --- Rule 3: Absensi pada hari libur ---
        if feat.sumber_jadwal == "libur":
            has_masuk = rekap.jam_masuk is not None
            has_pulang = rekap.jam_pulang is not None
            if has_masuk or has_pulang:
                anomalies_found += _insert_anomaly(db, pid, tanggal, now,
                    jenis="geofence_violation",
                    confidence=0.90,
                    tingkat=1,
                    metode="rule_engine",
                    metadata={
                        "rule": "absensi_hari_libur",
                        "has_masuk": has_masuk,
                        "has_pulang": has_pulang,
                    },
                )
                details.append(f"pid={pid}: absensi pada hari libur")

        # --- Rule 4: Dua lokasi terlalu jauh dalam satu hari (berangkat vs pulang) ---
        if (rekap.lat_berangkat and rekap.long_berangkat
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

    # --- Rule 5: Koordinat berulang identik (Fake GPS statis) ---
    # GPS hardware selalu punya jitter natural beberapa meter.
    # Jika koordinat (dibulatkan ke ~1 meter) persis sama selama 3+ hari,
    # sangat mungkin koordinat di-set manual (Fake GPS).
    anomalies_found += _check_repeated_coordinates(
        db, tanggal, pegawai_ids=list(rekaps.keys()), now=now, details=details,
    )

    db.commit()
    return {"total_checked": len(features), "anomalies_found": anomalies_found, "details": details}


def _check_repeated_coordinates(
    db: Session,
    tanggal: date,
    pegawai_ids: list[int],
    now: datetime,
    details: list[str],
) -> int:
    """
    Deteksi koordinat absensi yang berulang di titik persis sama.

    Logika:
    - Ambil rekap N hari terakhir per pegawai
    - Bulatkan koordinat ke COORD_PRECISION_DIGITS (≈1.1 meter)
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
                key = (
                    round(float(r.lat_berangkat), COORD_PRECISION_DIGITS),
                    round(float(r.long_berangkat), COORD_PRECISION_DIGITS),
                )
                berangkat_coords[key] += 1
                berangkat_dates[key].append(str(r.tanggal))

        for coord, count in berangkat_coords.items():
            if count >= COORD_REPEAT_MIN_DAYS:
                # Pastikan hari ini termasuk dalam titik berulang ini
                rekap_today = next((r for r in rows if r.tanggal == tanggal), None)
                if rekap_today and rekap_today.lat_berangkat is not None:
                    today_key = (
                        round(float(rekap_today.lat_berangkat), COORD_PRECISION_DIGITS),
                        round(float(rekap_today.long_berangkat), COORD_PRECISION_DIGITS),
                    )
                    if today_key == coord:
                        anomalies += _insert_anomaly(db, pid, tanggal, now,
                            jenis="fake_gps",
                            confidence=min(0.95, 0.60 + (count / COORD_REPEAT_WINDOW_DAYS) * 0.35),
                            tingkat=1,
                            metode="rule_engine",
                            metadata={
                                "rule": "koordinat_berulang_identik",
                                "sesi": "berangkat",
                                "lat": coord[0],
                                "lon": coord[1],
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
                key = (
                    round(float(r.lat_pulang), COORD_PRECISION_DIGITS),
                    round(float(r.long_pulang), COORD_PRECISION_DIGITS),
                )
                pulang_coords[key] += 1
                pulang_dates[key].append(str(r.tanggal))

        for coord, count in pulang_coords.items():
            if count >= COORD_REPEAT_MIN_DAYS:
                rekap_today = next((r for r in rows if r.tanggal == tanggal), None)
                if rekap_today and rekap_today.lat_pulang is not None:
                    today_key = (
                        round(float(rekap_today.lat_pulang), COORD_PRECISION_DIGITS),
                        round(float(rekap_today.long_pulang), COORD_PRECISION_DIGITS),
                    )
                    if today_key == coord:
                        anomalies += _insert_anomaly(db, pid, tanggal, now,
                            jenis="fake_gps",
                            confidence=min(0.95, 0.60 + (count / COORD_REPEAT_WINDOW_DAYS) * 0.35),
                            tingkat=1,
                            metode="rule_engine",
                            metadata={
                                "rule": "koordinat_berulang_identik",
                                "sesi": "pulang",
                                "lat": coord[0],
                                "lon": coord[1],
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
        SyncPresentRekap.dl == True,
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
    # ==========================================
    features_bulan = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal.between(tgl_awal, tgl_akhir),
        FeatureKehadiranHarian.status_data_final == True,
        FeatureKehadiranHarian.geofence_match_flag == "no_match",
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
    # D. INVALIDASI anomaly lama yang ter-resolve
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
    features_no_match = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal.between(tgl_awal, tgl_akhir),
        FeatureKehadiranHarian.status_data_final == True,
        FeatureKehadiranHarian.geofence_match_flag == "no_match",
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
    existing = db.query(AnomalyFlag).filter(
        AnomalyFlag.id_pegawai == id_pegawai,
        AnomalyFlag.tanggal == tanggal,
        AnomalyFlag.jenis_anomali == jenis,
        AnomalyFlag.detail_metadata.contains({"rule": metadata.get("rule", "")}),
    ).first()

    if existing:
        return 0

    db.add(AnomalyFlag(
        id_pegawai=id_pegawai,
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

        if rekap and rekap.dsp:
            af.status_review = "false_positive_resolved_by_status_update"
            af.catatan_review = "Auto-resolved: status DSP diinput retroaktif"
            af.updated_at = datetime.utcnow()
