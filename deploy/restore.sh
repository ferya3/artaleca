#!/usr/bin/env bash
#
# Put the server back, from nothing.
#
#     sudo bash deploy/restore.sh /path/to/artaleca-20260917-030000.tar.gz
#
# Or, with no argument, the newest archive in /var/backups/artaleca:
#
#     sudo bash deploy/restore.sh
#
# Two situations this covers, and the difference matters:
#
#   The site is broken but the machine is fine. Run this and it puts the
#   database, the uploads and .env back, reruns the migrations, rebuilds the
#   caches and restarts the services. Nothing else needed.
#
#   The machine is gone. Rebuild it first — sections 1 to 3 of the deployment
#   notes, which install the packages, clone the repository and set up the
#   queue worker — and then run this. It overwrites the fresh .env and the
#   seeded database with the real ones, which is the whole point: the clone
#   gives you the code, this gives you back the work.
#
# What it does *not* do is guess. If the archive was taken from a different
# commit than the checkout it is being restored onto, it says so and asks,
# because restoring a database from either side of a migration is how a schema
# ends up half-applied.

set -euo pipefail

ROOT=${ROOT:-/var/www/artaleca}
DEST=${DEST:-/var/backups/artaleca}

[ "$(id -u)" -eq 0 ] || { echo "run this as root"; exit 1; }
[ -f "$ROOT/artisan" ] || { echo "no application at $ROOT — rebuild it first (deployment notes, sections 1-3)"; exit 1; }

ARCHIVE=${1:-}

if [ -z "$ARCHIVE" ]; then
    ARCHIVE=$(ls -1t "$DEST"/artaleca-*.tar.gz 2>/dev/null | head -1 || true)
    [ -n "$ARCHIVE" ] || { echo "no archive given and none in $DEST"; exit 1; }
fi

[ -f "$ARCHIVE" ] || { echo "no such archive: $ARCHIVE"; exit 1; }

WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT
chmod 700 "$WORK"

tar -xzf "$ARCHIVE" -C "$WORK"

echo
echo "── Restoring from $(basename "$ARCHIVE")"
echo
sed 's/^/  /' "$WORK/MANIFEST" 2>/dev/null || echo "  (no manifest — an archive from before these scripts?)"
echo

# ── The one question worth asking ──────────────────────────────────────────
WAS=$(awk '/^commit/ {print $2}' "$WORK/MANIFEST" 2>/dev/null || echo unknown)
NOW=$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || echo unknown)

if [ "$WAS" != "unknown" ] && [ "$WAS" != "$NOW" ]; then
    echo "  The archive was taken at $WAS"
    echo "  and this checkout is at    $NOW"
    echo
    echo "  Migrations run after the restore, so moving *forward* is fine — that is"
    echo "  an ordinary deploy. Restoring onto an OLDER checkout is not: the database"
    echo "  would carry tables the code does not know about. If that is the case,"
    echo "  update the checkout first and run this again."
    echo

    # Asking needs somewhere to ask. With no terminal — run from cron, or
    # through a pipe — this refuses rather than assuming yes: the one thing a
    # restore script must never do is overwrite a database because nobody was
    # there to say no. `FORCE=1` is how you say yes in advance.
    #
    # `[ -r /dev/tty ]` is not the test. The node exists and stats as readable
    # in a container with no controlling terminal, and the open then fails with
    # ENXIO — which under `set -e` killed the redirect and left the script
    # having asked a question nobody could answer. The only reliable check is
    # to try opening it.
    if [ "${FORCE:-}" = "1" ]; then
        echo "  FORCE=1 — continuing without asking."
        echo
    elif { : < /dev/tty; } 2>/dev/null; then
        printf '  Continue? [y/N] '
        read -r answer < /dev/tty

        case "$answer" in
            y | Y) ;;
            *) echo "  stopped, nothing changed"; exit 1 ;;
        esac

        echo
    elif [ -t 0 ]; then
        printf '  Continue? [y/N] '
        read -r answer

        case "$answer" in
            y | Y) ;;
            *) echo "  stopped, nothing changed"; exit 1 ;;
        esac

        echo
    else
        echo "  No terminal to ask on, so nothing has been changed."
        echo "  Re-run with FORCE=1 if the commit difference is expected."
        exit 1
    fi
fi

cd "$ROOT"

# Stop the worker before touching the database. A queue worker holding a write
# transaction while the file is replaced underneath it is a corrupt file.
systemctl stop artaleca-worker 2>/dev/null || true

# ── .env first, because APP_KEY has to be in place before anything reads it ──
# The SMS panel's password and API key are encrypted with APP_KEY. A restored
# database beside a newly generated key comes back with credentials nothing can
# decrypt — and it fails quietly, as an SMS that never arrives.
if [ -f "$WORK/env" ]; then
    [ -f .env ] && cp -a .env ".env.before-restore-$(date -u +%Y%m%d-%H%M%S)"
    cp -a "$WORK/env" .env
    chmod 640 .env
    echo "  ✓ .env (the previous one kept as .env.before-restore-…)"
fi

# ── The database ───────────────────────────────────────────────────────────
if [ -f "$WORK/database.sqlite" ]; then
    [ -f database/database.sqlite ] && mv database/database.sqlite "database/database.sqlite.before-restore-$(date -u +%Y%m%d-%H%M%S)"
    cp -a "$WORK/database.sqlite" database/database.sqlite
    echo "  ✓ database"
fi

# ── Uploads ────────────────────────────────────────────────────────────────
# `cp -a` over the top rather than a delete-and-replace: a photograph uploaded
# since the backup is not in the archive, and losing it to a restore that was
# meant to be a rescue is a bad trade.
if [ -d "$WORK/storage-app" ]; then
    mkdir -p storage/app
    cp -a "$WORK/storage-app/." storage/app/
    echo "  ✓ uploads and documents"
fi

# ── Server configuration ───────────────────────────────────────────────────
if [ -f "$WORK/system/nginx-site" ] && [ ! -f /etc/nginx/sites-available/artaleca ]; then
    cp -a "$WORK/system/nginx-site" /etc/nginx/sites-available/artaleca
    ln -sf /etc/nginx/sites-available/artaleca /etc/nginx/sites-enabled/artaleca
    rm -f /etc/nginx/sites-enabled/default
    echo "  ✓ nginx site (run deploy/https.sh afterwards for the certificate)"
fi

if [ -f "$WORK/system/artaleca-worker.service" ] && [ ! -f /etc/systemd/system/artaleca-worker.service ]; then
    cp -a "$WORK/system/artaleca-worker.service" /etc/systemd/system/
    systemctl daemon-reload
    systemctl enable artaleca-worker >/dev/null 2>&1 || true
    echo "  ✓ queue worker unit"
fi

for ini in /etc/php/*/fpm/conf.d/; do
    if [ -f "$WORK/system/php-artaleca.ini" ] && [ -d "$ini" ] && [ ! -f "$ini/99-artaleca.ini" ]; then
        cp -a "$WORK/system/php-artaleca.ini" "$ini/99-artaleca.ini"
        echo "  ✓ php limits"
    fi
done

# ── Make it run ────────────────────────────────────────────────────────────
php artisan migrate --force
php artisan storage:link >/dev/null 2>&1 || true

# `optimize` last, and it matters: a cached config from before the restore
# would still name the old APP_KEY and the old mail settings.
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache database public/build 2>/dev/null || true
chmod -R 775 storage bootstrap/cache
chmod 775 database
[ -f database/database.sqlite ] && chmod 664 database/database.sqlite
[ -f .env ] && chown www-data:www-data .env && chmod 640 .env

# Every service call is allowed to fail without failing the restore. The work
# is already back on disk at this point, and a script that reports failure
# because php-fpm happened not to be listed is a script that sends somebody
# looking for a problem that is not there.
systemctl start artaleca-worker 2>/dev/null || true

FPM=$(systemctl list-units --type=service --plain --no-legend 'php*-fpm.service' 2>/dev/null | cut -d' ' -f1 || true)

if [ -n "$FPM" ]; then
    systemctl reload "$FPM" 2>/dev/null || true
fi

systemctl reload nginx 2>/dev/null || systemctl restart nginx 2>/dev/null || true

echo
echo "── Restored. Check it:"
echo
echo "     curl -sI http://127.0.0.1 | head -1"
echo "     php artisan mail:test"
echo "     bash deploy/seo-check.sh http://127.0.0.1"
echo
echo "   The pre-restore database and .env are kept next to the live ones as"
echo "   *.before-restore-… — delete them once the site is confirmed good."
echo
