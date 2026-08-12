<x-layouts.app>

    <x-page-header
        :eyebrow="content('nav.applications')"
        :title="content('applications.title')"
        :lead="content('applications.intro')"
    />

    <section class="py-section">
        <div class="container-page">
            @if ($applications->isEmpty())
                <x-empty-state :message="content('applications.empty')" />
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
