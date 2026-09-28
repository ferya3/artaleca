#!/usr/bin/env bash
#
# What is actually wrong with this server.
#
#     bash deploy/doctor.sh
#
# Prints one line per check and never stops on a failure — a diagnostic that
# halts on the first problem tells you about one thing when you needed to see
# all of them. Paste the whole output when asking for help; every line is
# either a fact about this machine or a number somebody can compare against.
#
# Deliberately read-only. It starts nothing, writes nothing and fixes nothing,
# so it is safe to run on a live site and safe to run when you have no idea
# what state the machine is in.

ROOT=${ROOT:-/var/www/artaleca}
APEX=${APEX:-artaleca.com}
ALIASES=${ALIASES:-www.artaleca.com artaleca.ir www.artaleca.ir}

# No `set -e`: every check has to run.
set +e

pass() { printf '  \033[32m✓\033[0m %s\n' "$1"; }
fail() { printf '  \033[31m✗\033[0m %s\n' "$1"; FAILED=$((FAILED + 1)); }
warn() { printf '  \033[33m!\033[0m %s\n' "$1"; }
note() { printf '    %s\n' "$1"; }
head2() { printf '\n\033[1m%s\033[0m\n' "$1"; }

FAILED=0

echo
echo "════ artaleca — server check ════"
echo "  $(date -u +'%Y-%m-%dT%H:%M:%SZ')   $(hostname)"

# ── Is the application even here ───────────────────────────────────────────
head2 "Application"

if [ -f "$ROOT/artisan" ]; then
    pass "found at $ROOT"
    note "commit $(git -C "$ROOT" rev-parse --short HEAD 2>/dev/null || echo unknown) on $(git -C "$ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo unknown)"
else
    fail "no application at $ROOT — nothing else below will make sense"
    exit 1
fi

for f in vendor/autoload.php public/build/manifest.json .env database/database.sqlite; do
    if [ -e "$ROOT/$f" ]; then
        pass "$f"
    else
        fail "$f is missing"
        case "$f" in
            vendor/*) note "fix: composer install --no-dev --optimize-autoloader" ;;
            public/build/*) note "fix: npm ci && npm run build" ;;
            .env) note "fix: cp .env.example .env && php artisan key:generate" ;;
            database/*) note "fix: touch database/database.sqlite && php artisan migrate --force" ;;
        esac
    fi
done

if [ -L "$ROOT/public/storage" ]; then
    pass "public/storage symlink"
else
    fail "public/storage symlink missing — every uploaded image will 404"
    note "fix: php artisan storage:link"
fi

# ── What the application thinks it is ──────────────────────────────────────
head2 "Configuration"

env_of() { grep -m1 "^$1=" "$ROOT/.env" 2>/dev/null | cut -d= -f2- | tr -d '"'; }

APP_ENV=$(env_of APP_ENV)
APP_URL=$(env_of APP_URL)
APP_DEBUG=$(env_of APP_DEBUG)

note "APP_ENV   ${APP_ENV:-(unset)}"
note "APP_URL   ${APP_URL:-(unset)}"
note "APP_DEBUG ${APP_DEBUG:-(unset)}"
note "MAILER    $(env_of MAIL_MAILER)"

[ -n "$(env_of APP_KEY)" ] && pass "APP_KEY is set" || fail "APP_KEY is empty — nothing encrypted can be read"
[ "$APP_DEBUG" = "false" ] && pass "APP_DEBUG is false" || warn "APP_DEBUG is not false — stack traces are public"

# `production` forces every generated URL to https. Right behind a working
# certificate, and the reason a site 302s to an address it cannot serve when
# there is not one yet.
if [ "$APP_ENV" = "production" ] && [ ! -d "/etc/letsencrypt/live/$APEX" ]; then
    warn "APP_ENV=production but there is no certificate yet"
    note "every request will 302 to https and the browser will fail to connect"
    note "this is expected mid-rebuild; it resolves when deploy/https.sh succeeds"
fi

# ── The data ───────────────────────────────────────────────────────────────
# The one question a rebuild has to answer: is this the real site or the
# demonstration catalogue the seeder ships?
head2 "Content"

COUNTS=$(cd "$ROOT" && php artisan tinker --execute='
    printf("%d %d %d %d %d",
        App\Models\Product::count(),
        App\Models\Post::count(),
        App\Models\ContactMessage::count(),
        App\Models\Setting::count(),
        App\Models\User::count());
' 2>/dev/null | tail -1)

read -r PRODUCTS POSTS ENQUIRIES SETTINGS USERS <<< "$COUNTS"

if [ -n "$PRODUCTS" ]; then
    note "products $PRODUCTS | articles $POSTS | enquiries $ENQUIRIES | settings $SETTINGS | users $USERS"

    if [ "${ENQUIRIES:-0}" -gt 0 ] || [ "${SETTINGS:-0}" -gt 40 ]; then
        pass "this looks like the restored site"
    else
        warn "no enquiries and few settings — this may still be the seeded demo"
        note "if you meant to restore a backup: bash deploy/restore.sh"
    fi
else
    fail "could not read the database"
fi

MEDIA=$(find "$ROOT/storage/app/public/media" -type f 2>/dev/null | wc -l)
[ "$MEDIA" -gt 0 ] && pass "$MEDIA uploaded image files" || warn "no uploaded images on disk"

# ── Services ───────────────────────────────────────────────────────────────
head2 "Services"

for unit in nginx artaleca-worker; do
    if systemctl is-active --quiet "$unit" 2>/dev/null; then
        pass "$unit running"
    else
        fail "$unit is NOT running"
        note "fix: systemctl enable --now $unit"
        note "why: $(systemctl is-failed "$unit" 2>/dev/null || echo 'never started')"
    fi
done

FPM=$(systemctl list-units --type=service --plain --no-legend 'php*-fpm.service' 2>/dev/null | cut -d' ' -f1)

if [ -n "$FPM" ] && systemctl is-active --quiet "$FPM"; then
    pass "$FPM running"
else
    fail "php-fpm is NOT running — every page will be 502"
    note "fix: systemctl enable --now php$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')-fpm"
fi

# ── Listening sockets ──────────────────────────────────────────────────────
head2 "Ports"

for port in 80 443; do
    LISTEN=$(ss -lnt "sport = :$port" 2>/dev/null | awk 'NR>1 {print $4}' | tr '\n' ' ')

    if [ -n "$LISTEN" ]; then
        pass "listening on $port — $LISTEN"
    else
        fail "nothing is listening on $port"
        [ "$port" = 443 ] && note "expected until the certificate is issued"
    fi
done

# ── Does it serve, from the machine itself ─────────────────────────────────
head2 "Local HTTP"

for path in / /fa /.well-known/acme-challenge/probe; do
    CODE=$(curl -s -o /dev/null -m 10 -w '%{http_code}' "http://127.0.0.1$path" 2>/dev/null)
    LOC=$(curl -s -o /dev/null -m 10 -w '%{redirect_url}' "http://127.0.0.1$path" 2>/dev/null)
    printf '    %-38s %s %s\n' "$path" "${CODE:-no answer}" "$LOC"
done

# The ACME path must be served by nginx as a plain file, never handed to the
# application — a 301/302 here is why certificate issuance fails even when
# port 80 is open to the world.
PROBE=$(curl -s -o /dev/null -m 10 -w '%{http_code}' "http://127.0.0.1/.well-known/acme-challenge/probe" 2>/dev/null)

case "$PROBE" in
    404) pass "the ACME path is served by nginx (404 for a file that does not exist is correct)" ;;
    30*) fail "the ACME path redirects — certbot cannot validate through this"
         note "the port-80 server block is missing its acme-challenge location" ;;
    *)   warn "the ACME path answered $PROBE, which is neither 404 nor a redirect" ;;
esac

# ── Names ──────────────────────────────────────────────────────────────────
head2 "DNS"

HERE=$(curl -s -m 10 https://api.ipify.org 2>/dev/null)

if [ -n "$HERE" ]; then
    note "this server's outbound address: $HERE"
else
    warn "could not determine this server's own address"
    note "so the names below are reported, not compared — check them by eye"
fi

for d in $APEX $ALIASES; do
    GOT=$(getent hosts "$d" 2>/dev/null | awk '{print $1; exit}')

    if [ -z "$GOT" ]; then
        fail "$d does not resolve"
        continue
    fi

    # Only a claim worth making when there is something to compare against.
    if [ -z "$HERE" ]; then
        note "$d → $GOT"
    elif [ "$GOT" = "$HERE" ]; then
        pass "$d → $GOT"
    else
        warn "$d → $GOT (not this server)"

        # Cloudflare's published ranges begin 104.16-31, 172.64-71, 162.158-159,
        # 188.114, 190.93, 197.234, 198.41. Naming it only when it looks like
        # one keeps the advice from being wrong about a plain misconfigured
        # record, which is the other reason these disagree.
        case "$GOT" in
            104.1[6-9].*|104.2[0-9].*|104.3[0-1].*|172.6[4-9].*|172.7[0-1].*|162.15[89].*|188.114.*|190.93.*|197.234.*|198.41.*)
                note "that is a Cloudflare address — the orange cloud is on"
                note "turn it off (DNS only) until the certificate is issued, or"
                note "certbot cannot reach this machine to validate"
                ;;
            *)
                note "the record points at a different machine — either the A record"
                note "is stale, or you are working on the wrong server"
                ;;
        esac
    fi
done

# ── Certificate ────────────────────────────────────────────────────────────
head2 "Certificate"

if [ -d "/etc/letsencrypt/live/$APEX" ]; then
    CERT="/etc/letsencrypt/live/$APEX/fullchain.pem"
    pass "present"
    note "expires  $(openssl x509 -enddate -noout -in "$CERT" 2>/dev/null | cut -d= -f2)"
    note "names    $(openssl x509 -noout -text -in "$CERT" 2>/dev/null | grep -A1 'Subject Alternative Name' | tail -1 | tr -d ' ' | sed 's/DNS://g')"
else
    fail "no certificate for $APEX"
    note "fix: bash deploy/https.sh"
    note "if that times out, port 80 is unreachable from outside — test at https://letsdebug.net"
fi

# ── Recent errors ──────────────────────────────────────────────────────────
head2 "Last errors"

LOG="$ROOT/storage/logs/laravel.log"

if [ -s "$LOG" ]; then
    note "$(grep -c 'ERROR\|CRITICAL' "$LOG" 2>/dev/null) error lines in storage/logs/laravel.log"
    grep 'ERROR\|CRITICAL' "$LOG" 2>/dev/null | tail -3 | cut -c1-160 | sed 's/^/    /'
else
    pass "the application log is empty"
fi

NGERR=$(tail -200 /var/log/nginx/error.log 2>/dev/null | grep -c 'error\|crit')
[ "${NGERR:-0}" -gt 0 ] && warn "$NGERR recent nginx error lines" && tail -3 /var/log/nginx/error.log 2>/dev/null | cut -c1-160 | sed 's/^/    /'

# ── Permissions and space ──────────────────────────────────────────────────
head2 "Permissions and space"

for d in storage bootstrap/cache database; do
    OWNER=$(stat -c '%U' "$ROOT/$d" 2>/dev/null)
    [ "$OWNER" = "www-data" ] && pass "$d owned by www-data" || fail "$d owned by ${OWNER:-?}, not www-data"
done

note "disk  $(df -h "$ROOT" 2>/dev/null | awk 'NR==2 {print $4" free of "$2" ("$5" used)"}')"
note "ram   $(free -h 2>/dev/null | awk 'NR==2 {print $7" available of "$2}')"

# ── Verdict ────────────────────────────────────────────────────────────────
echo
if [ "$FAILED" -eq 0 ]; then
    printf '\033[32m  Nothing failed.\033[0m If the site is still unreachable from a browser,\n'
    printf '  the problem is between the internet and this machine rather than on it:\n'
    printf '  the hosting firewall, or the datacentre filtering inbound traffic.\n'
    printf '  Test what the outside world sees: https://check-host.net/check-http?host=http://%s\n' "$APEX"
else
    printf '\033[31m  %s check(s) failed.\033[0m Work down them in the order printed — the\n' "$FAILED"
    printf '  early ones cause the later ones.\n'
fi
echo
