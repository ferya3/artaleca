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
                <div class="card-grid">
                    @foreach ($applications as $application)
                        <x-application-card :application="$application" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <x-cta-band />

</x-layouts.app>
