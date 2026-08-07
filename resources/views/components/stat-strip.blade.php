@props(['tone' => 'dark'])

@php
    use App\Support\Figures;

    // Values come from the settings table (editable in the admin), falling back
    // to config/site.php before the settings row exists.
    $items = Figures::strip();
    $dark = $tone === 'dark';
@endphp

@if ($items !== [])
    {{-- The proof strip: the numbers that answer "is this company big enough for
         my project?" before a visitor reads a single paragraph. --}}
    <dl {{ $attributes->merge([
        'class' => 'grid grid-cols-2 gap-px md:grid-cols-3 lg:grid-cols-5 '
            .($dark ? 'bg-hairline-dark' : 'bg-hairline'),
    ]) }}>
        @foreach ($items as $item)
            <div class="{{ $dark ? 'bg-ink-950' : 'bg-white' }} px-5 py-7">
                <dd class="tabular flex items-baseline gap-1.5">
                    <span class="ltr-run text-2xl font-bold {{ $dark ? 'text-white' : 'text-ink-950' }} lg:text-3xl">{{ $item['value'] }}</span>
                    <span class="text-xs {{ $dark ? 'text-ink-400' : 'text-ink-500' }}">{{ $item['unit'] }}</span>
                </dd>
                <dt class="mt-2 text-xs leading-snug {{ $dark ? 'text-ink-400' : 'text-ink-500' }}">{{ $item['label'] }}</dt>
            </div>
        @endforeach
    </dl>
@endif
