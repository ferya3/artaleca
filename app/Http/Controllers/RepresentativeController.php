<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesSalesDesk;
use App\Http\Requests\RepresentationRequest;
use App\Models\ContactMessage;
use App\Models\Partner;
use App\Support\Locales;
use App\Support\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Sales representatives.
 *
 * Built on the existing Partner model rather than a new one: a representative
 * is a partner with a `kind`, and the admin already manages the table, so this
 * adds a public page and nothing else to maintain.
 */
class RepresentativeController extends Controller
{
    use NotifiesSalesDesk;

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

    /**
     * An application to represent the plant.
     *
     * Stored as an enquiry with a type of its own rather than as a new model:
     * it arrives in the same inbox, is triaged with the same statuses and
     * answered by the same desk, and giving it a separate table would have
     * meant a second inbox nobody remembers to open.
     */
    public function store(RepresentationRequest $request): RedirectResponse
    {
        $message = ContactMessage::create([
            ...$request->safe()->only(['name', 'company', 'email', 'phone', 'country_code']),
            'type' => 'representation',
            'subject' => __('representatives.apply_subject', [], Locales::default()),
            'message' => $request->string('message')->toString(),
            'details' => $request->details(),
            'locale' => Locales::current(),
            'ip_hash' => ContactMessage::hashIp($request->ip()),
            'user_agent' => str($request->userAgent() ?? '')->limit(250)->toString(),
            'referer' => str($request->headers->get('referer') ?? '')->limit(250)->toString(),
        ]);

        $this->notifySalesDesk($message);

        return redirect()
            ->route('representatives')
            ->with('status', __('representatives.apply_success'))
            ->withFragment('apply');
    }
}
