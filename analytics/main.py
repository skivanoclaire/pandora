from fastapi import FastAPI

app = FastAPI(
    title="PANDORA Analytics Service",
    description="Analytics engine untuk Portal Analitik Data Kehadiran ASN",
    version="0.1.0",
)


@app.get("/health")
def health_check():
    return {"status": "ok", "service": "pandora-analytics"}
