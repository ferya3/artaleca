<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Mobile;
use App\Support\Sms;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The SMS panel, and the number an enquiry rings.
 *
 * A screen of its own rather than a section of Settings, because half of what
 * makes an integration like this actually work is not a field: it is a button
 * that sends a real message and tells you what the panel said. Without it the
 * first time anyone finds out the credentials are wrong is when a customer's
 * enquiry goes unanswered.
 */
class SmsController extends Controller
{
    public function edit(): View
    {
        $this->authorize('manage-settings');

        $settings = Sms::settings();

        return view('admin.sms', [
            'settings' => $settings,
            'providers' => Sms::providers(),
            // Whether a credential is saved, never the credential itself.
            'hasPassword' => filled($settings['sms.password']),
            'hasApiKey' => filled($settings['sms.api_key']),
            'configurationError' => Sms::configurationError($settings),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'provider' => ['required', Rule::in(array_keys(Sms::providers()))],
            'sender' => ['nullable', 'string', 'max:32'],
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:200'],
            'api_key' => ['nullable', 'string', 'max:400'],
            'afe_domain' => ['nullable', 'string', 'max:200'],
            'custom_url' => ['nullable', 'string', 'max:600', 'starts_with:http://,https://'],
            'custom_method' => ['required', Rule::in(['GET', 'POST'])],
            'sales_mobile' => ['nullable', 'string', 'max:32'],
        ]);

        if (filled($validated['sales_mobile'] ?? null) && ! Mobile::isValid($validated['sales_mobile'])) {
            return back()->withInput()->withErrors(['sales_mobile' => __('admin.sms.errors.bad_mobile')]);
        }

        if ($validated['provider'] === 'custom' && blank($validated['custom_url'] ?? null)) {
            return back()->withInput()->withErrors(['custom_url' => __('admin.sms.errors.no_custom_url')]);
        }

        Sms::store([
            'sms.enabled' => $request->boolean('enabled') ? '1' : '0',
            'sms.provider' => $validated['provider'],
            'sms.sender' => trim((string) ($validated['sender'] ?? '')),
            'sms.username' => trim((string) ($validated['username'] ?? '')),
            'sms.password' => (string) ($validated['password'] ?? ''),
            'sms.api_key' => (string) ($validated['api_key'] ?? ''),
            'sms.afe_domain' => trim((string) ($validated['afe_domain'] ?? '')),
            'sms.custom_url' => trim((string) ($validated['custom_url'] ?? '')),
            'sms.custom_method' => $validated['custom_method'],
            // Stored in the one shape a panel accepts, whatever was typed.
            'sms.sales_mobile' => Mobile::normalize($validated['sales_mobile'] ?? '') ?? '',
        ]);

        return redirect()->route('admin.sms.edit')->with('status', __('admin.updated'));
    }

    /** Forget the saved credentials — the one way to remove them. */
    public function destroy(): RedirectResponse
    {
        $this->authorize('manage-settings');

        Sms::store(['sms.enabled' => '0']);

        foreach (Sms::SECRETS as $key) {
            \App\Models\Setting::put($key, null, 'sms', translatable: false);
        }

        return redirect()->route('admin.sms.edit')->with('status', __('admin.sms.credentials_cleared'));
    }

    /**
     * Send a real message with the saved settings.
     *
     * Deliberately not queued: the operator pressed a button and needs the
     * panel's own answer, not "added to the queue".
     */
    public function test(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $mobile = Mobile::normalize((string) $request->input('mobile')) ?: Sms::salesMobile();

        if ($mobile === '') {
            return back()->withErrors(['mobile' => __('admin.sms.errors.bad_mobile')]);
        }

        $settings = Sms::settings();

        if ($reason = Sms::configurationError($settings)) {
            return back()->with('error', $reason);
        }

        [$status, $detail] = Sms::send($mobile, __('admin.sms.test_message'), $settings);

        return back()->with($status === Sms::STATUS_SENT ? 'status' : 'error', $detail);
    }

    /**
     * Which of the reseller domains this account is actually on.
     *
     * The afe panel answers on several, and a wrong one looks exactly like
     * wrong credentials. This tries each and reports what came back.
     */
    public function probe(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $mobile = Mobile::normalize((string) $request->input('mobile')) ?: Sms::salesMobile();

        if ($mobile === '') {
            return back()->withErrors(['mobile' => __('admin.sms.errors.bad_mobile')]);
        }

        $results = Sms::probe(Sms::settings(), $mobile);
        $working = collect($results)->firstWhere('ok', true);

        return back()->with('probe', [
            'results' => $results,
            'suggest' => $working ? preg_replace('#^www\.#i', '', (string) $working['host']) : null,
        ]);
    }
}
