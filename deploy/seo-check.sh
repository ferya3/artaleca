#!/usr/bin/env bash
#
# Reads the live site and reports what a search engine will find.
#
#     bash deploy/seo-check.sh                     # https://artaleca.com
#     bash deploy/seo-check.sh http://127.0.0.1    # before DNS, on the server
#
# Everything here is checked against the served HTML rather than the source,
# because the two come apart in ways that are invisible locally: a stale view
# cache, a description truncated at 158 characters, an admin override that
# replaced the sentence carrying half the search terms.
#
# Exits non-zero if anything fails, so it can gate a deploy.

export LC_ALL=${LC_ALL:-C.UTF-8}

BASE=${1:-https://artaleca.com}
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT

pass=0
fail=0

ok()   { printf '  \033[32m✓\033[0m %s\n' "$1"; pass=$((pass + 1)); }
bad()  { printf '  \033[31m✗\033[0m %s\n' "$1"; fail=$((fail + 1)); }
head2() { printf '\n\033[1m%s\033[0m\n' "$1"; }

fetch() { curl -sk -m 20 -o "$TMP/$2" -w '%{http_code}' "$BASE$1"; }

# One line, tags intact — the extractors below all assume no newlines.
flat() { tr '\n' ' ' < "$TMP/$1"; }

tag_content() { flat "$1" | sed -n "s/.*<meta name=\"$2\" content=\"\([^\"]*\)\".*/\1/p"; }

# ── The indexable pages ────────────────────────────────────────────────────
declare -A PAGES=(
    [home]=/fa
    [products]=/fa/products
    [applications]=/fa/applications
)

head2 "Pages"

for name in home products applications; do
    path=${PAGES[$name]}
    code=$(fetch "$path" "$name.html")

    if [ "$code" != "200" ]; then
        bad "$path -> HTTP $code"
        continue
    fi

    title=$(flat "$name.html" | sed -n 's/.*<title>\(.*\)<\/title>.*/\1/p')
    desc=$(tag_content "$name.html" description)
    robots=$(tag_content "$name.html" robots)
    canon=$(flat "$name.html" | sed -n 's/.*rel="canonical" href="\([^"]*\)".*/\1/p')

    printf '\n  %s\n' "$path"
    printf '    title  (%s) %s\n' "$(printf '%s' "$title" | wc -m)" "$title"
    printf '    descr  (%s) %s\n' "$(printf '%s' "$desc" | wc -m)" "$desc"

    [ -n "$title" ] && ok "has a title" || bad "no <title>"
    [ -n "$desc" ] && ok "has a description" || bad "no meta description"

    # Seo::description cuts at 158 and appends an ellipsis; if one arrives it
    # means the sentence was written past the cut and its ending is gone.
    case "$desc" in
        *...) bad "description was truncated — its last phrase never reaches Google" ;;
        *) ok "description is not truncated" ;;
    esac

    case "$robots" in
        *noindex*) bad "noindex — this page cannot rank at all" ;;
        *) ok "indexable" ;;
    esac

    [ -n "$canon" ] && ok "canonical: $canon" || bad "no canonical link"

    # Three languages plus x-default is the set Google needs to treat the
    # translations as one page rather than as duplicates of each other.
    n=$(flat "$name.html" | grep -o 'rel="alternate" hreflang=' | wc -l)
    [ "$n" -ge 4 ] && ok "hreflang: $n links" || bad "hreflang: $n links, expected at least 4"
done

# ── The search terms ───────────────────────────────────────────────────────
# Each has to survive as an unbroken run of characters: "قیمت و شرایط سبکدانه
# لیکا" reads the same to a person as "قیمت سبکدانه لیکا" and matches nothing.
head2 "Search terms"

TERMS=(
    'سبکدانه لیکا'
    'لیکا'
    'پوکه صنعتی لیکا'
    'دانه لیکا'
    'سبکدانه صنعتی'
    'پوکه لیکا'
    'قیمت سبکدانه لیکا'
    'خرید سبکدانه لیکا'
    'فروش سبکدانه لیکا'
    'قیمت لیکا'
    'خرید لیکا'
    'فروش لیکا'
    'سبکدانه بتن'
    'بتن سبک لیکا'
    'مصالح سبک ساختمانی'
)

for term in "${TERMS[@]}"; do
    where=""
    for name in home products applications; do
        [ -f "$TMP/$name.html" ] && grep -qF "$term" "$TMP/$name.html" && where="$where $name"
    done

    if [ -n "$where" ]; then
        ok "$term →$where"
    else
        bad "$term — on none of the three pages"
    fi
done

# ── robots.txt and the sitemap ─────────────────────────────────────────────
head2 "robots.txt and sitemap"

code=$(fetch /robots.txt robots.txt)
if [ "$code" = "200" ]; then
    ok "robots.txt -> 200"
    if grep -qE '^Disallow: /$' "$TMP/robots.txt" && ! grep -q '^Sitemap:' "$TMP/robots.txt"; then
        bad "robots.txt blocks the whole site — APP_ENV is not 'production' on this server"
    else
        grep -q '^Sitemap:' "$TMP/robots.txt" && ok "names the sitemap" || bad "no Sitemap: line — Google is not told where it is"
    fi
    grep -qE '^Disallow: /$' "$TMP/robots.txt" \
        || { grep -q 'Disallow: /admin' "$TMP/robots.txt" && ok "keeps crawlers out of /admin" || bad "/admin is not disallowed"; }
    # The Laravel skeleton ships a static public/robots.txt that nginx serves
    # before the route runs, and it allows everything with no sitemap line.
    grep -q '^Disallow:[[:space:]]*$' "$TMP/robots.txt" && bad "this is the skeleton file — delete public/robots.txt"
else
    bad "robots.txt -> HTTP $code"
fi

code=$(fetch /sitemap.xml sitemap.xml)
if [ "$code" = "200" ]; then
    urls=$(grep -o '<loc>' "$TMP/sitemap.xml" | wc -l)
    ok "sitemap.xml -> 200, $urls URLs"
    [ "$urls" -gt 20 ] && ok "enough URLs to be the real thing" || bad "only $urls URLs — something is filtering them out"
    grep -q 'hreflang="x-default"' "$TMP/sitemap.xml" && ok "carries hreflang alternates" || bad "no alternates in the sitemap"
else
    bad "sitemap.xml -> HTTP $code"
fi

# ── One address per page ───────────────────────────────────────────────────
# Four names serving the same page splits the ranking four ways.
head2 "Canonical host"

W=www
case "$BASE" in
    *artaleca.com*) ;;
    *) printf '  skipped — only meaningful against the live domain\n'
       printf '\n\033[1m%s passed, %s failed\033[0m\n' "$pass" "$fail"
       [ "$fail" -eq 0 ] || exit 1
       exit 0 ;;
esac
for host in "artaleca.ir" "$W.artaleca.ir" "$W.artaleca.com"; do
    line=$(curl -sk -m 15 -o /dev/null -w '%{http_code} %{redirect_url}' "https://$host" 2>/dev/null)
    case "$line" in
        301*artaleca.com*) ok "$host -> $line" ;;
        "") bad "$host — no answer" ;;
        *) bad "$host -> $line (expected a 301 to artaleca.com)" ;;
    esac
done

# ── Verdict ────────────────────────────────────────────────────────────────
printf '\n\033[1m%s passed, %s failed\033[0m\n' "$pass" "$fail"
[ "$fail" -eq 0 ] || exit 1
