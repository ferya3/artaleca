<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared visibility/ordering scopes for editor-managed content.
 *
 * Every public-facing query goes through `active()` so an unpublished record
 * can never leak into a listing, a sitemap or a related-items block.
 */
trait Publishable
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy($query->qualifyColumn('position'))
            ->orderBy($query->qualifyColumn('id'));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_featured'), true);
    }

    /** Slugs are the public identifier; ids never appear in a URL. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
