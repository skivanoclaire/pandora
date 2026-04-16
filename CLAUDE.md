# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**PANDORA** (Portal Analitik Data Kehadiran ASN) — web portal untuk analitik data kehadiran Aparatur Sipil Negara (ASN) di lingkungan Pemerintah Provinsi Kalimantan Utara.

- Domain: pandora.kaltaraprov.go.id (103.156.110.117)
- Database: PostgreSQL 16 + PostGIS 3.4, nama database `pandora`
- Bahasa utama: Bahasa Indonesia (UI, komentar, dokumentasi)

## Tech Stack

| Layer | Technology |
|---|---|
| Web Portal & Orchestrator | Laravel 13 (PHP 8.4) |
| Analytics Service | Python 3.11 + FastAPI (pandas, scikit-learn, geopandas, shapely, mlxtend) |
| Database | PostgreSQL 16 + PostGIS 3.4 |
| Cache & Queue | Redis 7 (Laravel queue + cache driver) |
| Reverse Proxy | Nginx |
| Deployment | Docker Compose (single server) |

## Architecture

```
Client → Nginx (:80/:443)
           ├── /            → Laravel (PHP-FPM :9000)
           └── /api/analytics → FastAPI (:8000)

Laravel ──→ PostgreSQL (:5432)
       ──→ Redis (:6379)
       ──→ FastAPI (internal HTTP)

FastAPI ──→ PostgreSQL (via SQLAlchemy)
```

Laravel berfungsi sebagai portal utama dan orchestrator sinkronisasi data. FastAPI menyediakan endpoint analytics yang dipanggil oleh Laravel secara internal. Komunikasi antar container menggunakan Docker network.

## Directory Structure

```
├── src/                  # Laravel 11 application
├── analytics/            # Python FastAPI analytics service
├── docker/
│   ├── nginx/conf.d/     # Nginx site configuration
│   ├── php/              # PHP-FPM Dockerfile
│   └── python/           # FastAPI Dockerfile
├── docker-compose.yml
├── .env.example
└── CLAUDE.md
```

## Common Commands

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Rebuild specific service
docker compose build pandora-app
docker compose build pandora-analytics

# Laravel artisan (via container)
docker compose exec pandora-app php artisan <command>
docker compose exec pandora-app php artisan migrate
docker compose exec pandora-app php artisan queue:work

# Composer
docker compose exec pandora-app composer install
docker compose exec pandora-app composer require <package>

# Python analytics
docker compose exec pandora-analytics pip install -r requirements.txt

# Logs
docker compose logs -f pandora-app
docker compose logs -f pandora-analytics
docker compose logs -f pandora-nginx

# Database
docker compose exec pandora-db psql -U pandora -d pandora
```

## Container Names

Semua container menggunakan prefix `pandora-`:
- `pandora-app` — Laravel PHP-FPM
- `pandora-nginx` — Nginx reverse proxy
- `pandora-db` — PostgreSQL + PostGIS
- `pandora-redis` — Redis
- `pandora-analytics` — FastAPI
- `pandora-queue` — Laravel queue worker
