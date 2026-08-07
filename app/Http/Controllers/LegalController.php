<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        seo()
            ->title(__('legal.privacy_title'))
            ->description(__('legal.privacy_intro'))
            ->breadcrumbs($this->trail([['label' => __('legal.privacy_title'), 'url' => null]]));

        return view('pages.legal.privacy');
    }

    public function terms(): View
    {
        seo()
            ->title(__('legal.terms_title'))
            ->description(__('legal.terms_intro'))
            ->breadcrumbs($this->trail([['label' => __('legal.terms_title'), 'url' => null]]));

        return view('pages.legal.terms');
    }
}
