<?php

declare(strict_types=1);

if (! function_exists('php_upload_limit_kb')) {
    /**
     * The largest upload PHP will actually accept, in kilobytes.
     *
     * The smaller of `upload_max_filesize` and `post_max_size`, since a request
     * has to clear both. Defined here rather than in a support class because a
     * config file is loaded before the autoloader has a service container to
     * ask, and the value is frozen into `config:cache` anyway — php.ini does
     * not change between requests.
     */
    function php_upload_limit_kb(): int
    {
        $toKb = static function (string $value): int {
            $value = trim($value);

            if ($value === '' || $value === '-1') {
                return PHP_INT_MAX;   // unlimited
            }

            $bytes = (int) $value;

            return match (strtolower(substr($value, -1))) {
                'g' => $bytes * 1024 * 1024,
                'm' => $bytes * 1024,
                'k' => $bytes,
                default => intdiv($bytes, 1024),
            };
        };

        return max(1, min(
            $toKb((string) ini_get('upload_max_filesize')),
            $toKb((string) ini_get('post_max_size')),
        ));
    }
}

/*
|--------------------------------------------------------------------------
| Site configuration
|--------------------------------------------------------------------------
|
| Single source of truth for company identity, locales and contact details.
| Everything the templates need that is *not* editable content lives here,
| so Blade never hard-codes a phone number or a locale list.
|
*/

return [

    'company' => [
        'legal_name' => env('SITE_LEGAL_NAME', 'Arta Leca Industrial Co.'),
        'brand' => env('SITE_BRAND', 'ARTA LECA'),

        /*
         * Every name this company is actually searched under.
         *
         * "لیکا" transliterates the Italian *Leca* and Persian has no settled
         * spelling for it, so the company's own name is written both with the
         * ی and without — آرتا لیکا and آرتا لکا — by customers, by suppliers
         * and on delivery notes. Both are the same firm, and somebody who
         * types the second one is looking for this site.
         *
         * These become `alternateName` on the Organization and LocalBusiness
         * records, which is the declared way to tell a search engine that one
         * entity has several names. It is not keyword stuffing: a genuine
         * spelling variant of a proper noun is exactly what the field is for,
         * and the list stays at the handful of forms people really type.
         *
         * Structured data alone will not rank a spelling that appears nowhere
         * in the text, so the genuine variants are also written once into the
         * home page copy and once into the company's own account of itself.
         *
         * One casing per form, and that is not an oversight. Name matching in
         * every search engine is case-insensitive, so `ARTALCA`, `Artalca` and
         * `artalca` are one token rather than three; listing all three would
         * add nothing and would make the list read as padding, which is the
         * one thing this field must not look like. A test fails on a
         * case-duplicate. The space is different — `Arta Lca` and `Artalca`
         * really are two tokens, so both are here.
         */
        'aliases' => [
            // Persian and Arabic, the two scripts the company trades in.
            'آرتا لیکا',
            'آرتا لکا',
            'آرتا ليكا',

            // Latin, as written. `Arta Leca` itself is absent because the
            // brand above already supplies it, and one casing per form is the
            // rule — a test enforces it.
            'Arta Leka',
            'Artaleca',
            'Artaleka',

            /*
             * Latin, as typed. The `e` is the first thing to go when somebody
             * keys the name from memory or from a delivery note, and these are
             * the forms that actually reach a search box. They are here and
             * nowhere else on purpose: a sentence on a page listing six
             * misspellings of your own name reads as spam to a person and to a
             * crawler alike, while `alternateName` is precisely the field for
             * saying "this entity is also called that".
             */
            'Arta Lca',
            'Artalca',
            'Arta Lka',
            'Artalka',
        ],
        'founded' => 1996,
        'registration_no' => env('SITE_REGISTRATION_NO', '۱۲۴۵۸'),
        'national_id' => env('SITE_NATIONAL_ID', '۱۰۸۶۱۲۳۴۵۶۷'),
    ],

    /*
    | Locales. `dir` drives the <html dir> attribute and the RTL/LTR logical
    | properties in the stylesheet. `hreflang` is what search engines see.
    */
    'locales' => [
        'fa' => [
            'name' => 'فارسی',
            'native' => 'فارسی',
            'dir' => 'rtl',
            'hreflang' => 'fa-IR',
            'flag' => 'IR',
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'dir' => 'ltr',
            'hreflang' => 'en',
            'flag' => 'GB',
        ],
        'ar' => [
            'name' => 'العربية',
            'native' => 'العربية',
            'dir' => 'rtl',
            'hreflang' => 'ar',
            'flag' => 'SA',
        ],
    ],

    'default_locale' => 'fa',

    'contact' => [
        'phone' => env('SITE_PHONE', '+98 21 8888 0000'),
        'phone_display' => env('SITE_PHONE_DISPLAY', '۰۲۱-۸۸۸۸۰۰۰۰'),
        'sales_phone' => env('SITE_SALES_PHONE', '+98 21 8888 0011'),
        // The one number to ring from anywhere in the country, shown
        // ahead of the sales line because it is the one to try first.
        'national_phone' => env('SITE_NATIONAL_PHONE', '+98-45-3182'),
        'fax' => env('SITE_FAX', '+98 21 8888 0099'),
        'email' => env('SITE_EMAIL', 'info@artaleca.com'),
        'sales_email' => env('SITE_SALES_EMAIL', 'sales@artaleca.com'),
        'export_email' => env('SITE_EXPORT_EMAIL', 'export@artaleca.com'),
        /*
         * The messenger desks, shipped empty on purpose.
         *
         * These drive the quick-contact widget, and a link to an account
         * nobody holds is worse than no link: it costs the visitor the one
         * thing they opened it for and tells them the company does not answer.
         * Empty means the channel simply is not offered. Fill them in at
         * Panel → Settings → Support and messengers.
         */
        'whatsapp' => env('SITE_WHATSAPP', ''),
        'telegram' => env('SITE_TELEGRAM', ''),
        'rubika' => env('SITE_RUBIKA', ''),

        'hq' => [
            'lines' => [
                'fa' => 'تهران، خیابان ولیعصر، بالاتر از میدان ونک، برج آرتا، طبقه ۹',
                'en' => 'Arta Tower, Floor 9, Valiasr St., above Vanak Sq., Tehran, Iran',
                'ar' => 'برج آرتا، الطابق ٩، شارع ولي العصر، أعلى ميدان ونك، طهران، إيران',
            ],
            'postal_code' => '1969764514',
        ],

        'plant' => [
            'lines' => [
                'fa' => 'استان قم، کیلومتر ۳۵ جاده قم–کاشان، شهرک صنعتی محمودآباد، فاز ۲',
                'en' => 'Phase 2, Mahmoudabad Industrial Zone, km 35 Qom–Kashan Rd., Qom Province, Iran',
                'ar' => 'المرحلة ٢، منطقة محمودآباد الصناعية، كم ٣٥ طريق قم–كاشان، محافظة قم، إيران',
            ],
            'geo' => ['lat' => 34.4520, 'lng' => 50.9130],
        ],

        'hours' => [
            'fa' => 'شنبه تا چهارشنبه، ۸:۰۰ تا ۱۷:۰۰',
            'en' => 'Saturday–Wednesday, 08:00–17:00 (GMT+3:30)',
            'ar' => 'السبت – الأربعاء، ٨:٠٠ – ١٧:٠٠',
        ],
    ],

    'social' => [
        'linkedin' => env('SITE_LINKEDIN', 'https://www.linkedin.com/company/artaleca'),
        'instagram' => env('SITE_INSTAGRAM', 'https://instagram.com/artaleca'),
        'youtube' => env('SITE_YOUTUBE', ''),
        'aparat' => env('SITE_APARAT', ''),
    ],

    /*
    | Headline figures shown in the hero data strip and the about page.
    | Kept here (not in the DB) because they are brand facts, not content
    | an editor changes weekly.
    */
    'figures' => [
        'annual_capacity_m3' => 450000,
        'plant_area_m2' => 84000,
        'kiln_lines' => 3,
        'export_countries' => 14,
        'employees' => 210,
        'since' => 1996,
    ],

    'seo' => [
        'twitter_handle' => env('SITE_TWITTER', '@artaleca'),
        'default_og_image' => '/images/og/og-default.png',
        'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    ],

    /*
    | Uploads accepted by the admin media handler and the RFQ form.
    |
    | The limits are clamped to what PHP will actually accept. PHP rejects an
    | oversized upload at the transport layer, before any rule runs, and all
    | Laravel can then say is `uploaded` — a failure with no size in it, on a
    | form that had just advertised a larger one. Promising only what the
    | server can take means the message names the real number instead.
    |
    | Raise `upload_max_filesize` and `post_max_size` in php.ini to lift these;
    | see docs/deploy-ubuntu.md.
    */
    'uploads' => [
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],
        'document_mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'max_image_kb' => min(4096, php_upload_limit_kb()),
        'max_document_kb' => min(10240, php_upload_limit_kb()),
    ],

    /*
    |--------------------------------------------------------------------------
    | The administrator account
    |--------------------------------------------------------------------------
    |
    | Read by UserSeeder, and here rather than through `env()` at the point of
    | use for one reason: `php artisan config:cache` — which `optimize` runs,
    | and which every deployment runs — makes Laravel skip loading `.env`
    | altogether. An `env()` call outside a config file then returns null with
    | no error, so re-seeding on a deployed server set a *random* password
    | instead of the one in `.env` and locked the operator out of the panel.
    |
    | Config files are read before the cache is written, so the value is baked
    | in and survives. Leaving the password unset is still the safe default:
    | see UserSeeder.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@artaleca.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
