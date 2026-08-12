<x-layouts.admin :title="__('admin.site_content')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.site_content') }}</h1>
    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-ink-600">{{ __('admin.site_content_intro') }}</p>

    {{-- One card per area rather than one enormous form: there are some 370
         strings across the site and three languages each. The counter is the
         useful thing on this screen — it says at a glance which areas have been
         rewritten and which are still running on the shipped text. --}}
    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($groups as $item)
            <a href="{{ route('admin.content.edit', $item['group']) }}"
               class="panel panel-interactive flex flex-col p-5">
                <p class="text-sm font-semibold text-ink-950">{{ $item['label'] }}</p>
                <code class="ltr-run mt-1 text-[0.625rem] text-ink-400">{{ $item['group'] }}</code>

                <p class="mt-4 text-xs text-ink-500">
                    <span class="tabular font-medium text-ink-800">{{ $item['total'] }}</span>
                    {{ __('admin.content_strings') }}

                    @if ($item['overridden'] > 0)
                        <span class="ms-2 inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-[0.6875rem] font-medium text-brand-700">
                            {{ __('admin.content_edited', ['count' => $item['overridden']]) }}
                        </span>
                    @endif
                </p>
            </a>
        @endforeach
    </div>

</x-layouts.admin>
