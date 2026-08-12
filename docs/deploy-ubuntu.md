# Deploying on Ubuntu 24.04

Nginx + PHP-FPM. Everything the application can do for performance and security
it already does; this file covers the part that only the web server can do —
compression, cache headers, and keeping the document root honest.

---

## 1. Packages

```bash
sudo apt update
sudo apt install -y nginx mysql-server \
    php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
    php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath \
    unzip git curl
```

`php8.3-gd` is **required**, not optional: the image pipeline (resize, WebP
derivatives, EXIF stripping) runs on it. Without it every upload is stored
unoptimised at full size, which quietly undoes the LCP work.

Node 20+ is needed to build the front end:

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

---

## 2. Application

```bash
sudo mkdir -p /var/www/artaleca && sudo chown -R "$USER":www-data /var/www/artaleca
git clone <repo> /var/www/artaleca && cd /var/www/artaleca

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
# set DB_CONNECTION=mysql and the DB_* credentials, APP_ENV=production,
# APP_DEBUG=false, APP_URL=https://your-domain
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
```

Ownership: the web server needs to write to exactly two trees, and nothing else.

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo find /var/www/artaleca -type d -exec chmod 755 {} \;
sudo find /var/www/artaleca -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 640 .env && sudo chown "$USER":www-data .env
```

---

## 3. Nginx

The document root is `public/`, never the project root — that single line is
what keeps `.env`, `storage/` and `vendor/` unreachable over HTTP.

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/artaleca/public;

    ssl_certificate     /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;

    index index.php;
    charset utf-8;

    # No directory listing anywhere, and no serving of dotfiles.
    autoindex off;
    location ~ /\. { deny all; access_log off; log_not_found off; }

    client_max_body_size 12M;   # matches the 10 MB document upload limit

    # ── Compression ──────────────────────────────────────────────────────
    gzip on;
    gzip_vary on;
    gzip_comp_level 6;
    gzip_min_length 512;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml application/javascript
               application/json application/xml image/svg+xml
               application/manifest+json;

    # Requires nginx-module-brotli. Optional — gzip alone is fine.
    # brotli on;
    # brotli_comp_level 5;
    # brotli_types text/plain text/css application/javascript application/json
    #              image/svg+xml application/xml;

    # ── Cache headers ────────────────────────────────────────────────────
    # Vite writes a content hash into every build filename, so a changed file
    # is a changed URL. That is what makes `immutable` correct here: the
    # browser never revalidates, and a deploy invalidates by renaming.
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    # Uploaded media has a random generated filename, and the font is versioned
    # by hand — both are equally safe to pin for a year.
    location ~* \.(woff2|avif|webp|jpe?g|png|gif|svg|ico)$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    # ── Uploads must never execute ───────────────────────────────────────
    # Media filenames and extensions are already generated from the sniffed
    # MIME type, so a .php can't be stored in the first place. This is the
    # second lock: an upload directory that can run code is the worst possible
    # outcome, so it is closed at the server as well as at the application.
    location ^~ /storage/ {
        location ~ \.php$ { deny all; }
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;   # no version disclosure
        include fastcgi_params;
    }

    # Anything else that reached PHP-FPM would be a misconfiguration.
    location ~ /\.php$ { deny all; }
}

server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}
```

HSTS is sent by the application itself (`SecurityHeaders`) once the request
arrives over HTTPS, so it must not be duplicated here.

```bash
sudo nginx -t && sudo systemctl reload nginx
```

---

## 4. PHP-FPM

```ini
; /etc/php/8.3/fpm/conf.d/99-artaleca.ini
expose_php = Off                 ; no PHP version in response headers
display_errors = Off             ; never leak a stack trace to a visitor
log_errors = On

upload_max_filesize = 12M         ; the app clamps its own limit to this
post_max_size = 14M              ; must exceed upload_max_filesize
memory_limit = 256M              ; headroom for GD to decode a large upload

opcache.enable = 1
opcache.memory_consumption = 192
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0  ; production: reload FPM to pick up new code
```

```bash
sudo systemctl restart php8.3-fpm
```

Ubuntu ships `upload_max_filesize = 2M`, and PHP rejects anything larger before
a single validation rule runs — the only error left is "failed to upload", with
no size in it. The application clamps its advertised limits to whatever PHP
accepts, so the form always names a number the server can honour; raising these
two values is what lifts it.

**Running under `php artisan serve` instead?** That uses the CLI configuration,
so the same two lines belong in `/etc/php/8.3/cli/conf.d/99-artaleca.ini` — and
there is no service to restart, just start the server again. Check what is
actually in force with:

```bash
php -r 'echo ini_get("upload_max_filesize"), " / ", ini_get("post_max_size"), PHP_EOL;'
```

With `validate_timestamps = 0`, a deploy must end in
`sudo systemctl reload php8.3-fpm` or the old bytecode keeps serving.

---

## 5. Deploying an update

One line, safe to re-run, and it stops at the first failure rather than
half-deploying:

```bash
cd /var/www/artaleca && git config --global --add safe.directory /var/www/artaleca; git fetch origin claude/industrial-company-website-6vty0h && git reset --hard FETCH_HEAD && composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan migrate --force && php artisan storage:link && php artisan optimize && sudo chown -R www-data:www-data storage bootstrap/cache public/build database && sudo systemctl reload "$(systemctl list-units --type=service --plain --no-legend 'php*-fpm.service' | awk '{print $1}')" nginx
```

Four things in there are not obvious:

- **`git reset --hard`, not `git pull`.** A deploy target has no local work
  worth keeping, and a merge conflict on a server is a worse outcome than
  discarding whatever caused it. Everything that must survive — `.env`, the
  database, `storage/app/public` — is outside the working tree or ignored.
- **`php artisan optimize`, not three `*:cache` commands.** Artisan takes one
  command per invocation; `config:cache route:cache view:cache` passes the last
  two as *arguments* to the first and fails.
- **The FPM service name is looked up.** Hard-coding `php8.3-fpm` breaks the
  moment the box is on 8.4, which is what a current Ubuntu installs.
- **`safe.directory` is set first, with `;` rather than `&&`.** Git refuses to
  operate on a tree owned by another user, which is exactly the case when the
  files belong to `www-data` and the deploy runs as root. It is already set on
  the second run, so it must not be allowed to fail the chain.

Reloading FPM is what clears OPcache. Skipping it leaves the old bytecode
serving while the new files sit on disk — the most confusing failure mode
there is, because everything looks deployed.

---

## 6. Putting assets on a CDN

Set `ASSET_URL=https://cdn.example.com` and point the CDN at the origin. The
CSP picks the origin up automatically — see `SecurityHeaders::origin()` — so no
policy edit is needed. The CDN must forward `Accept-Encoding` in its cache key,
or a gzip-capable client can be served a response cached for one that was not.

---

## 7. Verifying it actually worked

Do not take the config on trust; the whole point of pinning these is that they
are checkable:

```bash
# Security headers present, and no PHP version disclosed
curl -sI https://your-domain.com/fa | grep -iE 'content-security|strict-transport|x-frame|referrer|permissions|x-powered'

# Build assets immutable, and compressed
curl -sI -H 'Accept-Encoding: gzip' https://your-domain.com/build/assets/app-*.css \
  | grep -iE 'cache-control|content-encoding'

# The document root does not expose the project root
curl -s -o /dev/null -w '%{http_code}\n' https://your-domain.com/.env        # 403/404
curl -s -o /dev/null -w '%{http_code}\n' https://your-domain.com/storage/    # 403
curl -s -o /dev/null -w '%{http_code}\n' https://your-domain.com/vendor/     # 404

# Production really is production
curl -s https://your-domain.com/robots.txt        # must not be "Disallow: /"
```
