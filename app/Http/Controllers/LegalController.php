<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        seo()
            ->title(content('legal.privacy_title'))
            ->description(content('legal.privacy_intro'))
            ->breadcrumbs($this->trail([['label' => content('legal.privacy_title'), 'url' => null]]));

        return view('pages.legal.privacy');
    }

    public function terms(): View
    {
        seo()
            ->title(content('legal.terms_title'))
            ->description(content('legal.terms_intro'))
            ->breadcrumbs($this->trail([['label' => content('legal.terms_title'), 'url' => null]]));

        return view('pages.legal.terms');
    }
}
