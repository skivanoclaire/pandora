"""
Feature Engineering pipeline untuk kehadiran harian.

Menghitung fitur per pegawai per hari dari sync_present_rekap:
- Velocity berangkat-pulang
- Velocity vs hari kemarin
- Jarak dari geofence terdekat
- Geofence match flag
- Deviasi waktu masuk/pulang vs jadwal ekspektasi
- Deviasi waktu masuk vs median personal & unit
- Snapshot status SIKARA
"""

import uuid
from datetime import date, datetime, time, timedelta
from math import atan2, cos, radians, sin, sqrt
from typing import Optional

import pandas as pd
from sqlalchemy import func, text
from sqlalchemy.orm import Session

from models.staging import (
    SyncPegPegawai,
    SyncPresentRekap,
    SyncRefBantuUnit,
    SyncRefLokasiUnit,
)
from models.analytics import FeatureKehadiranHarian
from services.jadwal_resolver import resolve_jadwal_batch


def haversine_km(lat1: float, lon1: float, lat2: float, lon2: float) -> float:
    """Jarak antara dua titik koordinat dalam kilometer (formula Haversine)."""
    R = 6371.0  # Radius bumi km
    lat1, lon1, lat2, lon2 = map(radians, [lat1, lon1, lat2, lon2])
    dlat = lat2 - lat1
    dlon = lon2 - lon1
    a = sin(dlat / 2) ** 2 + cos(lat1) * cos(lat2) * sin(dlon / 2) ** 2
    return R * 2 * atan2(sqrt(a), sqrt(1 - a))


def time_to_minutes(t: Optional[time]) -> Optional[float]:
    """Konversi time ke menit sejak midnight."""
    if t is None:
        return None
    return t.hour * 60 + t.minute + t.second / 60


def compute_velocity(
    lat1: Optional[float], lon1: Optional[float], t1: Optional[time],
    lat2: Optional[float], lon2: Optional[float], t2: Optional[time],
) -> Optional[float]:
    """Hitung kecepatan (km/jam) antara dua titik dan dua waktu."""
    if any(v is None for v in [lat1, lon1, lat2, lon2, t1, t2]):
        return None

    dist_km = haversine_km(float(lat1), float(lon1), float(lat2), float(lon2))

    m1 = time_to_minutes(t1)
    m2 = time_to_minutes(t2)
    if m1 is None or m2 is None:
        return None

    delta_hours = abs(m2 - m1) / 60
    if delta_hours < 0.01:  # Kurang dari ~36 detik
        return 99999.0 if dist_km > 0.01 else 0.0

    return dist_km / delta_hours


def compute_geofence_distance(
    lat: Optional[float], lon: Optional[float],
    lokasi_list: list[dict],
) -> tuple[Optional[float], str]:
    """Hitung jarak minimum ke lokasi geofence terdekat.

    Returns: (jarak_meter, match_flag)
    """
    if lat is None or lon is None or not lokasi_list:
        return None, "ambiguous"

    min_dist = float("inf")
    matched = False

    for lok in lokasi_list:
        if lok["latitude"] is None or lok["longitude"] is None:
            continue
        dist_km = haversine_km(float(lat), float(lon), float(lok["latitude"]), float(lok["longitude"]))
        dist_m = dist_km * 1000
        if dist_m < min_dist:
            min_dist = dist_m
        radius = lok.get("radius") or 100  # default 100m
        if dist_m <= radius:
            matched = True

    if min_dist == float("inf"):
        return None, "ambiguous"

    return round(min_dist, 2), "match" if matched else "no_match"


def run_feature_engineering(
    db: Session,
    tanggal: date,
    is_final: bool = False,
) -> dict:
    """
    Jalankan feature engineering untuk satu tanggal.

    Args:
        db: SQLAlchemy session
        tanggal: Tanggal yang diproses
        is_final: True jika pipeline bulanan (status_data_final=True)

    Returns:
        dict dengan statistik: total, inserted, skipped
    """
    run_id = str(uuid.uuid4())

    # 1. Ambil semua rekap untuk tanggal ini
    rekaps = db.query(SyncPresentRekap).filter(
        SyncPresentRekap.tanggal == tanggal
    ).all()

    if not rekaps:
        return {"total": 0, "inserted": 0, "skipped": 0, "run_id": run_id}

    # 2. Kumpulkan id_pegawai unik
    pegawai_ids = list({r.id_pegawai for r in rekaps})

    # 3. Resolve jadwal batch
    jadwal_map = resolve_jadwal_batch(db, pegawai_ids, tanggal)

    # 4. Pre-fetch lokasi geofence per unit pegawai
    pegawai_units = {
        p.id_pegawai: p.id_unit
        for p in db.query(SyncPegPegawai).filter(
            SyncPegPegawai.id_pegawai.in_(pegawai_ids)
        ).all()
    }

    # Pre-fetch pegawai bebas_lokasi
    bebas_lokasi_set = {
        p.id_pegawai
        for p in db.query(SyncPegPegawai).filter(
            SyncPegPegawai.id_pegawai.in_(pegawai_ids),
            SyncPegPegawai.bebas_lokasi == True,
        ).all()
    }

    unit_ids = set(pegawai_units.values()) - {None}
    lokasi_per_unit = _build_lokasi_per_unit(db, unit_ids)

    # 5. Pre-fetch rekap kemarin untuk velocity_vs_kemarin
    kemarin = tanggal - timedelta(days=1)
    rekap_kemarin = {
        r.id_pegawai: r
        for r in db.query(SyncPresentRekap).filter(
            SyncPresentRekap.tanggal == kemarin,
            SyncPresentRekap.id_pegawai.in_(pegawai_ids),
        ).all()
    }

    # 6. Pre-fetch median personal (30 hari terakhir)
    median_personal = _compute_median_personal(db, pegawai_ids, tanggal)

    # 7. Pre-fetch median unit
    median_unit = _compute_median_unit(db, unit_ids, tanggal)

    # 8. Hitung fitur per rekap
    now = datetime.utcnow()
    inserted = 0
    skipped = 0

    for rekap in rekaps:
        pid = rekap.id_pegawai
        jadwal = jadwal_map.get(pid)
        unit_id = pegawai_units.get(pid)
        lokasi_list = lokasi_per_unit.get(unit_id, []) if unit_id else []

        # Velocity berangkat → pulang (dalam satu hari)
        vel_bp = compute_velocity(
            rekap.lat_berangkat, rekap.long_berangkat, rekap.jam_masuk,
            rekap.lat_pulang, rekap.long_pulang, rekap.jam_pulang,
        )

        # Velocity vs kemarin (pulang kemarin → berangkat hari ini)
        rk = rekap_kemarin.get(pid)
        vel_kemarin = None
        if rk:
            vel_kemarin = compute_velocity(
                rk.lat_pulang, rk.long_pulang, rk.jam_pulang,
                rekap.lat_berangkat, rekap.long_berangkat, rekap.jam_masuk,
            )

        # Jarak dari geofence
        is_exempt = pid in bebas_lokasi_set
        if is_exempt:
            jarak_berangkat, match_berangkat = None, "exempt"
            jarak_pulang, match_pulang = None, "exempt"
        else:
            jarak_berangkat, match_berangkat = compute_geofence_distance(
                rekap.lat_berangkat, rekap.long_berangkat, lokasi_list,
            )
            jarak_pulang, match_pulang = compute_geofence_distance(
                rekap.lat_pulang, rekap.long_pulang, lokasi_list,
            )

        # Gabungkan match flags
        if is_exempt:
            geo_flag = "exempt"
        elif match_berangkat == "match" and match_pulang == "match":
            geo_flag = "match"
        elif match_berangkat == "no_match" or match_pulang == "no_match":
            geo_flag = "no_match"
        else:
            geo_flag = "ambiguous"

        # Deviasi temporal vs jadwal
        dev_masuk_jadwal = None
        dev_pulang_jadwal = None
        if jadwal and jadwal.jam_masuk and rekap.jam_masuk:
            dev_masuk_jadwal = round(
                time_to_minutes(rekap.jam_masuk) - time_to_minutes(jadwal.jam_masuk), 2
            )
        if jadwal and jadwal.jam_pulang and rekap.jam_pulang:
            dev_pulang_jadwal = round(
                time_to_minutes(rekap.jam_pulang) - time_to_minutes(jadwal.jam_pulang), 2
            )

        # Deviasi vs median personal
        dev_personal = None
        med_p = median_personal.get(pid)
        if med_p is not None and rekap.jam_masuk:
            dev_personal = round(time_to_minutes(rekap.jam_masuk) - med_p, 2)

        # Deviasi vs median unit
        dev_unit = None
        med_u = median_unit.get(unit_id) if unit_id else None
        if med_u is not None and rekap.jam_masuk:
            dev_unit = round(time_to_minutes(rekap.jam_masuk) - med_u, 2)

        # Alasan ketidakhadiran
        alasan = None
        if rekap.dl:
            alasan = "dl"
        elif rekap.dsp:
            alasan = "dsp"
        elif rekap.i:
            alasan = "i"
        elif rekap.s:
            alasan = "s"
        elif rekap.c:
            alasan = "c"

        # Upsert
        existing = db.query(FeatureKehadiranHarian).filter(
            FeatureKehadiranHarian.id_pegawai == pid,
            FeatureKehadiranHarian.tanggal == tanggal,
            FeatureKehadiranHarian.status_data_final == is_final,
        ).first()

        feature_data = dict(
            id_pegawai=pid,
            tanggal=tanggal,
            velocity_berangkat_pulang=vel_bp,
            velocity_vs_kemarin=vel_kemarin,
            jarak_dari_geofence_berangkat=jarak_berangkat,
            jarak_dari_geofence_pulang=jarak_pulang,
            geofence_match_flag=geo_flag,
            aplikasi_fake_gps_terdeteksi=False,  # Akan diisi rule engine
            id_group_efektif=jadwal.id_group if jadwal else None,
            sumber_jadwal=jadwal.tipe if jadwal else "undefined",
            jam_masuk_ekspektasi=jadwal.jam_masuk if jadwal else None,
            jam_pulang_ekspektasi=jadwal.jam_pulang if jadwal else None,
            deviasi_masuk_vs_jadwal_ekspektasi=dev_masuk_jadwal,
            deviasi_pulang_vs_jadwal_ekspektasi=dev_pulang_jadwal,
            deviasi_waktu_masuk_vs_median_personal=dev_personal,
            deviasi_waktu_masuk_vs_median_unit=dev_unit,
            status_sikara_tw=bool(rekap.tw),
            status_sikara_mkttw=bool(rekap.mkttw),
            status_sikara_pktw=bool(rekap.pktw),
            status_sikara_plc=bool(rekap.plc),
            status_sikara_tk=bool(rekap.tk),
            status_sikara_ta=bool(rekap.ta),
            alasan_ketidakhadiran=alasan,
            rule_compliance_flag="pending_status_finalization" if not is_final else None,
            status_data_final=is_final,
            computed_at_run_id=run_id,
            updated_at=now,
        )

        if existing:
            for k, v in feature_data.items():
                setattr(existing, k, v)
            skipped += 1  # counted as update, not new insert
        else:
            feature_data["created_at"] = now
            db.add(FeatureKehadiranHarian(**feature_data))
            inserted += 1

    db.commit()

    return {"total": len(rekaps), "inserted": inserted, "skipped": skipped, "run_id": run_id}


def _build_lokasi_per_unit(db: Session, unit_ids: set[int]) -> dict[int, list[dict]]:
    """Pre-fetch lokasi geofence per unit via ref_bantu_unit + ref_lokasi_unit."""
    if not unit_ids:
        return {}

    bantu = db.query(SyncRefBantuUnit).filter(
        SyncRefBantuUnit.id_unit.in_(unit_ids)
    ).all()

    lokasi_ids = {b.id_lokasi for b in bantu}
    if not lokasi_ids:
        return {}

    lokasi_all = {
        l.id_lokasi: {
            "id_lokasi": l.id_lokasi,
            "nama_lokasi": l.nama_lokasi,
            "latitude": float(l.latitude) if l.latitude else None,
            "longitude": float(l.longitude) if l.longitude else None,
            "radius": l.radius,
        }
        for l in db.query(SyncRefLokasiUnit).filter(
            SyncRefLokasiUnit.id_lokasi.in_(lokasi_ids),
            SyncRefLokasiUnit.aktif == True,
        ).all()
    }

    result: dict[int, list[dict]] = {}
    for b in bantu:
        lok = lokasi_all.get(b.id_lokasi)
        if lok:
            result.setdefault(b.id_unit, []).append(lok)

    return result


def _compute_median_personal(
    db: Session, pegawai_ids: list[int], tanggal: date, window_days: int = 30,
) -> dict[int, Optional[float]]:
    """Median jam_masuk per pegawai dalam N hari terakhir (dalam menit)."""
    start = tanggal - timedelta(days=window_days)

    rows = db.query(
        SyncPresentRekap.id_pegawai,
        SyncPresentRekap.jam_masuk,
    ).filter(
        SyncPresentRekap.id_pegawai.in_(pegawai_ids),
        SyncPresentRekap.tanggal.between(start, tanggal - timedelta(days=1)),
        SyncPresentRekap.jam_masuk.isnot(None),
    ).all()

    from collections import defaultdict
    import statistics

    per_peg: dict[int, list[float]] = defaultdict(list)
    for pid, jam in rows:
        per_peg[pid].append(time_to_minutes(jam))

    return {
        pid: statistics.median(vals) if vals else None
        for pid, vals in per_peg.items()
    }


def _compute_median_unit(
    db: Session, unit_ids: set[int], tanggal: date, window_days: int = 30,
) -> dict[int, Optional[float]]:
    """Median jam_masuk per unit dalam N hari terakhir (dalam menit)."""
    if not unit_ids:
        return {}

    start = tanggal - timedelta(days=window_days)

    # Join rekap dengan pegawai untuk dapat unit
    rows = db.query(
        SyncPegPegawai.id_unit,
        SyncPresentRekap.jam_masuk,
    ).join(
        SyncPresentRekap,
        SyncPresentRekap.id_pegawai == SyncPegPegawai.id_pegawai,
    ).filter(
        SyncPegPegawai.id_unit.in_(unit_ids),
        SyncPresentRekap.tanggal.between(start, tanggal - timedelta(days=1)),
        SyncPresentRekap.jam_masuk.isnot(None),
    ).all()

    from collections import defaultdict
    import statistics

    per_unit: dict[int, list[float]] = defaultdict(list)
    for uid, jam in rows:
        if uid is not None:
            per_unit[uid].append(time_to_minutes(jam))

    return {
        uid: statistics.median(vals) if vals else None
        for uid, vals in per_unit.items()
    }
