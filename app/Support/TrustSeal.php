<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;

/**
 * The eNamad trust seal (نماد اعتماد الکترونیکی) in the footer.
 *
 * eNamad hands out a ready-made `<a><img></a>` snippet. It is *not* pasted
 * into the page as given: a settings box whose contents are echoed raw is a
 * box that runs any script anybody with panel access types into it. Only the
 * two values that identify the seal — the numeric `id` and the alphanumeric
 * `Code` — are pulled out of whatever was pasted, and the markup is built here
 * from those, escaped like everything else.
 *
 * So the panel accepts the whole snippet, the link alone, or just
 * `id=…&Code=…` — whichever the person renewing the seal has to hand — and
 * something that does not contain both values renders nothing rather than a
 * broken badge.
 *
 * `referrerpolicy="origin"` is kept on both elements and it is not decoration:
 * eNamad checks the Referer to confirm the seal is being shown on the domain
 * it was issued to. The site-wide `strict-origin-when-cross-origin` would send
 * the same thing today, but the seal should not depend on that staying true.
 */
final class TrustSeal
{
    public const SETTING = 'seo.enamad_seal';

    public const HOST = 'https://trustseal.enamad.ir';

    /**
     * @return array{id: string, code: string, href: string, src: string}|null
     */
    public static function enamad(): ?array
    {
        $raw = Setting::get(self::SETTING);

        $seal = self::parse(is_string($raw) && trim($raw) !== '' ? $raw : (string) config('site.seo.enamad_seal'));

        if ($seal === null) {
            return null;
        }

        $query = http_build_query(['id' => $seal['id'], 'Code' => $seal['code']]);

        return $seal + [
            'href' => self::HOST.'/?'.$query,
            'src' => self::HOST.'/logo.aspx?'.$query,
        ];
    }

    /**
     * @return array{id: string, code: string}|null
     */
    public static function parse(string $text): ?array
    {
        // `&amp;` as well as `&`: the snippet is HTML, and copied out of a
        // page's source it arrives entity-encoded.
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        if (! preg_match('/[?&\s]id=(\d{1,12})(?!\d)/i', ' '.$text, $id)
            || ! preg_match('/[?&\s]Code=([A-Za-z0-9]{8,64})(?![A-Za-z0-9])/', ' '.$text, $code)) {
            return null;
        }

        return ['id' => $id[1], 'code' => $code[1]];
    }
}
