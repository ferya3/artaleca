<x-layouts.app>

    <x-page-header :title="__('legal.privacy_title')" :lead="__('legal.privacy_intro')" />

    <section class="py-section">
        <div class="container-page max-w-3xl">
            <div class="space-y-10">
                @foreach (__('legal.privacy_sections') as $section)
                    <div>
                        <h2 class="text-lg font-bold text-ink-950">{{ $section['title'] }}</h2>
                        <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $section['body'] }}</p>
                    </div>
                @endforeach
            </div>

            <p class="mt-12 border-t border-hairline pt-6 text-xs text-ink-500">
                {{ __('legal.last_updated', ['date' => now()->isoFormat('D MMMM Y')]) }}
            </p>
        </div>
    </section>

</x-layouts.app>
