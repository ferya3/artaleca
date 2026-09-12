@php
    use App\Support\Locales;

    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';
@endphp

<x-layouts.admin :title="__('admin.profile')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.profile') }}</h1>
    <p class="mt-1 max-w-xl text-sm text-ink-600">{{ __('admin.profile_intro') }}</p>

    <div class="mt-8 grid max-w-4xl gap-8 lg:grid-cols-2 lg:items-start">

        {{-- ── Who you are ──────────────────────────────────────────────── --}}
        <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel space-y-5 p-5">
                <div class="flex flex-col gap-1.5">
                    <label for="name" class="text-sm font-medium text-ink-800">{{ __('form.name') }}</label>
                    <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}" class="{{ $inputClass }}">
                    @error('name') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="email" class="text-sm font-medium text-ink-800">{{ __('admin.email') }}</label>
                    <input id="email" name="email" type="email" required dir="ltr" autocomplete="username"
                           value="{{ old('email', $user->email) }}" class="{{ $inputClass }}">
                    @error('email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
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

                {{-- Shown, not editable: which of these you are is an
                     administrator's decision, and hiding it would only make
                     people guess why a menu is missing. --}}
                <div class="border-t border-hairline pt-4">
                    <p class="text-xs text-ink-500">{{ __('admin.role') }}</p>
                    <p class="mt-1 text-sm font-medium text-ink-800">{{ __('admin.roles.'.$user->role) }}</p>
                    <p class="mt-1 text-xs text-ink-500">{{ __('admin.role_help.'.$user->role) }}</p>
                </div>
            </div>

            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </form>

        {{-- ── Your password ────────────────────────────────────────────── --}}
        <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel space-y-5 p-5">
                <h2 class="text-sm font-bold text-ink-900">{{ __('admin.change_password') }}</h2>

                <div class="flex flex-col gap-1.5">
                    <label for="current_password" class="text-sm font-medium text-ink-800">{{ __('admin.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password" required dir="ltr"
                           autocomplete="current-password" class="{{ $inputClass }}">
                    @error('current_password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="text-sm font-medium text-ink-800">{{ __('admin.new_password') }}</label>
                    <input id="password" name="password" type="password" required dir="ltr"
                           autocomplete="new-password" class="{{ $inputClass }}">
                    @error('password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="password_confirmation" class="text-sm font-medium text-ink-800">{{ __('admin.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required dir="ltr"
                           autocomplete="new-password" class="{{ $inputClass }}">
                </div>
            </div>

            <x-button type="submit">{{ __('admin.change_password') }}</x-button>
        </form>
    </div>

</x-layouts.admin>
