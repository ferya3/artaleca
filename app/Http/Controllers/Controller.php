<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Build a breadcrumb trail that always starts at the localised home page.
     *
     * Passing it to `seo()->breadcrumbs()` drives both the visible trail and
     * the BreadcrumbList JSON-LD, so the two can never drift apart.
     *
     * @param  list<array{label:string,url:?string}>  $crumbs
     * @return list<array{label:string,url:?string}>
     */
    protected function trail(array $crumbs): array
    {
        return array_merge(
            [['label' => __('nav.home'), 'url' => route('home', ['locale' => Locales::current()])]],
            $crumbs,
        );
    }
}
