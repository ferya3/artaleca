<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.plant')"
        :title="__('about.plant_title')"
        :lead="__('about.plant_lead')"
    />

    <x-stat-strip tone="light" class="border-b border-hairline" />

    {{-- The production line, step by step. Alternating sides on desktop keeps a
         seven-step sequence readable without turning into a wall of cards. --}}
    <section class="py-section">
        <div class="container-page">
            <ol class="space-y-px bg-hairline">
                @foreach (__('about.plant_steps') as $step)
                    <li class="grid gap-6 bg-surface p-6 md:grid-cols-12 md:items-start md:gap-10 md:p-8">
                        <p class="ltr-run tabular text-2xl font-bold text-clay-500 md:col-span-2 md:text-3xl">
                            {{ $step['step'] }}
                        </p>

                        <h2 class="text-lg font-bold text-ink-950 md:col-span-3">{{ $step['title'] }}</h2>

                        <p class="text-sm leading-relaxed text-ink-600 md:col-span-7">{{ $step['body'] }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="border-t border-hairline bg-surface-muted py-section">
        <div class="container-page grid gap-8 md:grid-cols-2">
            <x-media :src="site_image('media.kiln')['light']" :dark-src="site_image('media.kiln')['dark']" seed="arta-kiln" ratio="4/3" :alt="__('about.plant_title')" class="rounded-lg border border-hairline shadow-soft" sizes="(min-width: 768px) 50vw, 100vw" />
            <x-media :src="site_image('media.screening')['light']" :dark-src="site_image('media.screening')['dark']" seed="arta-screening" ratio="4/3" :alt="__('about.plant_title')" class="rounded-lg border border-hairline shadow-soft" sizes="(min-width: 768px) 50vw, 100vw" />
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
