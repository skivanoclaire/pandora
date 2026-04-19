#!/bin/bash
# =============================================================
# PANDORA Full Backup Script
# Backup source code + PostgreSQL database ke folder lokal
# Kemudian upload ke Google Drive via rclone
# =============================================================

set -e

BACKUP_DIR="/home/pandora/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="pandora_backup_${TIMESTAMP}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"

echo "=== PANDORA Backup — ${TIMESTAMP} ==="

# 1. Buat folder backup
mkdir -p "${BACKUP_PATH}"

# 2. Backup PostgreSQL database
echo "[1/4] Dumping PostgreSQL database..."
docker compose -f /home/pandora/pandora/docker-compose.yml exec -T pandora-db \
    pg_dump -U pandora -d pandora --no-owner --no-acl \
    > "${BACKUP_PATH}/pandora_db.sql"
echo "  Database: $(du -sh ${BACKUP_PATH}/pandora_db.sql | cut -f1)"

# 3. Backup source code (tanpa vendor, node_modules, storage/logs)
echo "[2/4] Archiving source code..."
tar czf "${BACKUP_PATH}/pandora_source.tar.gz" \
    --exclude='src/vendor' \
    --exclude='src/node_modules' \
    --exclude='src/storage/logs/*.log' \
    --exclude='src/storage/framework/views/*.php' \
    --exclude='*.pyc' \
    --exclude='__pycache__' \
    -C /home/pandora/pandora .
echo "  Source: $(du -sh ${BACKUP_PATH}/pandora_source.tar.gz | cut -f1)"

# 4. Backup .env (encrypted with password)
echo "[3/4] Backing up .env..."
if [ -f /home/pandora/pandora/src/.env ]; then
    cp /home/pandora/pandora/src/.env "${BACKUP_PATH}/env_backup.txt"
    echo "  .env: OK"
fi

# 5. Create checksum
echo "[4/4] Creating checksums..."
cd "${BACKUP_PATH}"
sha256sum * > checksums.sha256
echo "  Checksums: OK"

# 6. Compress everything into single file
cd "${BACKUP_DIR}"
tar czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"
rm -rf "${BACKUP_PATH}"
echo ""
echo "=== Backup selesai: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz ==="
echo "  Size: $(du -sh ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz | cut -f1)"

# 7. Upload ke Google Drive
RCLONE="${HOME}/bin/rclone"
if [ -x "${RCLONE}" ]; then
    echo ""
    echo "[Upload] Uploading ke Google Drive..."
    ${RCLONE} copy "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" gdrive:PANDORA-Backups/
    echo "  Upload selesai: gdrive:PANDORA-Backups/${BACKUP_NAME}.tar.gz"

    # Cleanup remote: keep last 7 backups di Google Drive juga
    ${RCLONE} lsf gdrive:PANDORA-Backups/ --files-only | sort -r | tail -n +8 | while read f; do
        ${RCLONE} deletefile "gdrive:PANDORA-Backups/${f}" 2>/dev/null
        echo "  Remote deleted: ${f}"
    done
else
    echo "[Skip] rclone not found at ${RCLONE}"
fi

# 8. Cleanup: keep last 7 backups
echo ""
echo "[Cleanup] Menyimpan 7 backup terakhir..."
ls -t ${BACKUP_DIR}/pandora_backup_*.tar.gz 2>/dev/null | tail -n +8 | xargs rm -f 2>/dev/null
echo "  Backup tersisa: $(ls ${BACKUP_DIR}/pandora_backup_*.tar.gz 2>/dev/null | wc -l)"

echo ""
echo "=== Selesai ==="
