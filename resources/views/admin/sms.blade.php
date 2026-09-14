@php
    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';

    $provider = old('provider', $settings['sms.provider'] ?: 'console');
@endphp

<x-layouts.admin :title="__('admin.sms.title')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.sms.title') }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">{{ __('admin.sms.intro') }}</p>

    @if ($configurationError)
        <p class="mt-4 max-w-2xl border-s-2 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            {{ $configurationError }}
        </p>
    @endif

    <form method="POST" action="{{ route('admin.sms.update') }}" class="mt-8 max-w-2xl space-y-4">
        @csrf
        @method('PUT')

        {{-- The number the alert rings. First, because it is the one field on
             this screen that changes after the panel is set up once. --}}
        <div class="panel p-4">
            <p class="mb-3 text-sm font-medium text-ink-800">{{ __('admin.sms.sales_mobile') }}</p>

            <input type="text" name="sales_mobile" dir="ltr" inputmode="tel"
                   value="{{ old('sales_mobile', $settings['sms.sales_mobile']) }}"
                   placeholder="09121234567"
                   class="{{ $inputClass }} @error('sales_mobile') border-red-500 @enderror">

            @error('sales_mobile')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.sales_mobile_hint') }}</p>
        </div>

        <div class="panel p-4">
            <p class="mb-3 text-sm font-medium text-ink-800">{{ __('admin.sms.provider') }}</p>

            <select name="provider" class="{{ $inputClass }}">
                @foreach ($providers as $key => $label)
                    <option value="{{ $key }}" @selected($provider === $key)>{{ $label }}</option>
                @endforeach
            </select>

            <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.provider_hint') }}</p>
        </div>

        {{-- Every provider's field, on one screen.

             Hiding the ones the chosen provider does not use would need script
             and would hide a value that is still saved — which is worse than a
             field that is simply blank. Each label says which providers read
             it. --}}
        <div class="panel space-y-4 p-4">
            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="sender">{{ __('admin.sms.sender') }}</label>
                <input id="sender" type="text" name="sender" dir="ltr" value="{{ old('sender', $settings['sms.sender']) }}"
                       class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.sender_hint') }}</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="username">{{ __('admin.sms.username') }}</label>
                <input id="username" type="text" name="username" dir="ltr" autocomplete="off"
                       value="{{ old('username', $settings['sms.username']) }}" class="{{ $inputClass }}">
            </div>

            {{-- Rendered empty every time: a saved credential is never sent back
                 to a browser, so blank means "keep the one you have". --}}
            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="password">{{ __('admin.sms.password') }}</label>
                <input id="password" type="password" name="password" dir="ltr" autocomplete="new-password"
                       placeholder="{{ $hasPassword ? __('admin.sms.saved_replace') : '' }}" class="{{ $inputClass }}">
                @if ($hasPassword)
                    <p class="mt-2 text-xs text-green-800">{{ __('admin.sms.saved') }}</p>
                @endif
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="api_key">{{ __('admin.sms.api_key') }}</label>
                <input id="api_key" type="password" name="api_key" dir="ltr" autocomplete="off"
                       placeholder="{{ $hasApiKey ? __('admin.sms.saved_replace') : '' }}" class="{{ $inputClass }}">
                @if ($hasApiKey)
                    <p class="mt-2 text-xs text-green-800">{{ __('admin.sms.saved') }}</p>
                @endif
                <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.secret_hint') }}</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="afe_domain">{{ __('admin.sms.afe_domain') }}</label>
                <input id="afe_domain" type="text" name="afe_domain" dir="ltr"
                       value="{{ old('afe_domain', $settings['sms.afe_domain']) }}"
                       placeholder="afe.ir" class="{{ $inputClass }}">
                <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.afe_domain_hint') }}</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-ink-800" for="custom_url">{{ __('admin.sms.custom_url') }}</label>
                <input id="custom_url" type="text" name="custom_url" dir="ltr"
                       value="{{ old('custom_url', $settings['sms.custom_url']) }}"
                       placeholder="https://panel.example.com/send?to={to}&text={text}"
                       class="{{ $inputClass }} @error('custom_url') border-red-500 @enderror">

                @error('custom_url')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <p class="mt-2 text-xs text-ink-500">{{ __('admin.sms.custom_url_hint') }}</p>

                <div class="mt-3 flex items-center gap-4">
                    @foreach (['GET', 'POST'] as $method)
                        <label class="flex items-center gap-2 text-sm text-ink-700">
                            <input type="radio" name="custom_method" value="{{ $method }}"
                                   @checked(old('custom_method', $settings['sms.custom_method'] ?: 'GET') === $method)
                                   class="h-4 w-4 border-ink-400 text-brand-600">
                            <span class="ltr-run">{{ $method }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="panel p-4">
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="enabled" value="1"
                       @checked(old('enabled', $settings['sms.enabled'] === '1'))
                       class="h-4 w-4 border-ink-400 text-brand-600">
                {{ __('admin.sms.enabled') }}
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </div>
    </form>

    {{-- The buttons, each its own POST so none of them carries the credential
         fields along with it. --}}
    <div class="mt-6 max-w-2xl border-t border-hairline pt-6">
        <p class="text-sm font-medium text-ink-800">{{ __('admin.sms.test_title') }}</p>
        <p class="mt-1.5 text-xs leading-relaxed text-ink-500">{{ __('admin.sms.test_hint') }}</p>

        <div class="mt-4 flex flex-wrap items-end gap-3">
            <div class="min-w-0 flex-1">
                <input form="sms-test" type="text" name="mobile" dir="ltr" inputmode="tel"
                       value="{{ old('mobile') }}" placeholder="{{ $settings['sms.sales_mobile'] ?: '09121234567' }}"
                       class="{{ $inputClass }} @error('mobile') border-red-500 @enderror">
                @error('mobile')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <form id="sms-test" method="POST" action="{{ route('admin.sms.test') }}">
                @csrf
                <x-button type="submit" variant="outline" size="sm">{{ __('admin.sms.send_test') }}</x-button>
            </form>

            {{-- Only useful for the afe family, and harmless elsewhere: it tries
                 the same credentials against each domain and says which
                 answered. --}}
            <form method="POST" action="{{ route('admin.sms.probe') }}">
                @csrf
                <input type="hidden" name="mobile" value="{{ $settings['sms.sales_mobile'] }}">
                <x-button type="submit" variant="outline" size="sm">{{ __('admin.sms.probe') }}</x-button>
            </form>
        </div>

        @if (session('probe'))
            <div class="mt-4 space-y-2">
                @if (session('probe')['suggest'])
                    <p class="border-s-2 border-green-600 bg-green-50 px-3 py-2 text-xs text-green-900">
                        {{ __('admin.sms.probe_found', ['host' => session('probe')['suggest']]) }}
                    </p>
                @endif

                @foreach (session('probe')['results'] as $row)
                    <div class="rounded-md border border-hairline px-3 py-2 text-xs">
                        <p class="flex items-center justify-between gap-3">
                            <span class="ltr-run font-medium text-ink-800">{{ $row['host'] }}</span>
                            <span class="{{ $row['ok'] ? 'text-green-700' : 'text-ink-500' }}">
                                {{ $row['ok'] ? __('admin.sms.probe_ok') : __('admin.sms.probe_failed') }}
                                <span class="ltr-run">{{ $row['code'] ?: '' }}</span>
                            </span>
                        </p>
                        @if (filled($row['body']))
                            <p class="mt-1.5 break-words text-ink-500">{{ \Illuminate\Support\Str::limit($row['body'], 200) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($hasPassword || $hasApiKey)
            <form method="POST" action="{{ route('admin.sms.destroy') }}" class="mt-5">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                    {{ __('admin.sms.clear_credentials') }}
                </button>
            </form>
        @endif
    </div>

    <p class="mt-6 max-w-2xl text-xs leading-relaxed text-ink-500">{{ __('admin.sms.queue_note') }}</p>

</x-layouts.admin>
