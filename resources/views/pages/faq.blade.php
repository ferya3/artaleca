<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.faq')"
        :title="content('faq.title')"
        :lead="content('faq.intro')"
    />

    <section class="py-section">
        <div class="container-page max-w-3xl">
            @if ($faqs->isEmpty())
                <x-empty-state :message="content('common.no_results')" />
            @else
                {{-- One flat list in the editor's own order, as a native
                     <details> accordion: expandable, keyboard accessible and
                     findable by in-page search, with no JavaScript involved. --}}
                <div class="panel divide-y divide-hairline overflow-hidden">
                    @foreach ($faqs as $faq)
                        <details class="group">
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-6 px-5 py-5 text-start transition-colors hover:bg-ink-50 [&::-webkit-details-marker]:hidden">
                                <h2 class="text-base font-semibold text-ink-900 group-open:text-brand-600">{{ $faq->question }}</h2>
                                <span class="mt-1.5 shrink-0 text-ink-400" aria-hidden="true">
                                    <svg viewBox="0 0 12 12" class="h-3 w-3 transition-transform group-open:rotate-45">
                                        <path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </summary>
                            <div class="prose-industrial px-5 pb-6 text-sm">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            @endif

            <div class="mt-14 panel-muted p-6 text-center">
                <p class="text-sm font-semibold text-ink-900">{{ content('faq.still_have_questions') }}</p>
                <p class="mt-2 text-sm text-ink-600">{{ content('faq.contact_expert') }}</p>
                <x-button :href="route('contact')" variant="outline" size="sm" class="mt-5">
                    {{ content('nav.contact') }}
                </x-button>
            </div>
        </div>
    </section>

</x-layouts.app>
