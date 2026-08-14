<x-layouts.admin :title="__('admin.settings')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.settings') }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">{{ __('admin.settings_intro') }}</p>

    {{-- One card per area, named for what an editor came looking for. The old
         screen was a single scroll of forty fields, and the plant address —
         the first thing anyone needs to correct — was somewhere in the middle
         of it with nothing but an eyebrow to mark the section. --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($groups as $item)
            <a href="{{ route('admin.settings.edit', $item['group']) }}"
               class="panel panel-interactive flex flex-col p-5">
                <p class="text-sm font-semibold text-ink-950">{{ $item['label'] }}</p>
                <p class="mt-2 flex-1 text-xs leading-relaxed text-ink-500">{{ $item['summary'] }}</p>

                <p class="mt-4 text-xs text-ink-500">
                    <span class="tabular font-medium text-ink-800">{{ $item['total'] }}</span>
                    {{ __('admin.settings_fields_count') }}

                    @if ($item['filled'] > 0)
                        <span class="ms-2 inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-[0.6875rem] font-medium text-brand-700">
                            {{ __('admin.content_edited', ['count' => $item['filled']]) }}
                        </span>
                    @endif
                </p>
            </a>
        @endforeach
    </div>

</x-layouts.admin>
