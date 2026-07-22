# AGENTS.md — Agri-Trials build guide

Read this first, then `docs/SPEC.md`, `docs/ERD.md`, `docs/DEVELOPMENT_RULES.md`, `docs/DESIGN.md`.

## What this is
A **variety-trials management app** for a single agricultural company (Morocco + South Europe). It
tests new crop varieties against control varieties (témoins) and helps the **Admin decide** whether a
variety is good enough to launch to production. **Not SaaS / not multi-tenant** — one install per
company, with many admin-created **workspaces** inside it. Benchmarked against `scpcseeds.netlify.app`
(competitor screenshots in `screenshots/`).

## The 6 golden rules (do not violate)
1. **Metadata-driven.** Never hardcode business fields, measurements, or lifecycle stages. Route them
   through `field_definitions` + `custom_data`, the measurement catalog, and workflow templates.
   (DEVELOPMENT_RULES §3.)
2. **Design-led.** UI/UX is the #1 factor. Build every screen from the shared design system; a screen
   isn't done until it matches `docs/DESIGN.md`.
3. **Multilingual content.** Translatable attributes (system + custom) are stored as `{locale: value}`
   JSON. Never inline user-facing strings; never overwrite the whole translation JSON on write.
   RTL-safe layouts (Arabic).
4. **Importable.** Every entity supports CSV/XLSX import/export, driven by the same metadata.
5. **RBAC + scoped.** Every model has a Policy; queries respect workspace membership; only Admin edits
   config. Decisions are **immutable + snapshotted**.
6. **Docker, self-hosted per company.** Seeders must always boot a working app with sensible defaults.

## Stack
Laravel 13 + Inertia + React 18 + TypeScript + Vite + Tailwind · PostgreSQL 16 · Redis · Docker.
Packages: spatie permission / medialibrary / activitylog, maatwebsite/excel, translatable trait.

## Where things are
- `docs/SPEC.md` — product spec, modules, phased roadmap (P0–P8), decisions log (§12).
- `docs/ERD.md` — data model, Mermaid ERD, table dictionary, invariants.
- `docs/DEVELOPMENT_RULES.md` — coding rules, project layout, metadata/i18n/import patterns, testing.
- `docs/DESIGN.md` — design system, tokens, components, screen priority.
- `docs/DEPLOYMENT.md` — Docker stack + per-company install.
- `screenshots/` — competitor reference.

## Build order (see SPEC §9)
P1 metadata foundation + auth → P2 référentiels + measurement catalog → P3 trials + workflow editor →
P4 stock → P5 harvests + decision engine → P6 dashboards/reporting → P7 responsive + i18n →
P8 hardening + Docker delivery. Cross-cutting: import/export, translation, design system.

## Current status
Real **Laravel 13 + Inertia + React + TS** app running in Docker (nginx/php-fpm/postgres/redis/queue/
vite) at http://localhost:8088. Breeze auth in place. **P1 vertical slice done**: design system +
theme (admin branding) + app shell ported into `resources/js`; **Essais list served from
`TrialController` → Inertia → React** with seeded Postgres data (`trials` table, `custom_data` JSONB).
Seeder: admin `admin@agri.test` / `password` + 6 trials. **Metadata engine BUILT**: `field_definitions`
+ `MetadataService` (dynamic validation + system/custom_data split) driving a metadata-driven
**Fournisseurs** screen (DynamicForm + DynamicTable, custom `linkedin_url`/`payment_terms` fields,
verified create + validation). Standalone `frontend/` remains the design reference. Next: more
référentiels + translatable per-locale UI + CSV/XLSX import; port Essai detail + Décision screens.

## Working rules for agents
- State which SPEC phase/module your task belongs to before coding.
- Extend the dynamic engines; don't hardcode. If tempted to hardcode a field/measurement/stage, stop.
- Keep `docs/` and seeders in sync with any model or rule change (same PR).
- Don't silently contradict a locked decision (SPEC §12) — surface it.
