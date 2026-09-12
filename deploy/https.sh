#!/usr/bin/env bash
#
# Puts the site on HTTPS: one certificate for all four names, the canonical
# name serving and the other three redirecting to it.
#
#     sudo bash deploy/https.sh
#
# Lives in the repository rather than in a document on purpose. Every chat and
# issue tracker turns a bare `www.` into a hyperlink when the text is copied,
# and a mangled `server_name` is the kind of thing nginx accepts without
# complaint and only misbehaves about later. Pulling the file is paste-proof.
#
# Safe to run more than once: an existing certificate is reused rather than
# re-issued, which also keeps clear of Let's Encrypt's five-per-week limit.

set -euo pipefail

APEX=${APEX:-artaleca.com}
ALIASES=${ALIASES:-www.artaleca.com artaleca.ir www.artaleca.ir}
ROOT=${ROOT:-/var/www/artaleca}
EMAIL=${EMAIL:-info@artaleca.com}

ALL="$APEX $ALIASES"
SITE=/etc/nginx/sites-available/artaleca

[ "$(id -u)" -eq 0 ] || { echo "run this as root"; exit 1; }
[ -f "$ROOT/public/index.php" ] || { echo "no application at $ROOT"; exit 1; }

SOCK=$(ls /run/php/php*-fpm.sock 2>/dev/null | head -1)
[ -n "$SOCK" ] || { echo "php-fpm is not running — no socket in /run/php"; exit 1; }

# ── 1. DNS ─────────────────────────────────────────────────────────────────
# Checked rather than assumed: certbot proves you own a name by fetching a
# file from it, so a name pointing somewhere else fails the whole request and
# takes the other three down with it.
echo "— DNS —"
here=$(curl -s -m 10 https://api.ipify.org || true)
bad=0
for d in $ALL; do
    got=$(getent hosts "$d" | awk '{print $1; exit}' || true)
    printf '  %-24s %s\n' "$d" "${got:-NO RECORD}"
    [ -n "$got" ] || bad=1
done
[ -n "$here" ] && echo "  this server: $here"
if [ "$bad" = 1 ]; then
    echo "  ↑ a name does not resolve. Fix the A records first."
    exit 1
fi

# ── 2. Plain HTTP, so the certificate check can reach the webroot ──────────
echo "— HTTP —"
printf 'server {
    listen 80;
    listen [::]:80;
    server_name %s;
    root %s/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;
    location ^~ /.well-known/acme-challenge/ { allow all; }
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \\.php$ {
        fastcgi_pass unix:%s;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        include fastcgi_params;
    }
}
' "$ALL" "$ROOT" "$SOCK" > "$SITE"

ln -sf "$SITE" /etc/nginx/sites-enabled/artaleca
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
echo "  127.0.0.1/fa -> $(curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/fa)"

# ── 3. The certificate ─────────────────────────────────────────────────────
echo "— certificate —"
if [ -f "/etc/letsencrypt/live/$APEX/fullchain.pem" ]; then
    echo "  already have one for $APEX, reusing it"
else
    args=""
    for d in $ALL; do args="$args -d $d"; done
    # `certonly --webroot` leaves the nginx config alone, so what runs below is
    # what this script wrote rather than whatever the nginx plugin rewrote.
    command -v certbot >/dev/null || apt install -y certbot
    # shellcheck disable=SC2086
    certbot certonly --webroot -w "$ROOT/public" $args \
        --agree-tos -m "$EMAIL" --non-interactive
fi
ls "/etc/letsencrypt/live/$APEX/"

# ── 4. The real config ─────────────────────────────────────────────────────
# `$host` rather than `$server_name`, because the redirect has to fire on the
# name the visitor actually typed.
echo "— HTTPS —"
printf 'server {
    listen 80;
    listen [::]:80;
    server_name %s;
    root %s/public;
    location ^~ /.well-known/acme-challenge/ { allow all; }
    location / { return 301 https://%s$request_uri; }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name %s;

    ssl_certificate /etc/letsencrypt/live/%s/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/%s/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;

    if ($host != "%s") { return 301 https://%s$request_uri; }

    root %s/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;
    autoindex off;

    add_header Strict-Transport-Security "max-age=31536000" always;

    gzip on;
    gzip_vary on;
    gzip_comp_level 6;
    gzip_min_length 512;
    gzip_types text/plain text/css application/javascript application/json image/svg+xml application/xml;

    location ~ /\\. { deny all; access_log off; }

    # Vite fingerprints these, so a year is safe and a redeploy is picked up
    # immediately anyway — the filename changes.
    location ^~ /build/ { expires 1y; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }
    location ~* \\.(woff2|avif|webp|jpe?g|png|gif|svg|ico)$ { expires 1y; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }

    # Uploads live here. Refusing to execute PHP under it is what stops a
    # picture upload from becoming code execution.
    location ^~ /storage/ { location ~ \\.php$ { deny all; } }

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \\.php$ {
        fastcgi_pass unix:%s;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        include fastcgi_params;
    }
}
' "$ALL" "$ROOT" "$APEX" "$ALL" "$APEX" "$APEX" "$APEX" "$APEX" "$ROOT" "$SOCK" > "$SITE"

nginx -t && systemctl reload nginx

# ── 5. The application's own idea of its address ───────────────────────────
# This is the step that matters beyond nginx: canonical tags, hreflang, the
# sitemap and every generated link come from APP_URL, so redirecting without
# it leaves the alias pages still *claiming* to be canonical — which is the
# version a search engine keeps.
#
# APP_ENV=production belongs here and not at install time: it forces every
# generated URL to https, which is right behind a certificate and breaks the
# site outright before there is one.
echo "— application —"
cd "$ROOT"
sed -i "s|^APP_URL=.*|APP_URL=https://$APEX|; s|^APP_ENV=.*|APP_ENV=production|" .env
php artisan optimize
systemctl reload "php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"

# ── 6. Proof ───────────────────────────────────────────────────────────────
echo "— check —"
for d in $ALIASES; do
    printf '  %-24s ' "https://$d"
    curl -sk -o /dev/null -m 10 -w '%{http_code} %{redirect_url}\n' "https://$d"
done
printf '  %-24s ' "https://$APEX/fa"
curl -s -o /dev/null -m 10 -w '%{http_code}\n' "https://$APEX/fa"
echo -n '  canonical tag: '
curl -s -m 10 "https://$APEX/fa" | grep -o 'rel="canonical" href="[^"]*"' | head -1

echo
echo "Aliases should say 301 and name https://$APEX; the canonical one 200."
echo "Renewal runs on certbot's own timer — confirm with: certbot renew --dry-run"
