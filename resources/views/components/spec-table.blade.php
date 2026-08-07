@props(['product'])

@php
    /*
     * Dedicated columns first (they are the filterable, comparable ones), then
     * any free-form rows an editor added. Empty values are dropped rather than
     * rendered as an em dash — a datasheet with blanks reads as unfinished.
     */
    $rows = collect([
        [__('product.grain_size'), $product->grainRange(), __('product.grain_size_unit')],
        [__('product.bulk_density'), $product->bulkDensityRange(), __('product.bulk_density_unit')],
        [__('product.particle_density'), $product->particle_density, __('product.bulk_density_unit')],
        [__('product.crushing_strength'), $product->crushing_strength, __('product.crushing_strength_unit')],
        [__('product.thermal_conductivity'), $product->thermal_conductivity, __('product.thermal_conductivity_unit')],
        [__('product.water_absorption'), $product->water_absorption_24h, __('product.water_absorption_unit')],
        [__('product.ph_value'), $product->ph_value, null],
        [__('product.fire_resistance'), $product->fire_resistance_c, __('product.fire_resistance_unit')],
    ])->filter(fn ($row) => filled($row[1]));

    $extra = collect($product->specs ?? [])
        ->map(fn ($spec) => [
            data_get($spec, 'label.'.app()->getLocale()) ?? data_get($spec, 'label.fa') ?? data_get($spec, 'label'),
            data_get($spec, 'value.'.app()->getLocale()) ?? data_get($spec, 'value.fa') ?? data_get($spec, 'value'),
            null,
        ])
        ->filter(fn ($row) => filled($row[0]) && filled($row[1]));
@endphp

@if ($rows->isNotEmpty() || $extra->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
        <table class="w-full min-w-[24rem] border-collapse text-sm">
            <caption class="sr-only">{{ __('product.technical_data') }} — {{ $product->name }}</caption>
            <tbody>
                @foreach ($rows->concat($extra) as [$label, $value, $unit])
                    <tr class="border-b border-hairline last:border-b-0">
                        <th scope="row" class="w-1/2 py-3.5 pe-4 text-start font-normal text-ink-600">{{ $label }}</th>
                        <td class="py-3.5 text-start font-semibold text-ink-950">
                            <span class="ltr-run">{{ $value }}</span>
                            @if ($unit)
                                <span class="ms-1 text-xs font-normal text-ink-500">{{ $unit }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
