#!/bin/bash
# =============================================================
# PANDORA Full Disaster Recovery Backup
# Backup SEMUA yang dibutuhkan untuk restore di VM baru
# =============================================================

set -e

BACKUP_DIR="/home/pandora/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_NAME="pandora_full_${TIMESTAMP}"
BACKUP_PATH="${BACKUP_DIR}/${BACKUP_NAME}"
RCLONE="${HOME}/bin/rclone"

echo "=== PANDORA Full Backup — ${TIMESTAMP} ==="

mkdir -p "${BACKUP_PATH}"

# 1. PostgreSQL database (full dump termasuk PostGIS, roles)
echo "[1/7] Dumping PostgreSQL database..."
docker compose -f /home/pandora/pandora/docker-compose.yml exec -T pandora-db \
    pg_dump -U pandora -d pandora --no-owner --no-acl \
    > "${BACKUP_PATH}/pandora_db.sql"
echo "  DB: $(du -sh ${BACKUP_PATH}/pandora_db.sql | cut -f1)"

# 2. Source code (full project directory)
echo "[2/7] Archiving source code..."
tar czf "${BACKUP_PATH}/pandora_source.tar.gz" \
    --exclude='src/vendor' \
    --exclude='src/node_modules' \
    --exclude='src/storage/logs/*.log' \
    --exclude='src/storage/framework/views/*.php' \
    --exclude='*.pyc' \
    --exclude='__pycache__' \
    -C /home/pandora/pandora .
echo "  Source: $(du -sh ${BACKUP_PATH}/pandora_source.tar.gz | cut -f1)"

# 3. Environment files
echo "[3/7] Backing up configs..."
cp /home/pandora/pandora/src/.env "${BACKUP_PATH}/env_laravel.txt" 2>/dev/null || true

# 4. SSL certificates (Let's Encrypt)
echo "[4/7] Backing up SSL certificates..."
docker compose -f /home/pandora/pandora/docker-compose.yml exec -T pandora-nginx \
    tar czf - /etc/letsencrypt 2>/dev/null > "${BACKUP_PATH}/ssl_certs.tar.gz" || echo "  SSL: skipped (no certs or permission denied)"
if [ -s "${BACKUP_PATH}/ssl_certs.tar.gz" ]; then
    echo "  SSL: $(du -sh ${BACKUP_PATH}/ssl_certs.tar.gz | cut -f1)"
else
    rm -f "${BACKUP_PATH}/ssl_certs.tar.gz"
    echo "  SSL: not available"
fi

# 5. rclone config (Google Drive token)
echo "[5/7] Backing up rclone config..."
cp ~/.config/rclone/rclone.conf "${BACKUP_PATH}/rclone.conf" 2>/dev/null || echo "  rclone: not found"

# 6. Crontab
echo "[6/7] Backing up crontab..."
crontab -l > "${BACKUP_PATH}/crontab.txt" 2>/dev/null || echo "  crontab: empty"

# 7. Recovery script
echo "[7/7] Creating recovery script..."
cat > "${BACKUP_PATH}/RECOVERY.md" << 'RECOVERY'
# PANDORA Disaster Recovery

## Prerequisites
- Ubuntu 22.04+ VM
- Docker + Docker Compose installed
- Port 80, 443 open

## Recovery Steps

### 1. Setup user & clone
```bash
sudo adduser pandora
sudo usermod -aG docker pandora
su - pandora

# Extract source
mkdir -p ~/pandora
tar xzf pandora_source.tar.gz -C ~/pandora
cd ~/pandora
```

### 2. Restore environment
```bash
cp /path/to/env_laravel.txt src/.env
# Edit .env jika IP/hostname berubah
```

### 3. Start containers
```bash
docker compose up -d --build
# Tunggu semua container healthy (~1 menit)
docker compose ps
```

### 4. Install dependencies
```bash
docker compose exec pandora-app composer install --no-dev
docker compose exec pandora-app npm install && npm run build
```

### 5. Restore database
```bash
docker compose exec -T pandora-db psql -U pandora -d pandora < pandora_db.sql
```

### 6. Restore SSL (jika ada)
```bash
docker cp ssl_certs.tar.gz pandora-nginx:/tmp/
docker compose exec pandora-nginx tar xzf /tmp/ssl_certs.tar.gz -C /
```

### 7. Setup rclone (Google Drive backup)
```bash
mkdir -p ~/bin ~/.config/rclone
# Install rclone
curl https://rclone.org/install.sh | sudo bash
# Or: copy rclone binary ke ~/bin/
cp rclone.conf ~/.config/rclone/rclone.conf
rclone about gdrive:  # verify
```

### 8. Restore crontab
```bash
crontab crontab.txt
```

### 9. Verify
```bash
docker compose exec pandora-app php artisan migrate --force
docker compose exec pandora-app php artisan ledger:verify
docker compose exec pandora-app php artisan schedule:list
curl -k https://localhost/
```
RECOVERY

# Checksum
cd "${BACKUP_PATH}"
sha256sum * > checksums.sha256 2>/dev/null

# Compress
cd "${BACKUP_DIR}"
tar czf "${BACKUP_NAME}.tar.gz" "${BACKUP_NAME}/"
rm -rf "${BACKUP_PATH}"

echo ""
echo "=== Backup selesai: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz ==="
echo "  Size: $(du -sh ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz | cut -f1)"

# Upload ke Google Drive
if [ -x "${RCLONE}" ]; then
    echo ""
    echo "[Upload] Uploading ke Google Drive..."
    ${RCLONE} copy "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" gdrive:PANDORA-Backups/
    echo "  Upload selesai!"

    # Keep last 4 full backups remotely
    ${RCLONE} lsf gdrive:PANDORA-Backups/ --files-only | grep "pandora_full_" | sort -r | tail -n +5 | while read f; do
        ${RCLONE} deletefile "gdrive:PANDORA-Backups/${f}" 2>/dev/null
        echo "  Remote deleted old full backup: ${f}"
    done
fi

# Keep last 4 full backups locally
ls -t ${BACKUP_DIR}/pandora_full_*.tar.gz 2>/dev/null | tail -n +5 | xargs rm -f 2>/dev/null

echo ""
echo "=== Selesai ==="
