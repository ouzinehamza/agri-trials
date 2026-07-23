#!/usr/bin/env bash
set -Eeuo pipefail

required=(
  APP_IMAGE
  NGINX_IMAGE
  APP_KEY
  APP_URL
  APP_HOST
  DB_PASSWORD
  REDIS_PASSWORD
  MINIO_ROOT_PASSWORD
)

for name in "${required[@]}"; do
  if [ -z "${!name:-}" ]; then
    echo "Missing required environment variable: ${name}" >&2
    exit 1
  fi
done

export COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-agri-trials}"
export TRAEFIK_NETWORK="${TRAEFIK_NETWORK:-gateway}"

if ! docker network inspect "${TRAEFIK_NETWORK}" >/dev/null 2>&1; then
  echo "Traefik network '${TRAEFIK_NETWORK}' does not exist on this host." >&2
  exit 1
fi

compose() {
  docker compose -p "${COMPOSE_PROJECT_NAME}" -f /deploy/compose.production.yml "$@"
}

echo "Ensuring public base services are available..."
compose pull postgres redis minio minio-init

echo "Starting Agri-Trials stack..."
compose up -d --remove-orphans

echo "Running database migrations..."
compose exec -T app php artisan migrate --force

echo "Seeding default metadata and demo baseline..."
compose exec -T app php artisan db:seed --force

echo "Running production preflight..."
compose exec -T app php artisan agri:preflight

echo "Restarting background workers..."
compose up -d --force-recreate --no-deps worker scheduler

echo "Agri-Trials deployment complete."
