#!/bin/bash
# ============================================================
# renew-ssl.sh — Auto-renew Let's Encrypt certificate
# ============================================================
# Called by cron. Certbot only renews if cert expires within 30 days.

set -euo pipefail
cd /home/pandora/pandora

docker compose run --rm pandora-certbot renew --quiet
docker compose exec pandora-nginx nginx -s reload
