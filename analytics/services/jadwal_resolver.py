"""
Resolusi jadwal efektif untuk (id_pegawai, tanggal).

Port dari Laravel JadwalResolverService — mengikuti algoritma DESIGN.md section 5.4:
1. Cek hari libur (present_libur)
2. Cek override per tanggal (present_masuk)
3. Cari grup jadwal efektif (present_presensi)
4. Cari template jadwal (present_group)
5. Ambil jam berdasarkan day-of-week
"""

from dataclasses import dataclass
from datetime import date, time
from typing import Optional

from sqlalchemy.orm import Session

from models.staging import SyncPresentGroup, SyncPresentLibur, SyncPresentMasuk, SyncPresentPresensi


DAY_MAP = {
    0: "sen",  # Monday
    1: "sel",
    2: "rab",
    3: "kam",
    4: "jum",
    5: "sab",
    6: "min",  # Sunday
}


@dataclass
class JadwalResult:
    tipe: str  # libur | override | template | undefined
    jam_masuk: Optional[time]
    jam_pulang: Optional[time]
    id_group: Optional[int]
    keterangan: str


def resolve_jadwal(db: Session, id_pegawai: int, tanggal: date) -> JadwalResult:
    """Resolve jadwal efektif untuk satu (id_pegawai, tanggal)."""

    # 1. Cek hari libur
    libur = db.query(SyncPresentLibur).filter(
        SyncPresentLibur.tanggal == tanggal
    ).first()

    if libur:
        return JadwalResult(
            tipe="libur",
            jam_masuk=None,
            jam_pulang=None,
            id_group=None,
            keterangan=libur.keterangan or "Hari libur",
        )

    # 2. Cari grup jadwal efektif
    assignment = db.query(SyncPresentPresensi).filter(
        SyncPresentPresensi.id_pegawai == id_pegawai,
        SyncPresentPresensi.cdate <= tanggal,
    ).order_by(SyncPresentPresensi.cdate.desc()).first()

    if not assignment:
        return JadwalResult(
            tipe="undefined",
            jam_masuk=None,
            jam_pulang=None,
            id_group=None,
            keterangan="Pegawai tidak memiliki assignment grup jadwal",
        )

    id_group = assignment.id_group

    # 3. Cek override per tanggal
    override = db.query(SyncPresentMasuk).filter(
        SyncPresentMasuk.tanggal == tanggal,
        SyncPresentMasuk.id_group == id_group,
    ).first()

    if override:
        return JadwalResult(
            tipe="override",
            jam_masuk=override.masuk,
            jam_pulang=override.pulang,
            id_group=id_group,
            keterangan=f"Override jadwal: {override.status or ''}",
        )

    # 4. Cari template jadwal
    template = db.query(SyncPresentGroup).filter(
        SyncPresentGroup.id_group == id_group,
        SyncPresentGroup.berlaku <= tanggal,
        SyncPresentGroup.berakhir >= tanggal,
    ).first()

    if not template:
        # Fallback: template terbaru tanpa filter tanggal
        template = db.query(SyncPresentGroup).filter(
            SyncPresentGroup.id_group == id_group,
        ).order_by(SyncPresentGroup.berlaku.desc()).first()

        if not template:
            return JadwalResult(
                tipe="undefined",
                jam_masuk=None,
                jam_pulang=None,
                id_group=id_group,
                keterangan=f"Template jadwal tidak ditemukan untuk grup {id_group}",
            )

    # 5. Ambil jam berdasarkan hari (Python: Monday=0 .. Sunday=6)
    day_idx = tanggal.weekday()  # 0=Monday, 6=Sunday
    prefix = DAY_MAP.get(day_idx)

    if not prefix:
        return JadwalResult(
            tipe="undefined",
            jam_masuk=None,
            jam_pulang=None,
            id_group=id_group,
            keterangan="Day-of-week tidak valid",
        )

    jam_masuk = getattr(template, f"{prefix}_awal", None)
    jam_pulang = getattr(template, f"{prefix}_akhir", None)

    if jam_masuk is None:
        return JadwalResult(
            tipe="libur",
            jam_masuk=None,
            jam_pulang=None,
            id_group=id_group,
            keterangan=f"Tidak dijadwalkan kerja pada hari ini (grup: {template.nama_group})",
        )

    return JadwalResult(
        tipe="template",
        jam_masuk=jam_masuk,
        jam_pulang=jam_pulang,
        id_group=id_group,
        keterangan=template.nama_group,
    )


def resolve_jadwal_batch(db: Session, pegawai_ids: list[int], tanggal: date) -> dict[int, JadwalResult]:
    """Resolve jadwal untuk banyak pegawai sekaligus pada satu tanggal.

    Optimasi: pre-fetch libur dan override agar tidak N+1 query.
    """
    # Cek libur dulu — berlaku untuk semua pegawai
    libur = db.query(SyncPresentLibur).filter(
        SyncPresentLibur.tanggal == tanggal
    ).first()

    if libur:
        result = JadwalResult(
            tipe="libur", jam_masuk=None, jam_pulang=None,
            id_group=None, keterangan=libur.keterangan or "Hari libur",
        )
        return {pid: result for pid in pegawai_ids}

    # Pre-fetch assignments terbaru per pegawai
    from sqlalchemy import func
    subq = (
        db.query(
            SyncPresentPresensi.id_pegawai,
            func.max(SyncPresentPresensi.cdate).label("max_cdate"),
        )
        .filter(
            SyncPresentPresensi.id_pegawai.in_(pegawai_ids),
            SyncPresentPresensi.cdate <= tanggal,
        )
        .group_by(SyncPresentPresensi.id_pegawai)
        .subquery()
    )

    assignments = (
        db.query(SyncPresentPresensi)
        .join(subq, (
            (SyncPresentPresensi.id_pegawai == subq.c.id_pegawai)
            & (SyncPresentPresensi.cdate == subq.c.max_cdate)
        ))
        .all()
    )

    assignment_map = {a.id_pegawai: a.id_group for a in assignments}

    # Pre-fetch overrides per group yang relevan
    relevant_groups = set(assignment_map.values())
    overrides = {
        o.id_group: o
        for o in db.query(SyncPresentMasuk).filter(
            SyncPresentMasuk.tanggal == tanggal,
            SyncPresentMasuk.id_group.in_(relevant_groups),
        ).all()
    }

    # Pre-fetch templates per group
    templates_all = db.query(SyncPresentGroup).filter(
        SyncPresentGroup.id_group.in_(relevant_groups),
    ).all()

    # Index templates: group -> list of templates sorted by berlaku desc
    from collections import defaultdict
    templates_by_group: dict[int, list[SyncPresentGroup]] = defaultdict(list)
    for t in templates_all:
        templates_by_group[t.id_group].append(t)
    for k in templates_by_group:
        templates_by_group[k].sort(key=lambda x: x.berlaku or date.min, reverse=True)

    day_idx = tanggal.weekday()
    prefix = DAY_MAP.get(day_idx, "sen")

    results = {}
    for pid in pegawai_ids:
        id_group = assignment_map.get(pid)
        if id_group is None:
            results[pid] = JadwalResult(
                tipe="undefined", jam_masuk=None, jam_pulang=None,
                id_group=None, keterangan="Tidak ada assignment grup",
            )
            continue

        # Cek override
        ov = overrides.get(id_group)
        if ov:
            results[pid] = JadwalResult(
                tipe="override", jam_masuk=ov.masuk, jam_pulang=ov.pulang,
                id_group=id_group, keterangan=f"Override: {ov.status or ''}",
            )
            continue

        # Cari template
        tmpl = None
        for t in templates_by_group.get(id_group, []):
            if t.berlaku and t.berakhir and t.berlaku <= tanggal <= t.berakhir:
                tmpl = t
                break
        if not tmpl and templates_by_group.get(id_group):
            tmpl = templates_by_group[id_group][0]  # fallback terbaru

        if not tmpl:
            results[pid] = JadwalResult(
                tipe="undefined", jam_masuk=None, jam_pulang=None,
                id_group=id_group, keterangan=f"Template tidak ditemukan untuk grup {id_group}",
            )
            continue

        jam_masuk = getattr(tmpl, f"{prefix}_awal", None)
        jam_pulang = getattr(tmpl, f"{prefix}_akhir", None)

        if jam_masuk is None:
            results[pid] = JadwalResult(
                tipe="libur", jam_masuk=None, jam_pulang=None,
                id_group=id_group, keterangan=f"Tidak dijadwalkan kerja (grup: {tmpl.nama_group})",
            )
        else:
            results[pid] = JadwalResult(
                tipe="template", jam_masuk=jam_masuk, jam_pulang=jam_pulang,
                id_group=id_group, keterangan=tmpl.nama_group,
            )

    return results
