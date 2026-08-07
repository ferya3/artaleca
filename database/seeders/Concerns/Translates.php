<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

trait Translates
{
    /**
     * Shorthand for a translatable column.
     *
     * Seeded content is written inline in all three languages, so `t()` keeps
     * the data readable — the alternative is a wall of `['fa' => …, 'en' => …]`
     * that buries the actual copy.
     *
     * @return array{fa:string, en:string, ar:string}
     */
    protected function t(string $fa, string $en, string $ar): array
    {
        return ['fa' => $fa, 'en' => $en, 'ar' => $ar];
    }
}
