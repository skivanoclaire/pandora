"""
ML Pipeline: Isolation Forest + DBSCAN untuk deteksi anomali Tingkat 3.

Isolation Forest — deteksi multivariate anomaly berdasarkan fitur numerik.
DBSCAN — spatial clustering untuk mendeteksi pola lokasi tidak wajar.

Output: anomaly_flags dengan tingkat=3, metode_deteksi sesuai model.
"""

import logging
from datetime import date, datetime
from typing import Optional

import numpy as np
import pandas as pd
from sklearn.ensemble import IsolationForest
from sklearn.cluster import DBSCAN
from sklearn.preprocessing import StandardScaler
from sqlalchemy.orm import Session

from models.analytics import AnomalyFlag, FeatureKehadiranHarian
from models.staging import SyncPresentRekap

logger = logging.getLogger(__name__)

# Kolom fitur yang digunakan untuk Isolation Forest
IF_FEATURE_COLS = [
    "velocity_berangkat_pulang",
    "velocity_vs_kemarin",
    "jarak_dari_geofence_berangkat",
    "jarak_dari_geofence_pulang",
    "deviasi_masuk_vs_jadwal_ekspektasi",
    "deviasi_pulang_vs_jadwal_ekspektasi",
    "deviasi_waktu_masuk_vs_median_personal",
    "deviasi_waktu_masuk_vs_median_unit",
]

# Kolom spasial untuk DBSCAN
DBSCAN_SPATIAL_COLS = [
    "lat_berangkat",
    "long_berangkat",
]

MODEL_VERSION = "ml_v1.0"


def run_isolation_forest(
    db: Session,
    tanggal_awal: date,
    tanggal_akhir: date,
    contamination: float = 0.05,
    n_estimators: int = 200,
    random_state: int = 42,
) -> dict:
    """
    Jalankan Isolation Forest pada features_kehadiran_harian.

    Args:
        contamination: Proporsi anomali yang diharapkan (default 5%)
        n_estimators: Jumlah pohon
    """
    # 1. Ambil data fitur
    features = db.query(FeatureKehadiranHarian).filter(
        FeatureKehadiranHarian.tanggal.between(tanggal_awal, tanggal_akhir),
    ).all()

    if len(features) < 30:
        logger.warning(f"Data terlalu sedikit untuk IF: {len(features)} baris")
        return {"status": "skipped", "reason": "insufficient_data", "count": len(features)}

    # 2. Konversi ke DataFrame
    records = []
    for f in features:
        row = {
            "id": f.id,
            "id_pegawai": f.id_pegawai,
            "tanggal": f.tanggal,
        }
        for col in IF_FEATURE_COLS:
            val = getattr(f, col)
            row[col] = float(val) if val is not None else np.nan
        records.append(row)

    df = pd.DataFrame(records)

    # 3. Tangani missing values: isi NaN dengan median kolom
    feature_df = df[IF_FEATURE_COLS].copy()
    for col in IF_FEATURE_COLS:
        median_val = feature_df[col].median()
        if pd.isna(median_val):
            median_val = 0.0
        feature_df[col] = feature_df[col].fillna(median_val)

    # 4. Scaling
    scaler = StandardScaler()
    X = scaler.fit_transform(feature_df)

    # 5. Fit Isolation Forest
    iso = IsolationForest(
        n_estimators=n_estimators,
        contamination=contamination,
        random_state=random_state,
        n_jobs=-1,
    )
    predictions = iso.fit_predict(X)
    scores = iso.decision_function(X)

    # 6. Filter anomali (prediction == -1)
    df["if_prediction"] = predictions
    df["if_score"] = scores

    anomali_df = df[df["if_prediction"] == -1].copy()

    # 7. Konversi score ke confidence (0-1, semakin negatif semakin anomali)
    if len(anomali_df) > 0:
        min_score = anomali_df["if_score"].min()
        max_score = anomali_df["if_score"].max()
        score_range = max_score - min_score if max_score != min_score else 1.0
        anomali_df["confidence"] = anomali_df["if_score"].apply(
            lambda s: round(min(1.0, max(0.5, 1.0 - (s - min_score) / score_range)), 4)
        )

    # 8. Insert anomaly flags
    now = datetime.utcnow()
    inserted = 0

    for _, row in anomali_df.iterrows():
        # Cek duplikat
        exists = db.query(AnomalyFlag).filter(
            AnomalyFlag.id_pegawai == int(row["id_pegawai"]),
            AnomalyFlag.tanggal == row["tanggal"],
            AnomalyFlag.jenis_anomali == "combination",
            AnomalyFlag.metode_deteksi == "isolation_forest",
        ).first()

        if exists:
            continue

        # Ambil fitur yang paling berkontribusi (top 3 deviasi dari mean)
        feature_vals = {}
        for col in IF_FEATURE_COLS:
            val = row.get(col)
            if not pd.isna(val):
                feature_vals[col] = round(float(val), 2)

        db.add(AnomalyFlag(
            id_pegawai=int(row["id_pegawai"]),
            tanggal=row["tanggal"],
            jenis_anomali="combination",
            confidence=float(row["confidence"]),
            tingkat=3,
            metode_deteksi="isolation_forest",
            model_version=MODEL_VERSION,
            detail_metadata={
                "if_score": round(float(row["if_score"]), 4),
                "features": feature_vals,
            },
            status_review="belum_direview",
            detected_at=now,
            created_at=now,
            updated_at=now,
        ))
        inserted += 1

    db.commit()

    return {
        "status": "completed",
        "total_records": len(df),
        "anomalies_detected": len(anomali_df),
        "anomalies_inserted": inserted,
        "contamination": contamination,
    }


def run_dbscan(
    db: Session,
    tanggal_awal: date,
    tanggal_akhir: date,
    eps_km: float = 0.5,
    min_samples: int = 3,
) -> dict:
    """
    Jalankan DBSCAN spatial clustering pada lokasi absensi.
    Identifikasi pegawai yang absen dari lokasi terisolasi (tidak dalam cluster).

    Args:
        eps_km: Radius neighborhood dalam km (dikonversi ke derajat)
        min_samples: Minimum titik untuk membentuk cluster
    """
    # 1. Ambil data rekap dengan koordinat
    rekaps = db.query(SyncPresentRekap).filter(
        SyncPresentRekap.tanggal.between(tanggal_awal, tanggal_akhir),
        SyncPresentRekap.lat_berangkat.isnot(None),
        SyncPresentRekap.long_berangkat.isnot(None),
    ).all()

    if len(rekaps) < min_samples * 2:
        logger.warning(f"Data terlalu sedikit untuk DBSCAN: {len(rekaps)} baris")
        return {"status": "skipped", "reason": "insufficient_data", "count": len(rekaps)}

    # 2. Konversi ke DataFrame
    records = []
    for r in rekaps:
        records.append({
            "id_pegawai": r.id_pegawai,
            "tanggal": r.tanggal,
            "lat": float(r.lat_berangkat),
            "lon": float(r.long_berangkat),
        })

    df = pd.DataFrame(records)

    # 3. DBSCAN dengan Haversine metric
    # Konversi ke radian untuk haversine
    coords_rad = np.radians(df[["lat", "lon"]].values)

    # eps dalam radian (approx: 1 km ≈ 0.009 derajat pada ekuator)
    eps_rad = eps_km / 6371.0

    clustering = DBSCAN(
        eps=eps_rad,
        min_samples=min_samples,
        metric="haversine",
        n_jobs=-1,
    )
    labels = clustering.fit_predict(coords_rad)
    df["cluster"] = labels

    # 4. Noise points (cluster == -1) = lokasi terisolasi
    noise_df = df[df["cluster"] == -1]

    # 5. Insert anomaly flags untuk noise points
    now = datetime.utcnow()
    inserted = 0

    for _, row in noise_df.iterrows():
        exists = db.query(AnomalyFlag).filter(
            AnomalyFlag.id_pegawai == int(row["id_pegawai"]),
            AnomalyFlag.tanggal == row["tanggal"],
            AnomalyFlag.metode_deteksi == "dbscan",
        ).first()

        if exists:
            continue

        db.add(AnomalyFlag(
            id_pegawai=int(row["id_pegawai"]),
            tanggal=row["tanggal"],
            jenis_anomali="combination",
            confidence=0.60,
            tingkat=3,
            metode_deteksi="dbscan",
            model_version=MODEL_VERSION,
            detail_metadata={
                "lat": round(row["lat"], 7),
                "lon": round(row["lon"], 7),
                "cluster_label": -1,
                "total_clusters": int(df["cluster"].max() + 1) if df["cluster"].max() >= 0 else 0,
                "noise_points": int(len(noise_df)),
            },
            status_review="belum_direview",
            detected_at=now,
            created_at=now,
            updated_at=now,
        ))
        inserted += 1

    db.commit()

    n_clusters = len(set(labels)) - (1 if -1 in labels else 0)

    return {
        "status": "completed",
        "total_points": len(df),
        "n_clusters": n_clusters,
        "noise_points": len(noise_df),
        "anomalies_inserted": inserted,
        "eps_km": eps_km,
        "min_samples": min_samples,
    }
