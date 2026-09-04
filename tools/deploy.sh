#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="/home/dad/dad"
WEB_ROOT="/var/www/dad"
DOMAIN="dennisadizon.online"

if [[ "$(id -un)" != "dad" ]]; then
  echo "Run this command as the dad deployment user." >&2
  exit 1
fi

if [[ ! -d "$SOURCE_DIR/.git" || ! -f "$SOURCE_DIR/index.php" ]]; then
  echo "Deployment checkout is missing at $SOURCE_DIR." >&2
  exit 1
fi

if [[ ! -d "$WEB_ROOT" || ! -w "$WEB_ROOT" ]]; then
  echo "$WEB_ROOT is not writable by dad. Complete the one-time ownership setup first." >&2
  exit 1
fi

echo "Updating deployment checkout..."
git -C "$SOURCE_DIR" pull --ff-only origin main

echo "Checking PHP syntax..."
while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find "$SOURCE_DIR" -type f -name '*.php' -print0)

echo "Publishing application..."
rsync -a --delete-delay \
  --exclude='.git/' \
  --exclude='.env' \
  --exclude='.dev/' \
  --exclude='.21st/' \
  --exclude='deploy/' \
  --exclude='tmp/' \
  --exclude='output/uploads/' \
  "$SOURCE_DIR/" "$WEB_ROOT/"

echo "Checking the local site..."
status="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' \
  --header "Host: $DOMAIN" http://127.0.0.1/)"
if [[ "$status" != "200" ]]; then
  echo "Deployment finished, but the local health check returned HTTP $status." >&2
  exit 1
fi

echo "Deployment complete at commit $(git -C "$SOURCE_DIR" rev-parse --short HEAD) (HTTP $status)."
