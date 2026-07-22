#!/usr/bin/env bash
#
# Agri-Trials restore — reload a PostgreSQL dump and (optionally) the MinIO objects (SPEC §10).
# DESTRUCTIVE: overwrites the current database and object store.
#
#   bash scripts/restore.sh backups/db-20260722-120000.sql.gz [backups/minio-20260722-120000.tar.gz]
#
set -euo pipefail
cd "$(dirname "$0")/.."

# Keep Git-Bash/MSYS from rewriting container-side mount paths on Windows (no-op on Linux).
export MSYS_NO_PATHCONV=1

if [ -f .env ]; then set -a; . ./.env; set +a; fi

DB_USERNAME=${DB_USERNAME:-agri}
DB_DATABASE=${DB_DATABASE:-agritrials}

DUMP=${1:?Usage: bash scripts/restore.sh <db-backup.sql.gz> [minio-backup.tar.gz]}
MINIO_TAR=${2:-}
[ -f "$DUMP" ] || { echo "Database backup not found: $DUMP" >&2; exit 1; }

echo "!! This OVERWRITES the current database '$DB_DATABASE'"
[ -n "$MINIO_TAR" ] && echo "!! and REPLACES all MinIO objects from $MINIO_TAR"
echo "   Press Ctrl-C within 5s to abort."
sleep 5

echo "→ Restoring database"
gunzip -c "$DUMP" | docker compose exec -T db psql -U "$DB_USERNAME" -d "$DB_DATABASE" -v ON_ERROR_STOP=1

if [ -n "$MINIO_TAR" ]; then
  [ -f "$MINIO_TAR" ] || { echo "MinIO backup not found: $MINIO_TAR" >&2; exit 1; }
  echo "→ Restoring MinIO objects"
  VOL=$(docker volume ls -q -f name=minio_data | head -1)
  docker run --rm -v "$VOL":/data -v "$PWD":/backup busybox \
    sh -c "rm -rf /data/* && tar xzf /backup/$MINIO_TAR -C /data"
  docker compose restart minio
fi

echo "✓ Restore complete. Finalise with:"
echo "    docker compose exec app php artisan migrate --force"
echo "    docker compose exec app php artisan optimize:clear"
