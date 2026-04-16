#!/bin/bash
# ============================================================
# init-ssl.sh — Obtain Let's Encrypt certificate for PANDORA
# ============================================================
# Usage: ./docker/nginx/init-ssl.sh
# Run once on initial deployment. After that, renewal is automatic.

set -euo pipefail

DOMAIN="pandora.kaltaraprov.go.id"
EMAIL="spbekaltaradkisp@gmail.com"
COMPOSE="docker compose"

echo "==> Step 1: Creating dummy certificate so Nginx can start..."

$COMPOSE run --rm --entrypoint "\
  mkdir -p /etc/letsencrypt/live/$DOMAIN" pandora-certbot

$COMPOSE run --rm --entrypoint "\
  openssl req -x509 -nodes -newkey rsa:2048 -days 1 \
    -keyout /etc/letsencrypt/live/$DOMAIN/privkey.pem \
    -out /etc/letsencrypt/live/$DOMAIN/fullchain.pem \
    -subj '/CN=$DOMAIN'" pandora-certbot

echo "==> Step 2: Starting Nginx with dummy certificate..."
$COMPOSE up -d pandora-nginx
sleep 3

echo "==> Step 3: Removing dummy certificate..."
$COMPOSE run --rm --entrypoint "\
  rm -rf /etc/letsencrypt/live/$DOMAIN && \
  rm -rf /etc/letsencrypt/archive/$DOMAIN && \
  rm -rf /etc/letsencrypt/renewal/$DOMAIN.conf" pandora-certbot

echo "==> Step 4: Requesting real certificate from Let's Encrypt..."
$COMPOSE run --rm pandora-certbot certonly \
  --webroot \
  --webroot-path=/var/www/certbot \
  --email "$EMAIL" \
  --domain "$DOMAIN" \
  --agree-tos \
  --no-eff-email \
  --force-renewal

echo "==> Step 5: Reloading Nginx with real certificate..."
$COMPOSE exec pandora-nginx nginx -s reload

echo ""
echo "==> Done! HTTPS is now active for $DOMAIN"
echo "    Test: curl -I https://$DOMAIN"
