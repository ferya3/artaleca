<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesSalesDesk;
use App\Http\Requests\QuoteRequest;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    use NotifiesSalesDesk;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'product' => ['nullable', 'string', 'max:120'],
        ]);

        seo()
            ->title(content('seo.quote_title'))
            ->description(content('seo.quote_description'))
            ->breadcrumbs($this->trail([
                ['label' => content('nav.quote'), 'url' => null],
            ]))
            // A form page has no content worth ranking and would compete with
            // the product pages that should send traffic here.
            ->noindex();

        return view('pages.quote', [
            'products' => Product::query()->active()->ordered()->with('category')->get(),
            'selected' => Product::query()->active()->where('slug', $validated['product'] ?? null)->first(),
        ]);
    }

    public function store(QuoteRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->only([
                'name', 'company', 'email', 'phone', 'country_code', 'subject',
                'product_id', 'quantity', 'delivery_terms',
            ]),
            'message' => $request->string('message')->toString(),
            'type' => 'quote',
            'locale' => Locales::current(),
            'ip_hash' => ContactMessage::hashIp($request->ip()),
            'user_agent' => str($request->userAgent() ?? '')->limit(250)->toString(),
            'referer' => str($request->headers->get('referer') ?? '')->limit(250)->toString(),
        ]);

        $this->notifySalesDesk($message);

        return redirect()
            ->route('quote')
            ->with('status', __('form.quote_success'))
            ->withFragment('form');
    }
}
