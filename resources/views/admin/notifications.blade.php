@php
    $inputClass = 'w-full border border-ink-300 bg-white px-3 py-2.5 text-sm text-ink-900
                   focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12';
@endphp

<x-layouts.admin :title="__('admin.notifications.title')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.notifications.title') }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">{{ __('admin.notifications.intro') }}</p>

    {{-- The setup, in the order it has to happen. Written out here rather than
         left to a manual: the three steps are done inside another app, and
         nobody is going to alt-tab to documentation for them. --}}
    <ol class="mt-6 max-w-2xl list-decimal space-y-2 ps-5 text-sm leading-relaxed text-ink-600">
        <li>{{ __('admin.notifications.step_bot') }}</li>
        <li>{{ __('admin.notifications.step_group') }}</li>
        <li>{{ __('admin.notifications.step_id') }}</li>
    </ol>

    <form method="POST" action="{{ route('admin.notifications.update') }}" class="mt-8 max-w-2xl space-y-4">
        @csrf
        @method('PUT')

        <div class="panel p-4">
            <p class="mb-3 text-sm font-medium text-ink-800">{{ __('admin.notifications.token') }}</p>

            @if ($configured)
                <p class="mb-2.5 text-xs text-green-800">{{ __('admin.notifications.token_saved') }}</p>
            @endif

            {{-- Rendered empty every time. A saved credential is never sent back
                 to a browser, so leaving this blank means "keep the one you
                 have" — see the controller. --}}
            <input type="password" name="bale_token" dir="ltr" autocomplete="off"
                   placeholder="{{ $configured ? __('admin.notifications.token_replace') : '123456789:AbCdEf…' }}"
                   class="{{ $inputClass }} @error('bale_token') border-red-500 @enderror">

            <p class="mt-2 text-xs text-ink-500">{{ __('admin.notifications.token_hint') }}</p>
        </div>

        <div class="panel p-4">
            <p class="mb-3 text-sm font-medium text-ink-800">{{ __('admin.notifications.chat_id') }}</p>

            <input type="text" name="bale_chat_id" dir="ltr" value="{{ old('bale_chat_id', $chatId) }}"
                   class="{{ $inputClass }} @error('bale_chat_id') border-red-500 @enderror">

            <p class="mt-2 text-xs text-ink-500">{{ __('admin.notifications.chat_id_hint') }}</p>

            {{-- Filled in by the "find my chats" button below, so the id is
                 chosen from a list rather than copied out of an API response. --}}
            @if (session('chats'))
                <ul class="mt-3 space-y-1.5">
                    @foreach (session('chats') as $chat)
                        <li class="flex items-center justify-between gap-3 rounded-md border border-hairline px-3 py-2 text-sm">
                            <span class="min-w-0 truncate text-ink-800">{{ $chat['title'] }}</span>
                            <code class="ltr-run shrink-0 text-xs text-ink-500">{{ $chat['id'] }}</code>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="panel p-4">
            <label class="flex items-center gap-2.5 text-sm text-ink-700">
                <input type="checkbox" name="bale_enabled" value="1" @checked(old('bale_enabled', $enabled))
                       class="h-4 w-4 border-ink-400 text-brand-600">
                {{ __('admin.notifications.enabled') }}
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <x-button type="submit">{{ __('admin.save') }}</x-button>
        </div>
    </form>

    {{-- Three separate forms: each is a POST of its own, and none of them
         should carry the token field along with it. --}}
    <div class="mt-6 flex max-w-2xl flex-wrap items-center gap-3 border-t border-hairline pt-6">
        <form method="POST" action="{{ route('admin.notifications.chats') }}">
            @csrf
            <x-button type="submit" variant="outline" size="sm">{{ __('admin.notifications.find_chats') }}</x-button>
        </form>

        <form method="POST" action="{{ route('admin.notifications.test') }}">
            @csrf
            <x-button type="submit" variant="outline" size="sm">{{ __('admin.notifications.send_test') }}</x-button>
        </form>

        @if ($configured)
            <form method="POST" action="{{ route('admin.notifications.destroy') }}" class="ms-auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-xs text-red-600 hover:text-red-800">
                    {{ __('admin.notifications.clear_token') }}
                </button>
            </form>
        @endif
    </div>

    <p class="mt-6 max-w-2xl text-xs leading-relaxed text-ink-500">{{ __('admin.notifications.queue_note') }}</p>

</x-layouts.admin>
