<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Support\Schema;
use Illuminate\Contracts\View\View;

/**
 * Sales representatives.
 *
 * Built on the existing Partner model rather than a new one: a representative
 * is a partner with a `kind`, and the admin already manages the table, so this
 * adds a public page and nothing else to maintain.
 */
class RepresentativeController extends Controller
{
    public function index(): View
    {
        $representatives = Partner::query()
            ->active()
            ->ofKind('representative')
            ->ordered()
            ->get();

        seo()
            ->title(__('representatives.title'))
            ->description(__('representatives.intro'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.representatives'), 'url' => null],
            ]))
            ->schema(Schema::itemList(
                $representatives->map(fn (Partner $partner) => [
                    'name' => (string) $partner->name,
                    'url' => $partner->website ?: url()->current(),
                ])->all()
            ));

        return view('pages.representatives', [
            'representatives' => $representatives,
        ]);
    }
}
