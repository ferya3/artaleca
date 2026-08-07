<?php

declare(strict_types=1);

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
        'fax' => env('SITE_FAX', '+98 21 8888 0099'),
        'email' => env('SITE_EMAIL', 'info@artaleca.com'),
        'sales_email' => env('SITE_SALES_EMAIL', 'sales@artaleca.com'),
        'export_email' => env('SITE_EXPORT_EMAIL', 'export@artaleca.com'),
        'whatsapp' => env('SITE_WHATSAPP', '+989120000000'),

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
    */
    'uploads' => [
        'image_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],
        'document_mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'max_image_kb' => 4096,
        'max_document_kb' => 10240,
    ],
];
