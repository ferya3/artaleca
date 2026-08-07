<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Substring search across a model's columns.
 *
 * The site's searchable text lives in JSON columns keyed by locale, so a LIKE
 * over the raw column matches every language at once — which is what a visitor
 * wants: typing "leca" in the Persian UI should still find a grade whose
 * Persian name is in Persian script but whose SKU is Latin.
 */
final class Search
{
    /**
     * Turn user input into a LIKE pattern with the wildcards neutralised.
     *
     * Without this, a search for "%" matches every row, and "_" matches any
     * single character — user input silently becoming query syntax.
     */
    public static function pattern(string $term): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
    }

    /**
     * Match any of the given columns against the term.
     *
     * An explicit `ESCAPE '\'` clause is required rather than assumed: MySQL
     * treats backslash as the default escape character but SQLite does not, so
     * without it the escaping above would silently do nothing on SQLite.
     *
     * @param  list<string>  $columns
     */
    public static function anyLike(Builder $query, array $columns, string $term): Builder
    {
        $pattern = self::pattern($term);

        return $query->where(function (Builder $inner) use ($columns, $pattern) {
            $grammar = $inner->getQuery()->getGrammar();

            foreach (array_values($columns) as $index => $column) {
                $sql = $grammar->wrap($inner->qualifyColumn($column))." like ? escape '\\'";

                $index === 0
                    ? $inner->whereRaw($sql, [$pattern])
                    : $inner->orWhereRaw($sql, [$pattern]);
            }
        });
    }
}
