<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Locales;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.users.index', [
            'users' => User::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.form', ['user' => new User(['role' => User::ROLE_EDITOR, 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate($this->rules());

        User::create([
            ...$validated,
            'password' => $validated['password'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.created'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate($this->rules($user));

        // Blank password field means "leave it alone", not "clear it".
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $user->fill($validated);

        /*
         * An admin must not be able to demote or deactivate themselves — doing
         * so is the classic way to lock the last administrator out of the
         * panel entirely.
         */
        if ($request->user()->is($user)) {
            $user->role = User::ROLE_ADMIN;
            $user->is_active = true;
        } else {
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.updated'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        abort_if($request->user()->is($user), 403);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', __('admin.deleted'));
    }

    /** @return array<string, mixed> */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
            'locale' => ['required', Rule::in(Locales::codes())],
        ];
    }
}
