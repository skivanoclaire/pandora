"""SQLAlchemy models untuk tabel output analitik (read-write dari sisi Python)."""

from sqlalchemy import (
    BigInteger, Boolean, Column, Date, DateTime, Enum, Integer,
    Numeric, SmallInteger, String, Text, Time,
)
from sqlalchemy.dialects.postgresql import JSONB, UUID

from config.database import Base


class FeatureKehadiranHarian(Base):
    __tablename__ = "features_kehadiran_harian"

    id = Column(BigInteger, primary_key=True)
    id_pegawai = Column(BigInteger, index=True, nullable=False)
    tanggal = Column(Date, index=True, nullable=False)

    # Fitur gerak & lokasi
    velocity_berangkat_pulang = Column(Numeric(8, 2))
    velocity_vs_kemarin = Column(Numeric(8, 2))
    jarak_dari_geofence_berangkat = Column(Numeric(10, 2))
    jarak_dari_geofence_pulang = Column(Numeric(10, 2))
    geofence_match_flag = Column(String(20))
    aplikasi_fake_gps_terdeteksi = Column(Boolean, default=False)

    # Konteks jadwal efektif
    id_group_efektif = Column(BigInteger)
    sumber_jadwal = Column(String(20))
    jam_masuk_ekspektasi = Column(Time)
    jam_pulang_ekspektasi = Column(Time)

    # Fitur temporal
    deviasi_masuk_vs_jadwal_ekspektasi = Column(Numeric(8, 2))
    deviasi_pulang_vs_jadwal_ekspektasi = Column(Numeric(8, 2))
    deviasi_waktu_masuk_vs_median_personal = Column(Numeric(8, 2))
    deviasi_waktu_masuk_vs_median_unit = Column(Numeric(8, 2))

    # Snapshot status SIKARA
    status_sikara_tw = Column(Boolean, default=False)
    status_sikara_mkttw = Column(Boolean, default=False)
    status_sikara_pktw = Column(Boolean, default=False)
    status_sikara_plc = Column(Boolean, default=False)
    status_sikara_tk = Column(Boolean, default=False)
    status_sikara_ta = Column(Boolean, default=False)
    alasan_ketidakhadiran = Column(String(5))

    # Hasil rule engine
    rule_compliance_flag = Column(String(30))

    # Versioning
    status_data_final = Column(Boolean, default=False)
    computed_at_run_id = Column(UUID)

    created_at = Column(DateTime)
    updated_at = Column(DateTime)


class AnomalyFlag(Base):
    __tablename__ = "anomaly_flags"

    id = Column(BigInteger, primary_key=True)
    id_pegawai = Column(BigInteger, index=True, nullable=False)
    tanggal = Column(Date, index=True, nullable=False)
    jenis_anomali = Column(String(30), nullable=False)
    confidence = Column(Numeric(5, 4), nullable=False)
    tingkat = Column(SmallInteger, nullable=False)
    metode_deteksi = Column(String(20), nullable=False)
    model_version = Column(String(30))
    detail_metadata = Column("metadata", JSONB)
    status_review = Column(String(50), default="belum_direview")
    direview_oleh = Column(BigInteger)
    direview_pada = Column(DateTime)
    catatan_review = Column(Text)
    detected_at = Column(DateTime, nullable=False)
    created_at = Column(DateTime)
    updated_at = Column(DateTime)
