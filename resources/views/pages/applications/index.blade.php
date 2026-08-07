<x-layouts.app>

    <x-page-header
        :eyebrow="__('nav.applications')"
        :title="__('applications.title')"
        :lead="__('applications.intro')"
    />

    <section class="py-section">
        <div class="container-page">
            @if ($applications->isEmpty())
                <x-empty-state :message="__('applications.empty')" />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($applications as $application)
                        <x-application-card :application="$application" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
