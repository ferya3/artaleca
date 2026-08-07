<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesSalesDesk;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    use NotifiesSalesDesk;

    public function index(): View
    {
        seo()
            ->title(__('seo.contact_title'))
            ->description(__('seo.contact_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.contact'), 'url' => null],
            ]));

        return view('pages.contact');
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->only([
                'name', 'company', 'email', 'phone', 'country_code', 'subject', 'message',
            ]),
            'type' => 'contact',
            'locale' => Locales::current(),
            'ip_hash' => ContactMessage::hashIp($request->ip()),
            'user_agent' => str($request->userAgent() ?? '')->limit(250)->toString(),
            'referer' => str($request->headers->get('referer') ?? '')->limit(250)->toString(),
        ]);

        $this->notifySalesDesk($message);

        return redirect()
            ->route('contact')
            ->with('status', __('form.contact_success'))
            ->withFragment('form');
    }
}
