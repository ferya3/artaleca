<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * A date and time as a reader in this language expects it.
 *
 * Persian and Kurdish readers in Iran live by the solar hijri calendar, and an
 * order placed "16 November" has to be converted in their head before it means
 * anything — so those two get ۲۵ آبان ۱۴۰۵. Everyone else gets the Gregorian
 * date in their own language. Always on Tehran time: the plant, the bank and
 * the customer are all on it, and the server is not.
 *
 * Without the intl extension it falls back to Gregorian rather than failing;
 * Ubuntu's php-intl is in the install guide.
 */
final class Dates
{
    public const ZONE = 'Asia/Tehran';

    public static function format(?CarbonInterface $at, bool $withTime = true): string
    {
        if ($at === null) {
            return '';
        }

        $at = $at->copy()->setTimezone(self::ZONE);
        $locale = Locales::current();

        if (class_exists(\IntlDateFormatter::class)) {
            $calendar = in_array($locale, ['fa', 'ku'], true) ? '@calendar=persian' : '';
            $tag = ['fa' => 'fa_IR', 'ku' => 'fa_IR', 'ar' => 'ar', 'ru' => 'ru_RU'][$locale] ?? 'en_GB';

            $formatter = new \IntlDateFormatter(
                $tag.$calendar,
                \IntlDateFormatter::MEDIUM,
                $withTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE,
                self::ZONE,
                $calendar === '' ? \IntlDateFormatter::GREGORIAN : \IntlDateFormatter::TRADITIONAL,
            );

            $out = $formatter->format($at->toDateTimeImmutable());

            if (is_string($out)) {
                return $out;
            }
        }

        return $at->locale($locale)->isoFormat($withTime ? 'D MMMM Y, HH:mm' : 'D MMMM Y');
    }
}
