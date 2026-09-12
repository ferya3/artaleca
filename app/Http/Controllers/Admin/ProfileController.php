<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Your own account.
 *
 * The users screen already let an administrator change anyone's password, but
 * it is administrator-only — an editor or a viewer could not reach any page
 * that would change their own, which left "ask the administrator" as the only
 * way to rotate a password. That is the arrangement under which people stop
 * rotating passwords.
 *
 * Nothing here touches role or active state. Those are decisions about a
 * person, made by an administrator on the users screen; this page is for the
 * things that are yours to change.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user)],
            'locale' => ['required', Rule::in(Locales::codes())],
        ]);

        $user->fill($validated)->save();

        return redirect()
            ->route('admin.profile.edit')
            ->with('status', __('admin.updated'));
    }

    public function password(Request $request): RedirectResponse
    {
        $request->validate([
            // `current_password` proves it is the account holder at the
            // keyboard and not someone who walked up to an unlocked screen.
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->forceFill(['password' => $request->string('password')->value()])->save();

        // The password just changed; every other session signed in with the old
        // one should stop working, which is the point of changing it.
        auth()->logoutOtherDevices($request->string('password')->value());

        return redirect()
            ->route('admin.profile.edit')
            ->with('status', __('admin.password_changed'));
    }
}
