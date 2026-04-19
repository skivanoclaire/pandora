"""SQLAlchemy models untuk tabel staging (read-only dari sisi Python)."""

from sqlalchemy import (
    BigInteger, Boolean, Column, Date, DateTime, Integer, Numeric, String, Text, Time,
)
from sqlalchemy.dialects.postgresql import JSONB

from config.database import Base


class SyncPegPegawai(Base):
    __tablename__ = "sync_peg_pegawai"

    id = Column(BigInteger, primary_key=True)
    id_pegawai = Column(BigInteger, unique=True, index=True)
    nip = Column(String(18), index=True)
    nama = Column(String(255))
    id_unit = Column(BigInteger, index=True)
    status = Column(String(50))
    bebas_lokasi = Column(Boolean, default=False)


class SyncRefUnit(Base):
    __tablename__ = "sync_ref_unit"

    id = Column(BigInteger, primary_key=True)
    id_unit = Column(BigInteger, unique=True)
    nama_unit = Column(String(255))
    parent_id = Column(BigInteger)
    kode_unit = Column(String(50))
    level = Column(Integer)


class SyncPresentRekap(Base):
    __tablename__ = "sync_present_rekap"

    id = Column(BigInteger, primary_key=True)
    id_rekap = Column(BigInteger, unique=True)
    id_pegawai = Column(BigInteger, index=True)
    tanggal = Column(Date, index=True)
    nip = Column(String(18))
    lat_berangkat = Column(Numeric(10, 7))
    long_berangkat = Column(Numeric(10, 7))
    nama_lokasi_berangkat = Column(String(255))
    foto_berangkat = Column(String(500))
    lat_pulang = Column(Numeric(10, 7))
    long_pulang = Column(Numeric(10, 7))
    nama_lokasi_pulang = Column(String(255))
    foto_pulang = Column(String(500))
    jam_masuk = Column(Time)
    jam_pulang = Column(Time)
    tw = Column(Boolean, default=False)
    mkttw = Column(Boolean, default=False)
    pktw = Column(Boolean, default=False)
    plc = Column(Boolean, default=False)
    tk = Column(Boolean, default=False)
    ta = Column(Boolean, default=False)
    i = Column(Boolean, default=False)
    s = Column(Boolean, default=False)
    c = Column(Boolean, default=False)
    dl = Column(Boolean, default=False)
    dsp = Column(Boolean, default=False)
    ll = Column(Boolean, default=False)
    d = Column(String(10))
    jenis_presensi = Column(String(50))
    cdate = Column(DateTime)


class SyncPresentGroup(Base):
    __tablename__ = "sync_present_group"

    id = Column(BigInteger, primary_key=True)
    id_group = Column(BigInteger, unique=True)
    nama_group = Column(String(255))
    berlaku = Column(Date)
    berakhir = Column(Date)
    sen_awal = Column(Time)
    sen_akhir = Column(Time)
    sel_awal = Column(Time)
    sel_akhir = Column(Time)
    rab_awal = Column(Time)
    rab_akhir = Column(Time)
    kam_awal = Column(Time)
    kam_akhir = Column(Time)
    jum_awal = Column(Time)
    jum_akhir = Column(Time)
    sab_awal = Column(Time)
    sab_akhir = Column(Time)
    min_awal = Column(Time)
    min_akhir = Column(Time)


class SyncPresentPresensi(Base):
    __tablename__ = "sync_present_presensi"

    id = Column(BigInteger, primary_key=True)
    id_presensi = Column(BigInteger, unique=True)
    id_pegawai = Column(BigInteger, index=True)
    id_group = Column(BigInteger, index=True)
    cdate = Column(DateTime)


class SyncPresentMasuk(Base):
    __tablename__ = "sync_present_masuk"

    id = Column(BigInteger, primary_key=True)
    id_masuk = Column(BigInteger, unique=True)
    tanggal = Column(Date)
    id_group = Column(BigInteger, index=True)
    status = Column(String(50))
    masuk = Column(Time)
    pulang = Column(Time)


class SyncPresentLibur(Base):
    __tablename__ = "sync_present_libur"

    id = Column(BigInteger, primary_key=True)
    id_libur = Column(BigInteger, unique=True)
    tanggal = Column(Date, index=True)
    keterangan = Column(String(255))


class SyncRefLokasiUnit(Base):
    __tablename__ = "sync_ref_lokasi_unit"

    id = Column(BigInteger, primary_key=True)
    id_lokasi = Column(BigInteger, unique=True)
    nama_lokasi = Column(String(255))
    latitude = Column(Numeric(10, 7))
    longitude = Column(Numeric(10, 7))
    radius = Column(Integer)
    aktif = Column(Boolean, default=True)


class SyncRefBantuUnit(Base):
    __tablename__ = "sync_ref_bantu_unit"

    id = Column(BigInteger, primary_key=True)
    id_bantu = Column(BigInteger, unique=True)
    id_unit = Column(BigInteger, index=True)
    id_lokasi = Column(BigInteger, index=True)


class SyncFakeGps(Base):
    __tablename__ = "sync_fake_gps"

    id = Column(BigInteger, primary_key=True)
    id_fake_gps = Column(BigInteger, unique=True)
    package_name = Column(String(255))
    nama_aplikasi = Column(String(255))


class SyncPresentMapsLogs(Base):
    __tablename__ = "sync_present_maps_logs"

    id = Column(BigInteger, primary_key=True)
    id_maps_log = Column(BigInteger, unique=True)
    id_pegawai = Column(BigInteger, index=True)
    latitude = Column(Numeric(10, 7))
    longitude = Column(Numeric(10, 7))
    jam = Column(Time)
    jamke = Column(Integer)
    id_maps = Column(BigInteger)
    tanggal = Column(Date)
    cdate = Column(DateTime)


class SyncPresentIjin(Base):
    __tablename__ = "sync_present_ijin"

    id = Column(BigInteger, primary_key=True)
    id_ijin = Column(BigInteger, unique=True)
    id_pegawai = Column(BigInteger, index=True)
    tanggal_mulai = Column(Date)
    tanggal_selesai = Column(Date)
    jenis_ijin = Column(String(100))
    keterangan = Column(Text)
    cdate = Column(DateTime)
