# Deploying on Ubuntu 24.04

Nginx + PHP-FPM. Everything the application can do for performance and security
it already does; this file covers the part that only the web server can do —
compression, cache headers, and keeping the document root honest.

---

## 0. A bare server, start to finish

Five pastes on a fresh Ubuntu box, as `root`. Sections 1–4 explain each of
them; this is the same thing without the prose.

The database is SQLite, which is what `.env.example` selects — no MySQL to
install, no credentials to invent, and a backup is one file. Section 2 covers
switching to MySQL if the traffic ever justifies it.

```bash
# 1. Packages. Unversioned names on purpose: they follow whatever PHP the
#    distribution ships, so this does not rot when Ubuntu moves to 8.4.
apt update && apt install -y nginx git unzip curl \
    php-fpm php-cli php-mbstring php-xml php-curl php-zip php-gd php-intl php-bcmath php-sqlite3 \
  && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt install -y nodejs \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

```bash
# 2. The application. Set the two variables on the first line first.
SITE=http://YOUR-IP-OR-DOMAIN; ADMPW='choose-a-long-password'; \
git clone -b claude/industrial-company-website-6vty0h https://github.com/ferya3/artaleca.git /var/www/artaleca \
  && cd /var/www/artaleca \
  && composer install --no-dev --optimize-autoloader && npm ci && npm run build \
  && cp .env.example .env && touch database/database.sqlite \
  && php artisan key:generate \
  && sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|; s|^APP_URL=.*|APP_URL=$SITE|; s|^ADMIN_PASSWORD=.*|ADMIN_PASSWORD=$ADMPW|" .env \
  && php artisan migrate --force --seed && php artisan storage:link && php artisan optimize
```

```bash
# 3. Ownership. The web server writes to exactly three trees and nothing else
#    — the third is `database/`, because SQLite needs to write the directory
#    as well as the file.
cd /var/www/artaleca \
  && chown -R www-data:www-data storage bootstrap/cache database \
  && chmod -R 775 storage bootstrap/cache database \
  && chmod 640 .env
```

```bash
# 4. Nginx, over plain HTTP for now. The socket path is looked up rather than
#    guessed, for the same reason the package names are unversioned.
cat > /etc/nginx/sites-available/artaleca <<EOF
server {
    listen 80;
    server_name _;
    root /var/www/artaleca/public;
    index index.php;
    charset utf-8;
    client_max_body_size 12M;
    autoindex off;
    location ~ /\. { deny all; access_log off; }
    gzip on; gzip_vary on; gzip_comp_level 6; gzip_min_length 512;
    gzip_types text/plain text/css application/javascript application/json image/svg+xml application/xml;
    location ^~ /build/ { expires 1y; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }
    location ~* \.(woff2|avif|webp|jpe?g|png|gif|svg|ico)\$ { expires 1y; add_header Cache-Control "public, max-age=31536000, immutable"; access_log off; }
    location ^~ /storage/ { location ~ \.php\$ { deny all; } }
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php\$ {
        fastcgi_pass unix:$(ls /run/php/php*-fpm.sock | head -1);
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        include fastcgi_params;
    }
}
EOF
ln -sf /etc/nginx/sites-available/artaleca /etc/nginx/sites-enabled/artaleca \
  && rm -f /etc/nginx/sites-enabled/default && nginx -t && systemctl reload nginx
```

```bash
# 5. PHP limits. Ubuntu ships upload_max_filesize = 2M, and PHP rejects a
#    larger file before any validation rule runs.
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;'); \
printf 'expose_php=Off\ndisplay_errors=Off\nlog_errors=On\nupload_max_filesize=12M\npost_max_size=14M\nmemory_limit=256M\nopcache.enable=1\nopcache.memory_consumption=192\nopcache.max_accelerated_files=20000\n' \
  > /etc/php/$PHPV/fpm/conf.d/99-artaleca.ini \
  && systemctl restart php$PHPV-fpm
```

The site is now on `http://YOUR-IP`, and the panel at `/admin` with
`admin@artaleca.com` and the password set in step 2.

**Before pointing a domain at it**, get a certificate
(`apt install -y certbot python3-certbot-nginx && certbot --nginx -d your-domain.com`)
and only then set `APP_ENV=production` in `.env` followed by
`php artisan optimize`. In `production` the application forces every generated
URL to `https`, which is right behind TLS and breaks the site outright while it
is still being served over plain HTTP.

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
git clone -b claude/industrial-company-website-6vty0h \
    https://github.com/ferya3/artaleca.git /var/www/artaleca && cd /var/www/artaleca

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
# APP_URL=https://your-domain, and the DB_* credentials if not using SQLite.
php artisan migrate --force --seed
php artisan storage:link
php artisan optimize
```

**`APP_ENV` is not a cosmetic choice.** In `production` the app forces every
generated URL to `https`, which is right behind TLS and breaks the site
outright on a server still being set up over plain HTTP — every link and asset
points at a scheme that is not answering yet. Serve over HTTPS and set
`APP_ENV=production`; until the certificate is in place leave it `local` and
set `APP_DEBUG=false` by hand, which is the part that actually matters for not
leaking stack traces.

`php artisan --version` will tell you which PHP is in use. A current Ubuntu
installs 8.4 rather than the 8.3 named above; the package names change with it
and nothing else does.

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

## 3b. The domains

`artaleca.com` is the canonical name. `artaleca.ir` and both `www` forms
redirect to it permanently, so a page is never reachable at four addresses —
which is the thing that splits a search ranking four ways and makes the
analytics meaningless.

**DNS first.** Four A records at the registrar, all pointing at the server:
`artaleca.com`, `www.artaleca.com`, `artaleca.ir`, `www.artaleca.ir`. Nothing
below works until they resolve, because Let's Encrypt proves you own a name by
fetching a file from it.

```bash
for d in artaleca.com www.artaleca.com artaleca.ir www.artaleca.ir; do echo -n "$d "; dig +short "$d" | tail -1; done
```

**1. Plain HTTP on every name**, which is all the certificate check needs:

```bash
cat > /etc/nginx/sites-available/artaleca <<EOF
# Plain HTTP, every name. Enough for Let's Encrypt to validate all four.
server {
    listen 80;
    listen [::]:80;
    server_name artaleca.com www.artaleca.com artaleca.ir www.artaleca.ir;
    root /var/www/artaleca/public;
    index index.php;
    charset utf-8;

    location ^~ /.well-known/acme-challenge/ { allow all; }

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~ \\.php\$ {
        fastcgi_pass unix:$(ls /run/php/php*-fpm.sock | head -1);
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
ln -sf /etc/nginx/sites-available/artaleca /etc/nginx/sites-enabled/artaleca
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

**2. One certificate covering all four names.** `certonly --webroot` leaves the
nginx config alone, so what runs is what is written here rather than whatever
the plugin rewrote it into:

```bash
apt install -y certbot
certbot certonly --webroot -w /var/www/artaleca/public \
    -d artaleca.com -d www.artaleca.com -d artaleca.ir -d www.artaleca.ir \
    --agree-tos -m info@artaleca.com --non-interactive
```

**3. The real config** — the site on the canonical name, everything else a 301:

```bash
cat > /etc/nginx/sites-available/artaleca <<EOF
# ── artaleca.com — the site ──────────────────────────────────────────────
server {
    # `listen ... http2` rather than the newer `http2 on;` directive: that one
    # arrived in nginx 1.25.1 and Ubuntu 24.04 ships 1.24, where it is an
    # unknown directive and the whole config fails to load.
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name artaleca.com;
    root /var/www/artaleca/public;

    ssl_certificate     /etc/letsencrypt/live/artaleca.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/artaleca.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    index index.php;
    charset utf-8;
    client_max_body_size 12M;

    autoindex off;
    location ~ /\\. { deny all; access_log off; log_not_found off; }

    gzip on;
    gzip_vary on;
    gzip_comp_level 6;
    gzip_min_length 512;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml application/javascript application/json
               application/xml image/svg+xml application/manifest+json;

    # Vite writes a content hash into every build filename, so a changed file is
    # a changed URL — which is what makes \`immutable\` correct here.
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    location ~* \\.(woff2|avif|webp|jpe?g|png|gif|svg|ico)\$ {
        expires 1y;
        add_header Cache-Control "public, max-age=31536000, immutable";
        access_log off;
    }

    # An upload directory that can run code is the worst possible outcome.
    location ^~ /storage/ {
        location ~ \\.php\$ { deny all; }
    }

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }

    location ~ \\.php\$ {
        fastcgi_pass unix:$(ls /run/php/php*-fpm.sock | head -1);
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_hide_header X-Powered-By;
        include fastcgi_params;
    }
}

# ── .ir and both www — one permanent redirect to the canonical name ──────
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.artaleca.com artaleca.ir www.artaleca.ir;

    ssl_certificate     /etc/letsencrypt/live/artaleca.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/artaleca.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    return 301 https://artaleca.com\$request_uri;
}

# ── Plain HTTP — renewal first, then up to TLS ───────────────────────────
server {
    listen 80;
    listen [::]:80;
    server_name artaleca.com www.artaleca.com artaleca.ir www.artaleca.ir;

    # Renewals validate over HTTP, so this must stay reachable unredirected.
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/artaleca/public;
        allow all;
    }

    location / { return 301 https://artaleca.com\$request_uri; }
}
EOF
nginx -t && systemctl reload nginx
```

**4. Tell the application its own address.** This is the step that matters
beyond nginx: canonical tags, `hreflang`, the sitemap and every generated link
come from `APP_URL`, so redirecting without it leaves the .ir pages still
*claiming* to be canonical — which is the version Google keeps.

```bash
cd /var/www/artaleca \
  && sed -i "s|^APP_URL=.*|APP_URL=https://artaleca.com|; s|^APP_ENV=.*|APP_ENV=production|; s|^APP_DEBUG=.*|APP_DEBUG=false|" .env \
  && php artisan optimize \
  && systemctl reload $(systemctl list-units --type=service --plain --no-legend "php*-fpm.service" | cut -d" " -f1)
```

`APP_ENV=production` belongs *here* and not earlier: it forces every generated
URL to `https`, which is right behind a certificate and breaks the site
outright before there is one.

**5. Check it.** The three aliases should answer `301` and name the canonical
address; the canonical one should answer `200` and point its canonical tag at
itself:

```bash
for u in https://artaleca.ir https://www.artaleca.ir https://www.artaleca.com; do echo -n "$u -> "; curl -sk -o /dev/null -w "%{http_code} %{redirect_url}\n" "$u"; done
curl -s -o /dev/null -w "canonical host: %{http_code}\n" https://artaleca.com/fa
curl -s https://artaleca.com/fa | grep -o 'rel="canonical" href="[^"]*"'
```

Renewal is a systemd timer certbot installs for itself, and the port-80 block
above keeps `/.well-known/` unredirected so it keeps working. Confirm with
`certbot renew --dry-run`.

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

## 4b. The queue worker

Enquiry alerts are queued, so a messenger that is slow or unreachable never
makes a visitor wait behind the contact form. Queued work needs something to
run it — without a worker the alerts sit in the `jobs` table and no phone ever
buzzes.

```bash
cat > /etc/systemd/system/artaleca-worker.service <<'EOF'
[Unit]
Description=Arta Leca queue worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
WorkingDirectory=/var/www/artaleca
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload && systemctl enable --now artaleca-worker && systemctl status artaleca-worker --no-pager
```

`--max-time=3600` retires the worker every hour and lets systemd start a fresh
one. A long-lived PHP process holds the code it booted with, so without this a
deploy would leave the old job classes running until somebody restarted it by
hand.

Which is also why a deploy has to tell it to stop:

```bash
php artisan queue:restart
```

That is already in the deploy line in section 5. It asks the worker to finish
the job in hand and exit; systemd starts a replacement on the new code.

---

## 5. Deploying an update

One line, safe to re-run, and it stops at the first failure rather than
half-deploying:

```bash
cd /var/www/artaleca && git config --global --add safe.directory /var/www/artaleca; git fetch origin claude/industrial-company-website-6vty0h && git reset --hard FETCH_HEAD && composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan migrate --force && php artisan storage:link && php artisan optimize && php artisan queue:restart && sudo chown -R www-data:www-data storage bootstrap/cache public/build database && sudo systemctl reload "$(systemctl list-units --type=service --plain --no-legend 'php*-fpm.service' | awk '{print $1}')" nginx
```

### As the `ubuntu` user

The line above assumes a shell that can already write the tree. On a stock
Ubuntu box you log in as `ubuntu` and the files belong to `www-data`, so the
whole chain runs through `sudo` and hands ownership back at the end:

```bash
sudo -H bash -c 'cd /var/www/artaleca && git config --global --add safe.directory /var/www/artaleca; git fetch origin claude/industrial-company-website-6vty0h && git reset --hard FETCH_HEAD && composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan migrate --force && php artisan storage:link && php artisan optimize && php artisan queue:restart && chown -R www-data:www-data storage bootstrap/cache public/build database && systemctl reload $(systemctl list-units --type=service --plain --no-legend "php*-fpm.service" | cut -d" " -f1) nginx'
```

`sudo -H` matters: without it `sudo` keeps `HOME=/home/ubuntu`, and the
`safe.directory` exemption is then written to a file the root-owned `git`
that follows will not read.

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

## 6. Moving to another server

The repository is the whole application and none of the content. Three things
live outside it, and a server move loses all three unless they are carried
across deliberately:

| What | Where | Why it is not in git |
|:-----|:------|:---------------------|
| Uploaded images | `storage/app/public/media/` | Ignored on purpose — binaries do not belong in a repository, and they change without a deploy |
| Catalogues and datasheets | `storage/app/private/documents/` | Same, and these are deliberately outside the document root so an unpublished one stays unreachable |
| The database | `database/database.sqlite`, or MySQL | Products, offices, projects, settings, edited copy, and every enquiry received |
| `.env` | project root | Holds `APP_KEY` and the credentials, and must never be committed |

Nothing in the database records a hostname: an uploaded image is stored as
`/storage/media/…`, a path rather than a URL. So the content is portable as it
stands, and the move is a file copy rather than a search-and-replace.

On the **old** server:

```bash
cd /var/www/artaleca && php artisan down \
  && tar czf /root/artaleca-content.tar.gz storage/app database/database.sqlite* .env \
  && php artisan up && ls -lh /root/artaleca-content.tar.gz
```

`storage/app`, not `storage/app/public`: the private documents tree sits beside
it and is just as unrecoverable. `database.sqlite*` with the glob, because
SQLite in WAL mode keeps recent writes in a `-wal` file next to the database —
copy only the database and you silently lose the last few edits.

(Using MySQL instead? Swap the sqlite files for
`mysqldump -u USER -p --single-transaction artaleca > db.sql` and add `db.sql`
to the archive.)

Copy it across — from the **new** server, which avoids putting a private key on
the old one:

```bash
scp root@OLD-SERVER-IP:/root/artaleca-content.tar.gz /root/
```

Then install as in section 0 — **but stop before `migrate --force --seed`**, or
run the whole thing and accept that the next step overwrites the demonstration
data. On the **new** server:

```bash
cd /var/www/artaleca && php artisan down \
  && tar xzf /root/artaleca-content.tar.gz \
  && sed -i "s|^APP_URL=.*|APP_URL=http://NEW-IP-OR-DOMAIN|" .env \
  && php artisan migrate --force \
  && php artisan storage:link && php artisan optimize \
  && chown -R www-data:www-data storage bootstrap/cache database \
  && chmod -R 775 storage bootstrap/cache database && chmod 640 .env \
  && php artisan up
```

Three things in there are worth knowing:

- **`migrate --force`, never `--seed`.** The restored database already has the
  real content. The seeders create the demonstration catalogue and would put it
  back alongside it. `migrate` on its own is still needed: the archive may
  predate a schema change in the repository.
- **Keep the old `.env`, change only `APP_URL`.** A fresh `APP_KEY` signs out
  every admin session and invalidates the timing token the public forms carry,
  so anyone mid-form gets an error they cannot explain. `APP_URL` is the one
  line that is about the server rather than about the application.
- **`storage:link` after the restore.** The archive does not carry
  `public/storage`, which is a symlink into `storage/app/public`; without it
  every image 404s while the files sit right there on disk.

Check it landed before pointing DNS at the new box:

```bash
cd /var/www/artaleca && php artisan tinker --execute='
    printf("products %d | offices %d | images %d | enquiries %d\n",
        App\Models\Product::count(), App\Models\Office::count(),
        App\Models\GalleryImage::count(), App\Models\ContactMessage::count());'
du -sh /var/www/artaleca/storage/app/public/media
```

The counts should match the old server, and the media directory should not be
a few kilobytes.

---

## 7. Putting assets on a CDN

Set `ASSET_URL=https://cdn.example.com` and point the CDN at the origin. The
CSP picks the origin up automatically — see `SecurityHeaders::origin()` — so no
policy edit is needed. The CDN must forward `Accept-Encoding` in its cache key,
or a gzip-capable client can be served a response cached for one that was not.

---

## 8. Verifying it actually worked

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
