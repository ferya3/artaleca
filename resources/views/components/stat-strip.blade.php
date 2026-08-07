@props(['tone' => 'dark'])

@php
    $figures = config('site.figures');

    $items = [
        [number_format($figures['annual_capacity_m3']), __('common.figures.capacity_unit'), __('common.figures.capacity')],
        [number_format($figures['plant_area_m2']), __('common.figures.area_unit'), __('common.figures.area')],
        [(string) $figures['kiln_lines'], __('common.figures.kilns_unit'), __('common.figures.kilns')],
        [(string) $figures['export_countries'], __('common.figures.countries_unit'), __('common.figures.countries')],
        [(string) $figures['employees'], __('common.figures.employees_unit'), __('common.figures.employees')],
    ];

    $dark = $tone === 'dark';
@endphp

{{-- The proof strip: five numbers that answer "is this company big enough for
     my project?" before a visitor reads a single paragraph. --}}
<dl {{ $attributes->merge([
    'class' => 'grid grid-cols-2 gap-px md:grid-cols-3 lg:grid-cols-5 '
        .($dark ? 'bg-hairline-dark' : 'bg-hairline'),
]) }}>
    @foreach ($items as [$value, $unit, $label])
        <div class="{{ $dark ? 'bg-ink-950' : 'bg-white' }} px-5 py-7">
            <dd class="tabular flex items-baseline gap-1.5">
                <span class="ltr-run text-2xl font-bold {{ $dark ? 'text-white' : 'text-ink-950' }} lg:text-3xl">{{ $value }}</span>
                <span class="text-xs {{ $dark ? 'text-ink-400' : 'text-ink-500' }}">{{ $unit }}</span>
            </dd>
            <dt class="mt-2 text-xs leading-snug {{ $dark ? 'text-ink-400' : 'text-ink-500' }}">{{ $label }}</dt>
        </div>
    @endforeach
</dl>
