#!/usr/bin/env bash
#
# Agri-Trials backup — PostgreSQL dump + MinIO object store (SPEC §10).
# Both are captured at the same run so a restore uses one consistent recovery point.
#
#   bash scripts/backup.sh
#
# Env (from .env or the shell): DB_USERNAME, DB_DATABASE, BACKUP_DIR (default: backups),
# BACKUP_RETENTION (default: 14 most-recent kept).
#
set -euo pipefail
cd "$(dirname "$0")/.."

# Keep Git-Bash/MSYS from rewriting the container-side ":/backup" mount path on Windows (no-op on Linux).
export MSYS_NO_PATHCONV=1

# Load .env if present (without clobbering already-exported vars is fine here).
if [ -f .env ]; then set -a; . ./.env; set +a; fi

DB_USERNAME=${DB_USERNAME:-agri}
DB_DATABASE=${DB_DATABASE:-agritrials}
OUT=${BACKUP_DIR:-backups}
RETENTION=${BACKUP_RETENTION:-14}
TS=$(date +%Y%m%d-%H%M%S)
mkdir -p "$OUT"

echo "→ PostgreSQL dump ($DB_DATABASE)"
docker compose exec -T db pg_dump -U "$DB_USERNAME" -d "$DB_DATABASE" --clean --if-exists \
  | gzip > "$OUT/db-$TS.sql.gz"

echo "→ MinIO objects"
VOL=$(docker volume ls -q -f name=minio_data | head -1 || true)
if [ -n "$VOL" ]; then
  docker run --rm -v "$VOL":/data -v "$PWD/$OUT":/backup busybox \
    tar czf "/backup/minio-$TS.tar.gz" -C /data .
else
  echo "  (minio_data volume not found — skipping object backup)"
fi

echo "→ Pruning (keeping newest $RETENTION of each)"
ls -1t "$OUT"/db-*.sql.gz 2>/dev/null | tail -n +$((RETENTION + 1)) | xargs -r rm -f
ls -1t "$OUT"/minio-*.tar.gz 2>/dev/null | tail -n +$((RETENTION + 1)) | xargs -r rm -f

echo "✓ Backup complete → $OUT/db-$TS.sql.gz"
echo "  Copy $OUT/ to offsite storage for durability."
