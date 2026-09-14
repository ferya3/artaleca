<?php

declare(strict_types=1);

namespace App\Support;

/**
 * An Iranian mobile number, in the one shape a panel will accept.
 *
 * A number typed by a person arrives in a dozen forms — `۰۹۱۲…` in Persian
 * digits, `+98912…`, `0098912…`, with spaces or dashes — and an SMS panel
 * accepts exactly one of them. Normalising on the way in means the failure is a
 * validation message under the field rather than an SMS that silently never
 * arrives.
 *
 * Ported from the nobatdehi project with the panel settings.
 */
final class Mobile
{
    /** Persian and Arabic-Indic digits, both of which reach a form field. */
    private const EASTERN = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /** `09xxxxxxxxx`, or null when it is not a mobile number at all. */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr($value ?? '', self::EASTERN)) ?? '';

        $digits = match (true) {
            str_starts_with($digits, '0098') => substr($digits, 4),
            str_starts_with($digits, '098') => substr($digits, 3),
            str_starts_with($digits, '98') && strlen($digits) === 12 => substr($digits, 2),
            str_starts_with($digits, '0') => substr($digits, 1),
            default => $digits,
        };

        return preg_match('/^9\d{9}$/', $digits) ? '0'.$digits : null;
    }

    public static function isValid(?string $value): bool
    {
        return self::normalize($value) !== null;
    }
}
