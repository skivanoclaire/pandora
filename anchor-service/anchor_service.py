"""
PANDORA Anchor Service
OpenTimestamps (OTS) operations - stamp Merkle roots to Bitcoin blockchain
and upgrade/verify proofs once confirmed.
"""

import base64
import os
import re
import subprocess
import tempfile

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel

app = FastAPI(title="PANDORA Anchor Service", version="1.0.0")


# ── Request / Response Models ────────────────────────────────────────────

class AnchorRequest(BaseModel):
    date: str
    merkle_root_hex: str


class AnchorResponse(BaseModel):
    date: str
    merkle_root_hex: str
    ots_proof_base64: str
    calendars: list[str]


class UpgradeRequest(BaseModel):
    ots_proof_base64: str


class UpgradeResponse(BaseModel):
    upgraded: bool
    ots_proof_base64: str
    btc_block_hash: str | None = None
    btc_block_height: int | None = None
    stdout: str
    stderr: str


class VerifyRequest(BaseModel):
    ots_proof_base64: str
    merkle_root_hex: str


class VerifyResponse(BaseModel):
    verified: bool
    stdout: str
    stderr: str


# ── Endpoints ────────────────────────────────────────────────────────────

@app.get("/health")
async def health():
    return {"status": "ok"}


@app.post("/anchor", response_model=AnchorResponse)
async def anchor(req: AnchorRequest):
    """Stamp a Merkle root to the Bitcoin blockchain via OpenTimestamps."""
    try:
        # Validate hex is 32 bytes (64 hex characters)
        root_bytes = bytes.fromhex(req.merkle_root_hex)
        if len(root_bytes) != 32:
            raise HTTPException(
                status_code=400,
                detail=f"merkle_root_hex must be 32 bytes, got {len(root_bytes)}",
            )
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid hex string")

    try:
        with tempfile.TemporaryDirectory() as tmpdir:
            hash_file = os.path.join(tmpdir, "merkle_root.bin")
            ots_file = hash_file + ".ots"

            # Write raw root bytes to temp file
            with open(hash_file, "wb") as f:
                f.write(root_bytes)

            # Run ots stamp
            result = subprocess.run(
                ["ots", "stamp", hash_file],
                capture_output=True,
                text=True,
                timeout=120,
            )

            if result.returncode != 0:
                raise HTTPException(
                    status_code=500,
                    detail=f"ots stamp failed: {result.stderr}",
                )

            # Read the .ots proof file
            with open(ots_file, "rb") as f:
                ots_proof_bytes = f.read()

            ots_proof_base64 = base64.b64encode(ots_proof_bytes).decode("utf-8")

            # Extract calendar URLs from stderr/stdout
            calendars = []
            combined_output = result.stdout + result.stderr
            for line in combined_output.splitlines():
                if "calendar" in line.lower() or "http" in line.lower():
                    urls = re.findall(r"https?://[^\s]+", line)
                    calendars.extend(urls)

            return AnchorResponse(
                date=req.date,
                merkle_root_hex=req.merkle_root_hex,
                ots_proof_base64=ots_proof_base64,
                calendars=calendars,
            )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/upgrade", response_model=UpgradeResponse)
async def upgrade(req: UpgradeRequest):
    """Upgrade an OTS proof once the Bitcoin transaction is confirmed."""
    try:
        ots_bytes = base64.b64decode(req.ots_proof_base64)
    except Exception:
        raise HTTPException(status_code=400, detail="Invalid base64 in ots_proof_base64")

    try:
        with tempfile.TemporaryDirectory() as tmpdir:
            ots_file = os.path.join(tmpdir, "proof.ots")

            with open(ots_file, "wb") as f:
                f.write(ots_bytes)

            # Run ots upgrade
            result = subprocess.run(
                ["ots", "upgrade", ots_file],
                capture_output=True,
                text=True,
                timeout=120,
            )

            # Read the (possibly upgraded) proof
            with open(ots_file, "rb") as f:
                upgraded_bytes = f.read()

            upgraded_base64 = base64.b64encode(upgraded_bytes).decode("utf-8")

            # Check if the proof was actually upgraded by comparing bytes
            upgraded = upgraded_bytes != ots_bytes

            btc_block_hash = None
            btc_block_height = None

            if upgraded:
                # Check for bitcoin attestation by looking at the raw bytes
                # Bitcoin attestation marker: 0x0588960d73d71901
                has_btc_attestation = b"\x05\x88\x96\x0d\x73\xd7\x19\x01" in upgraded_bytes

                if has_btc_attestation:
                    # Run ots info to extract block details
                    info_result = subprocess.run(
                        ["ots", "info", ots_file],
                        capture_output=True,
                        text=True,
                        timeout=30,
                    )
                    info_output = info_result.stdout + info_result.stderr

                    # Parse block height
                    height_match = re.search(
                        r"Bitcoin\s+block\s+(\d+)", info_output, re.IGNORECASE
                    )
                    if height_match:
                        btc_block_height = int(height_match.group(1))

                    # Parse block hash (64 hex chars)
                    hash_match = re.search(
                        r"block\s+hash\s+([0-9a-fA-F]{64})", info_output, re.IGNORECASE
                    )
                    if not hash_match:
                        # Try alternative patterns
                        hash_match = re.search(
                            r"([0-9a-fA-F]{64})", info_output
                        )
                    if hash_match:
                        btc_block_hash = hash_match.group(1)

            return UpgradeResponse(
                upgraded=upgraded,
                ots_proof_base64=upgraded_base64,
                btc_block_hash=btc_block_hash,
                btc_block_height=btc_block_height,
                stdout=result.stdout,
                stderr=result.stderr,
            )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


@app.post("/verify", response_model=VerifyResponse)
async def verify(req: VerifyRequest):
    """Verify an OTS proof against the original Merkle root."""
    try:
        ots_bytes = base64.b64decode(req.ots_proof_base64)
    except Exception:
        raise HTTPException(status_code=400, detail="Invalid base64 in ots_proof_base64")

    try:
        root_bytes = bytes.fromhex(req.merkle_root_hex)
    except ValueError:
        raise HTTPException(status_code=400, detail="Invalid hex in merkle_root_hex")

    try:
        with tempfile.TemporaryDirectory() as tmpdir:
            hash_file = os.path.join(tmpdir, "merkle_root.bin")
            ots_file = hash_file + ".ots"

            # Write original hash
            with open(hash_file, "wb") as f:
                f.write(root_bytes)

            # Write OTS proof
            with open(ots_file, "wb") as f:
                f.write(ots_bytes)

            # Run ots verify
            result = subprocess.run(
                ["ots", "verify", ots_file],
                capture_output=True,
                text=True,
                timeout=120,
            )

            combined = result.stdout + result.stderr
            verified = result.returncode == 0 and "success" in combined.lower()

            return VerifyResponse(
                verified=verified,
                stdout=result.stdout,
                stderr=result.stderr,
            )

    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))
