<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.quality')"
        :title="__('about.quality_title')"
        :lead="__('about.quality_lead')"
    />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            {{-- ── Test schedule ─────────────────────────────────────── --}}
            <div class="min-w-0 lg:col-span-7">
                <h2 class="text-xl font-bold text-ink-950">{{ __('about.quality_tests_title') }}</h2>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[34rem] border-collapse text-sm">
                        <thead>
                            <tr class="border-b-2 border-ink-950">
                                <th scope="col" class="py-3 pe-4 text-start text-xs uppercase tracking-wider text-ink-500">
                                    {{ __('about.quality_tests_title') }}
                                </th>
                                <th scope="col" class="py-3 pe-4 text-start text-xs uppercase tracking-wider text-ink-500">
                                    {{ __('common.working_hours') }}
                                </th>
                                <th scope="col" class="py-3 text-start text-xs uppercase tracking-wider text-ink-500">
                                    {{ __('product.standards') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (__('about.quality_tests') as $test)
                                <tr class="border-b border-hairline">
                                    <td class="py-3.5 pe-4 font-medium text-ink-900">{{ $test['name'] }}</td>
                                    <td class="py-3.5 pe-4 text-ink-600">{{ $test['frequency'] }}</td>
                                    <td class="ltr-run py-3.5 text-xs text-ink-500">{{ $test['standard'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ── Sampling to certificate ───────────────────────────── --}}
            <div class="min-w-0 lg:col-span-5">
                <div class="panel-muted p-6">
                    <h2 class="text-lg font-bold text-ink-950">{{ __('about.quality_process_title') }}</h2>

                    <ol class="mt-5 space-y-4">
                        @foreach (__('about.quality_process') as $index => $step)
                            <li class="flex gap-4">
                                <span class="tabular ltr-run shrink-0 text-xs font-bold text-brand-600">
                                    {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <span class="text-sm leading-relaxed text-ink-700">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>

    @if ($certificates->isNotEmpty())
        <section class="border-t border-hairline py-section">
            <div class="container-page">
                <h2 class="text-2xl font-bold text-ink-950">{{ __('common.certificates') }}</h2>

                <ul class="mt-10 grid gap-px bg-hairline sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($certificates as $certificate)
                        <li class="bg-surface p-6">
                            <p class="text-sm font-semibold text-ink-950">{{ $certificate->title }}</p>
                            @if (filled($certificate->issuer))
                                <p class="mt-1.5 text-xs text-ink-500">{{ $certificate->issuer }}</p>
                            @endif
                            <p class="ltr-run tabular mt-3 flex gap-3 text-xs text-ink-400">
                                @if ($certificate->reference)<span>{{ $certificate->reference }}</span>@endif
                                @if ($certificate->year)<span>{{ $certificate->year }}</span>@endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <x-cta-band />

</x-layouts.app>
