#!/usr/bin/env bash
#
# Everything the server holds that GitHub does not.
#
#     sudo bash deploy/backup.sh
#
# Writes one timestamped archive to /var/backups/artaleca and prints its path.
# `deploy/restore.sh` puts it back.
#
# The code is in git, so this is deliberately not an image of the machine. What
# it carries is the four things a `git clone` cannot give you back:
#
#   database/database.sqlite   every product, page, article, setting, enquiry
#                              and user account — the work, in other words
#   storage/app                the uploaded photographs and the private
#                              documents, which are served from disk and exist
#                              in no other copy
#   .env                       APP_KEY above all. The SMS panel's password and
#                              API key are encrypted with it, so a database
#                              restored beside a *new* APP_KEY comes back with
#                              credentials nothing can decrypt. It also holds
#                              the SMTP password and the admin bootstrap
#                              password. This is the file that makes the
#                              archive a secret — see the note at the bottom.
#   nginx + systemd            the site config and the queue worker unit. Both
#                              are reproducible from deploy/https.sh and the
#                              deployment notes, so they are here for speed
#                              rather than out of necessity.
#
# Not here, on purpose: the TLS certificate (certbot re-issues it in seconds
# from a name that resolves, and a copied certificate that later diverges from
# what Let's Encrypt has on file is worse than none), vendor/ and node_modules/
# (composer and npm rebuild them), public/build (npm run build), and the Laravel
# caches, which are regenerated and would otherwise restore a stale config.
#
# Safe to run on a live site: the database is snapshotted with `VACUUM INTO`
# rather than copied. A plain `cp` of a SQLite file that is being written to can
# capture a torn page, and the failure shows up months later as a corrupt
# restore — which is the worst possible moment to find out.

set -euo pipefail

ROOT=${ROOT:-/var/www/artaleca}
DEST=${DEST:-/var/backups/artaleca}
KEEP=${KEEP:-14}

[ "$(id -u)" -eq 0 ] || { echo "run this as root"; exit 1; }
[ -f "$ROOT/artisan" ] || { echo "no application at $ROOT"; exit 1; }

STAMP=$(date -u +%Y%m%d-%H%M%S)
ARCHIVE="$DEST/artaleca-$STAMP.tar.gz"
WORK=$(mktemp -d)
trap 'rm -rf "$WORK"' EXIT

mkdir -p "$DEST" "$WORK/payload"
chmod 700 "$DEST"

cd "$ROOT"

# ── The database ───────────────────────────────────────────────────────────
# `VACUUM INTO` is a consistent snapshot taken through SQLite itself, so it is
# safe while the site is serving. Done through PHP's PDO rather than the sqlite3
# binary, which the server install does not include.
if [ -f database/database.sqlite ]; then
    SRC=database/database.sqlite DST="$WORK/payload/database.sqlite" php -r '
        $db = new PDO("sqlite:".getenv("SRC"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec("VACUUM INTO ".$db->quote(getenv("DST")));
    '

    # A snapshot that cannot be opened is not a backup. Check it here, while
    # there is still a working server to tell somebody about it.
    DST="$WORK/payload/database.sqlite" php -r '
        $db = new PDO("sqlite:".getenv("DST"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($db->query("PRAGMA integrity_check")->fetchColumn() !== "ok") {
            fwrite(STDERR, "the database snapshot failed its integrity check\n");
            exit(1);
        }

        printf("  database   %d settings, %d products, %d articles, %d enquiries\n",
            $db->query("select count(*) from settings")->fetchColumn(),
            $db->query("select count(*) from products")->fetchColumn(),
            $db->query("select count(*) from posts")->fetchColumn(),
            $db->query("select count(*) from contact_messages")->fetchColumn(),
        );
    '
else
    echo "  database   none found — a MySQL/Postgres install needs its own dump here"
fi

# ── Uploads and secrets ────────────────────────────────────────────────────
[ -d storage/app ] && cp -a storage/app "$WORK/payload/storage-app"
[ -f .env ] && cp -a .env "$WORK/payload/env"

# ── Server configuration ───────────────────────────────────────────────────
mkdir -p "$WORK/payload/system"
[ -f /etc/nginx/sites-available/artaleca ] && cp -a /etc/nginx/sites-available/artaleca "$WORK/payload/system/nginx-site"
[ -f /etc/systemd/system/artaleca-worker.service ] && cp -a /etc/systemd/system/artaleca-worker.service "$WORK/payload/system/"
for ini in /etc/php/*/fpm/conf.d/99-artaleca.ini; do
    [ -f "$ini" ] && cp -a "$ini" "$WORK/payload/system/php-artaleca.ini"
done

# ── What this is a backup of ───────────────────────────────────────────────
# The commit matters most: restoring this database onto a much newer or older
# checkout is how a migration ends up half-applied, so the restore script
# reports the commit and lets whoever is running it decide.
{
    echo "taken_at   $(date -u +'%Y-%m-%dT%H:%M:%SZ')"
    echo "host       $(hostname)"
    echo "root       $ROOT"
    echo "commit     $(git -C "$ROOT" rev-parse HEAD 2>/dev/null || echo unknown)"
    echo "branch     $(git -C "$ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo unknown)"
    echo "php        $(php -r 'echo PHP_VERSION;')"
    echo "app_env    $(grep -m1 '^APP_ENV=' "$ROOT/.env" 2>/dev/null | cut -d= -f2- || echo unknown)"
    echo "app_url    $(grep -m1 '^APP_URL=' "$ROOT/.env" 2>/dev/null | cut -d= -f2- || echo unknown)"
} > "$WORK/payload/MANIFEST"

# ── One file ───────────────────────────────────────────────────────────────
tar -czf "$ARCHIVE" -C "$WORK/payload" .
chmod 600 "$ARCHIVE"

# ── Rotation ───────────────────────────────────────────────────────────────
# Oldest first, keeping $KEEP. Only files this script's own naming produces,
# so nothing else in the directory is ever deleted.
mapfile -t OLD < <(ls -1t "$DEST"/artaleca-*.tar.gz 2>/dev/null | tail -n +$((KEEP + 1)))
for stale in "${OLD[@]:-}"; do
    [ -n "$stale" ] && rm -f "$stale"
done

echo
echo "  archive    $ARCHIVE ($(du -h "$ARCHIVE" | cut -f1))"
echo "  keeping    $(ls -1 "$DEST"/artaleca-*.tar.gz 2>/dev/null | wc -l) of the last $KEEP"
echo
echo "  This archive contains .env, so it contains APP_KEY, the SMTP password"
echo "  and the SMS panel credentials. Treat it as a secret: it is mode 600 in"
echo "  a mode 700 directory here, and it must not go anywhere public."
echo
echo "  A copy on the same disk is not a backup. Pull it somewhere else:"
echo "      scp root@$(hostname -I 2>/dev/null | awk '{print $1}'):$ARCHIVE ."
echo
