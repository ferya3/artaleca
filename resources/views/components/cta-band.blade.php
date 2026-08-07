@props([
    'title' => null,
    'body' => null,
])

{{-- The site's single conversion band, repeated at the foot of every content
     page. One dark surface, one primary action, one secondary — deliberately
     the only place on a page where two CTAs sit together. --}}
<section class="relative overflow-hidden bg-ink-950 text-white">
    <div class="hairline-grid absolute inset-0" aria-hidden="true"></div>

    <div class="container-page relative py-16 md:py-20">
        <div class="flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-xl">
                <h2 class="text-2xl font-bold md:text-3xl">{{ $title ?? __('common.cta_quote_title') }}</h2>
                <p class="mt-4 text-base leading-relaxed text-ink-300">{{ $body ?? __('common.cta_quote_body') }}</p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row lg:shrink-0">
                <x-button :href="route('quote')" variant="accent" size="lg">
                    {{ __('common.cta_quote_action') }}
                </x-button>

                <x-button :href="route('contact')" variant="inverse" size="lg">
                    {{ __('common.cta_contact_action') }}
                </x-button>
            </div>
        </div>
    </div>
</section>
