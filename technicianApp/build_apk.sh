#!/usr/bin/env bash
# Builds the release APK and names it after API_NAME from the env file.
#   ./build_apk.sh              -> uses env.json
#   ./build_apk.sh env.x.json   -> uses another tenant's env file
set -euo pipefail
cd "$(dirname "$0")"

ENV_FILE="${1:-env.json}"
if [[ ! -f "$ENV_FILE" ]]; then
  echo "Env file not found: $ENV_FILE" >&2
  exit 1
fi

NAME=$(plutil -extract API_NAME raw -o - "$ENV_FILE")
VERSION=$(grep '^version:' pubspec.yaml | awk '{print $2}')

flutter build apk --release --split-per-abi --target-platform android-arm64 \
  --dart-define-from-file="$ENV_FILE"

OUT="build/app/outputs/flutter-apk/${NAME}-fixmate-${VERSION}.apk"
cp build/app/outputs/flutter-apk/app-arm64-v8a-release.apk "$OUT"
echo "→ $OUT"
