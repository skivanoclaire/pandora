"""
API endpoints untuk trigger pipeline analitik.

Endpoint ini dipanggil oleh Laravel (internal) untuk menjalankan:
- Pipeline harian (Tingkat 1): feature engineering + rule engine
- Pipeline bulanan (Tingkat 2+3): re-compute features + rules + ML
- Isolation Forest & DBSCAN (on-demand)
"""

from datetime import date, datetime
from typing import Optional

from fastapi import APIRouter, Depends, HTTPException
from pydantic import BaseModel
from sqlalchemy.orm import Session

from config.database import get_db
from services.feature_engineering import run_feature_engineering
from services.rule_engine import run_rules_tingkat1, run_rules_tingkat2
from services.ml_pipeline import run_isolation_forest, run_dbscan

router = APIRouter(prefix="/pipeline", tags=["Pipeline"])


class DailyRequest(BaseModel):
    tanggal: date


class MonthlyRequest(BaseModel):
    bulan: int
    tahun: int


class MLRequest(BaseModel):
    tanggal_awal: date
    tanggal_akhir: date
    contamination: float = 0.05
    eps_km: float = 0.5
    min_samples: int = 3


class PipelineResponse(BaseModel):
    status: str
    tanggal: Optional[str] = None
    feature_engineering: Optional[dict] = None
    rule_engine: Optional[dict] = None
    isolation_forest: Optional[dict] = None
    dbscan: Optional[dict] = None
    duration_seconds: Optional[float] = None


@router.post("/daily", response_model=PipelineResponse)
def run_daily_pipeline(req: DailyRequest, db: Session = Depends(get_db)):
    """
    Pipeline harian — dijalankan setiap dini hari untuk H-1.

    1. Feature engineering (status_data_final=False)
    2. Rule engine Tingkat 1 (physical impossibility)
    3. Isolation Forest & DBSCAN (insight awal, bukan untuk laporan final)
    """
    start = datetime.utcnow()

    # 1. Feature engineering
    fe_result = run_feature_engineering(db, req.tanggal, is_final=False)

    # 2. Rule engine Tingkat 1
    re_result = run_rules_tingkat1(db, req.tanggal)

    # 3. ML (insight awal, opsional untuk data satu hari)
    # IF dan DBSCAN butuh konteks lebih luas, jadi jalankan pada window 30 hari
    from datetime import timedelta
    window_start = req.tanggal - timedelta(days=30)
    if_result = run_isolation_forest(db, window_start, req.tanggal)
    dbscan_result = run_dbscan(db, window_start, req.tanggal)

    elapsed = (datetime.utcnow() - start).total_seconds()

    return PipelineResponse(
        status="completed",
        tanggal=str(req.tanggal),
        feature_engineering=fe_result,
        rule_engine=re_result,
        isolation_forest=if_result,
        dbscan=dbscan_result,
        duration_seconds=round(elapsed, 2),
    )


@router.post("/monthly", response_model=PipelineResponse)
def run_monthly_pipeline(req: MonthlyRequest, db: Session = Depends(get_db)):
    """
    Pipeline bulanan — dijalankan pada tanggal 5 bulan berikutnya.

    1. Re-compute features (status_data_final=True) untuk seluruh bulan
    2. Rule engine Tingkat 2 (DL violations, geofence compliance)
    3. Isolation Forest & DBSCAN pada dataset final
    """
    from calendar import monthrange

    start = datetime.utcnow()
    _, last_day = monthrange(req.tahun, req.bulan)
    tgl_awal = date(req.tahun, req.bulan, 1)
    tgl_akhir = date(req.tahun, req.bulan, last_day)

    # 1. Re-compute features untuk seluruh bulan
    fe_total = {"total": 0, "inserted": 0, "skipped": 0}
    current = tgl_awal
    while current <= tgl_akhir:
        result = run_feature_engineering(db, current, is_final=True)
        fe_total["total"] += result["total"]
        fe_total["inserted"] += result["inserted"]
        fe_total["skipped"] += result["skipped"]
        current += timedelta(days=1)

    # 2. Rule engine Tingkat 2
    re_result = run_rules_tingkat2(db, req.bulan, req.tahun)

    # 3. ML pada dataset final bulanan
    if_result = run_isolation_forest(db, tgl_awal, tgl_akhir)
    dbscan_result = run_dbscan(db, tgl_awal, tgl_akhir)

    elapsed = (datetime.utcnow() - start).total_seconds()

    return PipelineResponse(
        status="completed",
        tanggal=f"{req.tahun}-{req.bulan:02d}",
        feature_engineering=fe_total,
        rule_engine=re_result,
        isolation_forest=if_result,
        dbscan=dbscan_result,
        duration_seconds=round(elapsed, 2),
    )


@router.post("/ml", response_model=PipelineResponse)
def run_ml_only(req: MLRequest, db: Session = Depends(get_db)):
    """
    Jalankan hanya Isolation Forest + DBSCAN pada rentang tanggal tertentu.
    Untuk eksplorasi ad-hoc oleh admin DKISP.
    """
    start = datetime.utcnow()

    if_result = run_isolation_forest(
        db, req.tanggal_awal, req.tanggal_akhir,
        contamination=req.contamination,
    )
    dbscan_result = run_dbscan(
        db, req.tanggal_awal, req.tanggal_akhir,
        eps_km=req.eps_km,
        min_samples=req.min_samples,
    )

    elapsed = (datetime.utcnow() - start).total_seconds()

    return PipelineResponse(
        status="completed",
        tanggal=f"{req.tanggal_awal} — {req.tanggal_akhir}",
        isolation_forest=if_result,
        dbscan=dbscan_result,
        duration_seconds=round(elapsed, 2),
    )


from datetime import timedelta
