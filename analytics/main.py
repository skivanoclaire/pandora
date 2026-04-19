from fastapi import FastAPI

from routers.pipeline import router as pipeline_router

app = FastAPI(
    title="PANDORA Analytics Service",
    description="Analytics engine untuk Portal Analitik Data Kehadiran ASN",
    version="0.2.0",
)

app.include_router(pipeline_router)


@app.get("/health")
def health_check():
    return {"status": "ok", "service": "pandora-analytics"}
