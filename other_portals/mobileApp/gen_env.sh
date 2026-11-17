#!/usr/bin/env bash
# Reads .env and writes env.json for flutter run --dart-define-from-file=env.json
set -euo pipefail

ENV_FILE="$(dirname "$0")/.env"
OUT_FILE="$(dirname "$0")/env.json"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Error: $ENV_FILE not found. Copy .env.example to .env and fill in your values." >&2
  exit 1
fi

# shellcheck disable=SC1090
source "$ENV_FILE"

ENV="${ENV:-lan}"

if [[ "$ENV" == "prod" ]]; then
  BASE_URL="$API_BASE_URL_PROD"
  TENANT="$API_TENANT_PROD"
else
  BASE_URL="$API_BASE_URL_LAN"
  TENANT="$API_TENANT_LAN"
fi

cat > "$OUT_FILE" <<JSON
{
  "API_BASE_URL": "$BASE_URL",
  "API_TENANT": "$TENANT"
}
JSON

echo "Generated env.json → ENV=$ENV  BASE_URL=$BASE_URL  TENANT=$TENANT"
