<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The messenger desks: one tap from any page to somebody who answers.
 *
 * An enquiry form is the right way to ask for a price and the wrong way to ask
 * a question. Somebody standing on a site with a pallet of the wrong grade in
 * front of them wants an answer in the next minute, and in Iran that means
 * WhatsApp, Telegram or Rubika — not a form, not an email, and often not a
 * phone call either. Without a visible messenger the question either goes
 * unasked or arrives through the quote form, where it sits behind a product
 * field and a quantity nobody wanted to fill in.
 *
 * All four channels are settings, so the accounts can move to whoever is
 * actually answering that week without a deploy. A channel with nothing in it
 * does not appear — a dead WhatsApp link is worse than no WhatsApp link, since
 * it costs the visitor the one thing they came for and says the company does
 * not answer.
 *
 * What is stored is a number or a username, not a URL. Editors paste whatever
 * their phone gave them — `@artaleca`, `https://t.me/artaleca`, `۰۹۱۲…`,
 * `+98 912 …` — and every one of those becomes the same link here, because the
 * alternative is a settings field that silently produces a 404.
 */
final class QuickContact
{
    /**
     * Every reachable channel, in the order the widget lists them.
     *
     * The phone last, deliberately. It is the one channel that is always
     * configured, and it is also the one somebody reaches for when the others
     * have not worked — putting it first would push three faster answers below
     * the slowest.
     *
     * @return array<string, array{href: string, detail: string|null, external: bool}>
     */
    public static function channels(): array
    {
        $channels = [];

        if ($number = self::whatsappNumber()) {
            /*
             * The number to *dial*, not the `98…` form the link needs:
             * somebody saving the contact or ringing from another handset
             * needs the number they would type.
             *
             * Normalised rather than echoed as stored, because an editor types
             * `۰۹۱۲ ۳۴۵ ۶۷۸۹` and those Persian digits would then appear on
             * the English page — the site's digit rendering localises Latin
             * digits per language, so a number has to arrive here in Latin to
             * come out right in all three. A number the normaliser does not
             * recognise is shown as typed; it is somebody's international
             * number and only they know how it should read.
             */
            $channels['whatsapp'] = self::link(
                'https://wa.me/'.$number,
                Mobile::normalize(Contact::value('whatsapp')) ?? Contact::value('whatsapp'),
            );
        }

        if ($handle = self::handle('telegram')) {
            $channels['telegram'] = self::link('https://t.me/'.$handle, '@'.$handle);
        }

        if ($handle = self::handle('rubika')) {
            $channels['rubika'] = self::link('https://rubika.ir/'.$handle, '@'.$handle);
        }

        $phone = Contact::value('sales_phone');

        if (filled($phone)) {
            // Not external: a `tel:` href opens the dialler, and `target=_blank`
            // on one leaves an empty tab behind on a desktop browser.
            $channels['phone'] = self::link('tel:'.Contact::tel('sales_phone'), $phone, external: false);
        }

        return $channels;
    }

    public static function any(): bool
    {
        return self::channels() !== [];
    }

    /**
     * The fields the panel offers, in the order it shows them.
     *
     * @return list<string>
     */
    public static function fields(): array
    {
        return ['whatsapp', 'telegram', 'rubika'];
    }

    /**
     * `wa.me` wants an international number with no `+` and no separators.
     *
     * An Iranian mobile is recognised and rewritten to `98…`, because that is
     * what an editor will type and `09…` would send WhatsApp looking for a
     * number in whatever country the *visitor* is in.
     *
     * Anything else is trusted only if it reads as an international number —
     * the export desk or a WhatsApp Business landline may well be on one this
     * does not recognise, and refusing those would be worse than trusting
     * them. What it must not do is trust a *national* number: `02188880011`
     * has a trunk prefix instead of a country code, so `wa.me` cannot resolve
     * it and the link is dead. A leading zero is exactly what separates the
     * two, since no international number carries one. The way to offer a
     * landline here is to write it as `+98 21 …`, and the panel hint says so.
     */
    public static function whatsappNumber(): ?string
    {
        $iranian = Mobile::normalize(Contact::value('whatsapp'));

        if ($iranian !== null) {
            return '98'.substr($iranian, 1);
        }

        $digits = Mobile::digits(Contact::value('whatsapp'));

        return preg_match('/^[1-9]\d{9,14}$/', $digits) ? $digits : null;
    }

    /**
     * A messenger username, however it was pasted.
     *
     * `https://t.me/artaleca`, `t.me/artaleca`, `@artaleca` and `artaleca` all
     * arrive here and all leave as `artaleca`. Everything outside the character
     * set both messengers allow is dropped rather than escaped, because what is
     * being built is a path segment and a stray `/` or `?` in one is a link to
     * somewhere else entirely.
     */
    public static function handle(string $field): ?string
    {
        $value = trim(Contact::value($field));

        if ($value === '') {
            return null;
        }

        // Keep only what follows the last slash, so a full profile URL reduces
        // to its username and a bare username is untouched.
        $value = (string) preg_replace('#^.*/#', '', $value);
        $value = ltrim($value, '@');
        $value = (string) preg_replace('/[^A-Za-z0-9_]/', '', $value);

        return preg_match('/^[A-Za-z0-9_]{3,32}$/', $value) ? $value : null;
    }

    /** @return array{href: string, detail: string|null, external: bool} */
    private static function link(string $href, ?string $detail = null, bool $external = true): array
    {
        return ['href' => $href, 'detail' => $detail, 'external' => $external];
    }
}
