#!/usr/bin/env bash
set -euo pipefail

SOURCE_DIR="/home/dad/dad"
WEB_ROOT="/var/www/dad"
ENV_FILE="/var/www/.env"
DB_NAME="portfolio_app"
DB_USER="portfolio_app"
DOMAIN="dennisadizon.online"

if [[ ! -f "$SOURCE_DIR/index.php" ]]; then
  echo "Application not found at $SOURCE_DIR" >&2
  exit 1
fi

echo "Installing deployment utilities..."
sudo apt-get update
sudo apt-get install -y rsync nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-gd php8.3-curl

echo "Deploying application..."
sudo mkdir -p "$WEB_ROOT"
sudo rsync -a --delete \
  --exclude='.git/' --exclude='.env' --exclude='.dev/' \
  --exclude='tmp/pdfs/professional/' --exclude='tmp/pdfs/resume/' \
  "$SOURCE_DIR/" "$WEB_ROOT/"
sudo mkdir -p "$WEB_ROOT/output/uploads"
sudo chown -R root:www-data "$WEB_ROOT"
sudo find "$WEB_ROOT" -type d -exec chmod 0755 {} \;
sudo find "$WEB_ROOT" -type f -exec chmod 0644 {} \;
sudo chown www-data:www-data "$WEB_ROOT/output/uploads"
sudo chmod 0775 "$WEB_ROOT/output/uploads"

DB_PASSWORD="$(openssl rand -hex 24)"

echo "Creating MySQL database and least-privilege application user..."
sudo mysql <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
sudo mysql "$DB_NAME" < "$WEB_ROOT/database/schema.sql"

echo "Writing protected production environment..."
SESSION_SECRET="$(openssl rand -hex 32)"
sudo tee "$ENV_FILE" >/dev/null <<ENV
APP_ENV=production
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
SESSION_LIFETIME_DAYS=14
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_MINUTES=15
SITE_ORIGIN=https://${DOMAIN}
SESSION_SECRET=${SESSION_SECRET}
ENV
sudo chown root:www-data "$ENV_FILE"
sudo chmod 0640 "$ENV_FILE"

echo "Configuring Nginx..."
sudo tee "/etc/nginx/sites-available/${DOMAIN}" >/dev/null <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${WEB_ROOT};
    index index.php;
    client_max_body_size 64m;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\. { deny all; }
    location ~ ^/(database|tools|tmp)(/|\$) { deny all; }
    location ^~ /output/uploads/ { try_files \$uri =404; }

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy same-origin always;
}
NGINX
sudo ln -sfn "/etc/nginx/sites-available/${DOMAIN}" "/etc/nginx/sites-enabled/${DOMAIN}"
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

echo "Seeding portfolio systems..."
sudo -u www-data php "$WEB_ROOT/tools/seed_content.php" --force

echo
echo "Creating the administrator. Save the generated password shown below."
sudo -u www-data php "$WEB_ROOT/tools/create_admin.php" "denzyodfm@gmail.com" "Dennis Dizon"

echo
echo "Deployment complete: http://${DOMAIN}"
echo "Next: point DNS to the public IP, then install a TLS certificate."
