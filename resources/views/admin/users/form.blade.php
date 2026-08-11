@php
    use App\Models\User;
    use App\Support\Locales;

    $isNew = ! $user->exists;
    $isSelf = auth()->user()->is($user);
    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';
@endphp

<x-layouts.admin :title="__('admin.users')">

    <a href="{{ route('admin.users.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
        <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span> {{ __('admin.users') }}
    </a>

    <h1 class="mt-1 text-xl font-bold text-ink-950">{{ $isNew ? __('admin.create') : __('admin.edit') }}</h1>

    <form method="POST" action="{{ $isNew ? route('admin.users.store') : route('admin.users.update', $user) }}"
          class="mt-8 max-w-xl space-y-5">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div class="panel p-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="name" class="text-sm font-medium text-ink-800">{{ __('form.name') }}</label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" class="{{ $inputClass }}">
                    @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label for="email" class="text-sm font-medium text-ink-800">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" required dir="ltr" autocomplete="username"
                           value="{{ old('email', $user->email) }}" class="{{ $inputClass }}">
                    @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-medium text-ink-800">
                        {{ __('admin.password') }}
                        @unless ($isNew)
                            <span class="ms-1 text-xs font-normal text-ink-400">({{ __('common.optional') }})</span>
                        @endunless
                    </label>
                    <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password"
                           @if ($isNew) required @endif class="{{ $inputClass }}">
                    @error('password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-sm font-medium text-ink-800">{{ __('admin.password') }} ×2</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr"
                           autocomplete="new-password" @if ($isNew) required @endif class="{{ $inputClass }}">
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="role" class="text-sm font-medium text-ink-800">Role</label>
                    <select id="role" name="role" class="{{ $inputClass }}" @if ($isSelf) disabled @endif>
                        @foreach (User::ROLES as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    @if ($isSelf)
                        {{-- Disabled inputs are not submitted; resend the value so
                             the request still validates, and the controller
                             independently refuses to demote the current user. --}}
                        <input type="hidden" name="role" value="{{ $user->role }}">
                        <p class="text-xs text-ink-500">{{ __('admin.self_role_locked') }}</p>
                    @endif
                    @error('role') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="locale" class="text-sm font-medium text-ink-800">{{ __('nav.language') }}</label>
                    <select id="locale" name="locale" class="{{ $inputClass }}">
                        @foreach (Locales::all() as $code => $meta)
                            <option value="{{ $code }}" @selected(old('locale', $user->locale ?? Locales::default()) === $code)>
                                {{ $meta['native'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2.5 text-sm text-ink-700">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $user->is_active ?? true))
                               @if ($isSelf) disabled @endif
                               class="h-4 w-4 border-ink-400 text-brand-600">
                        {{ __('admin.active') }}
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-ink-500 hover:text-ink-900">{{ __('admin.cancel') }}</a>
        </div>
    </form>

</x-layouts.admin>
