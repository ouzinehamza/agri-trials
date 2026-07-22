# Agri-Trials

Variety-trials management app for a single agricultural company (Morocco + South Europe).
**Laravel 13 + React + Inertia.js + TypeScript**, PostgreSQL 16, Redis — Dockerized, self-hosted per
company. See [`docs/`](docs/) for the full spec, ERD, dev rules, design system, and deployment.

## Stack
- **Backend:** Laravel 13 (PHP 8.4)
- **Frontend:** React 18 + TypeScript via Inertia.js (Vite + Tailwind)
- **DB:** PostgreSQL 16 · **Cache/queue:** Redis · **Auth:** Laravel Breeze (Inertia-React)

## Run (Docker)
```bash
docker compose up -d --build          # app, web, db, redis, queue (+ vite)
docker compose exec app php artisan migrate    # once, sets up the schema
npm install && npm run build          # build Inertia assets (needs local Node 22)
```
Open **http://localhost:8088** → register at `/register`, then `/dashboard`.

### Frontend dev (HMR)
```bash
docker compose up -d vite             # Vite dev server on :5173 with hot reload
# or locally: npm run dev
```

## Ports
| Service | URL |
|---|---|
| App (nginx) | http://localhost:8088 |
| Vite HMR | http://localhost:5173 |
| PostgreSQL | localhost:5433 (db `agritrials`, user `agri`) |

## Layout
- `app/`, `resources/js/`, `routes/`, `database/` — the Laravel + Inertia app
- `docker/` + `docker-compose.yml` — container setup
- `docs/` — SPEC, ERD, DEVELOPMENT_RULES, DESIGN, DEPLOYMENT
- `frontend/` — standalone React **prototype** / component library (design reference; ports into
  `resources/js` as we build the real Inertia pages)

## Status
Scaffolded and running. Next: port the prototype's design system + screens into Inertia pages and
build the metadata foundation (SPEC phase P1).
