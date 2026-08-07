<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Support\Locales;

/**
 * Per-locale content stored in a JSON column, e.g.
 *
 *     {"fa": "لیکای ۳ تا ۱۰", "en": "LECA 3–10", "ar": "ليكا ٣-١٠"}
 *
 * Reading `$product->title` returns the string for the active locale (falling
 * back to the default locale, then to any non-empty value). Writing a plain
 * string only touches the active locale, so an editor working in one language
 * can never wipe the other two.
 *
 * Slugs are deliberately *not* translatable — a single Latin slug is shared by
 * all locales so that alternate-language URLs differ only by their prefix,
 * which keeps hreflang and canonical tags trivial and URLs stable.
 *
 * Implementing models must declare:
 *
 *     protected array $translatable = ['title', 'body'];
 *
 * and cast those attributes to `array`.
 */
trait HasTranslations
{
    /** @return list<string> */
    public function translatableAttributes(): array
    {
        return $this->translatable ?? [];
    }

    public function isTranslatableAttribute(string $key): bool
    {
        return in_array($key, $this->translatableAttributes(), true);
    }

    public function getAttribute($key)
    {
        if (is_string($key) && $this->isTranslatableAttribute($key)) {
            return $this->getTranslation($key);
        }

        return parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        if (is_string($key) && $this->isTranslatableAttribute($key) && ! is_array($value) && $value !== null) {
            return $this->setTranslation($key, Locales::current(), (string) $value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Resolve one translatable attribute.
     *
     * Falls back default-locale first, then to the first non-empty value, so a
     * partially translated record still renders something rather than a hole.
     */
    public function getTranslation(string $key, ?string $locale = null, bool $fallback = true): ?string
    {
        $translations = $this->getTranslations($key);
        $locale = $locale ?? Locales::current();

        if (filled($translations[$locale] ?? null)) {
            return $translations[$locale];
        }

        if (! $fallback) {
            return null;
        }

        $default = Locales::default();

        if (filled($translations[$default] ?? null)) {
            return $translations[$default];
        }

        foreach ($translations as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * The raw locale => value map for an attribute.
     *
     * @return array<string, string|null>
     */
    public function getTranslations(string $key): array
    {
        $raw = parent::getAttribute($key);

        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }

        return is_array($raw) ? $raw : [];
    }

    public function setTranslation(string $key, string $locale, ?string $value): static
    {
        $translations = $this->getTranslations($key);
        $translations[$locale] = $value;

        parent::setAttribute($key, $translations);

        return $this;
    }

    /**
     * Replace every locale at once, e.g. from an admin form.
     *
     * @param  array<string, string|null>  $values
     */
    public function setTranslations(string $key, array $values): static
    {
        $filtered = array_intersect_key($values, array_flip(Locales::codes()));

        parent::setAttribute($key, $filtered);

        return $this;
    }

    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        return filled($this->getTranslations($key)[$locale ?? Locales::current()] ?? null);
    }

    /**
     * Serialise translatable attributes as their resolved strings so API and
     * `@json()` output matches what the page renders.
     */
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        foreach ($this->translatableAttributes() as $key) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = $this->getTranslation($key);
            }
        }

        return $attributes;
    }
}
