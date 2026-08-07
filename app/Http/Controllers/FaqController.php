<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Support\Schema;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::query()->active()->ordered()->get();

        seo()
            ->title(__('seo.faq_title'))
            ->description(__('seo.faq_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.faq'), 'url' => null],
            ]))
            ->schema(Schema::faqPage($faqs));

        return view('pages.faq', [
            'groups' => $faqs->groupBy('group'),
        ]);
    }
}
