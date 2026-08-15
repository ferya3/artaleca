<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\Bale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where a new enquiry goes besides the inbox.
 *
 * A screen of its own rather than a section of Settings, because two of the
 * three things on it are buttons rather than fields: finding a chat id means
 * reading raw JSON out of an API, and confirming the whole path works means
 * waiting for a real customer unless something here can send a test. Both of
 * those are what make the difference between a configured integration and an
 * abandoned one.
 */
class NotificationController extends Controller
{
    public function edit(): View
    {
        $this->authorize('manage-settings');

        return view('admin.notifications', [
            'enabled' => (bool) Setting::get('notifications.bale_enabled'),
            'configured' => Bale::configured(),
            'chatId' => Bale::chatId(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('manage-settings');

        $validated = $request->validate([
            'bale_enabled' => ['nullable', 'boolean'],
            'bale_token' => ['nullable', 'string', 'max:255'],
            'bale_chat_id' => ['nullable', 'string', 'max:64'],
        ]);

        /*
         * A blank token field means "leave it alone", not "delete it". The field
         * is rendered empty on every visit — a saved credential is never sent
         * back to the browser — so treating blank as a deletion would wipe the
         * token every time somebody changed the chat id.
         */
        if (filled($validated['bale_token'] ?? null)) {
            Bale::storeToken(trim($validated['bale_token']));
        }

        Setting::put('notifications.bale_chat_id', $validated['bale_chat_id'] ?? null, 'notifications', translatable: false);
        Setting::put('notifications.bale_enabled', $request->boolean('bale_enabled'), 'notifications', translatable: false);

        return redirect()
            ->route('admin.notifications.edit')
            ->with('status', __('admin.updated'));
    }

    /** Forget the token entirely — the one way to remove it. */
    public function destroy(): RedirectResponse
    {
        $this->authorize('manage-settings');

        Bale::storeToken('');
        Setting::put('notifications.bale_enabled', false, 'notifications', translatable: false);

        return redirect()
            ->route('admin.notifications.edit')
            ->with('status', __('admin.notifications.token_cleared'));
    }

    /**
     * The chats the bot can see, so a chat id is picked from a list rather
     * than dug out of an API response by hand.
     */
    public function chats(): RedirectResponse
    {
        $this->authorize('manage-settings');

        $chats = Bale::chats();

        return redirect()
            ->route('admin.notifications.edit')
            ->with('chats', $chats)
            ->with($chats === [] ? 'error' : 'status', $chats === []
                ? __('admin.notifications.no_chats')
                : __('admin.notifications.chats_found', ['count' => count($chats)]));
    }

    /**
     * Prove the whole path works now, rather than finding out on the first
     * real enquiry that the server cannot reach Bale at all.
     */
    public function test(): RedirectResponse
    {
        $this->authorize('manage-settings');

        if (! Bale::configured()) {
            return redirect()->route('admin.notifications.edit')
                ->with('error', __('admin.notifications.no_token'));
        }

        $bot = Bale::me();

        if ($bot === null) {
            return redirect()->route('admin.notifications.edit')
                ->with('error', __('admin.notifications.unreachable'));
        }

        if (blank(Bale::chatId())) {
            return redirect()->route('admin.notifications.edit')
                ->with('error', __('admin.notifications.no_chat_id', ['bot' => $bot['username'] ?? '']));
        }

        $sent = Bale::send(__('admin.notifications.test_message'));

        return redirect()->route('admin.notifications.edit')
            ->with($sent ? 'status' : 'error', $sent
                ? __('admin.notifications.test_sent')
                : __('admin.notifications.test_failed'));
    }
}
