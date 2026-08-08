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
    {{-- Hidden below `md`. On a phone the five figures become five stacked rows
         that push everything else off the screen, and a visitor there is
         scanning for a product or a phone number rather than reading company
         statistics. The numbers stay in the DOM nowhere — `hidden` on the grid
         keeps them out of the accessibility tree too, so a screen-reader user
         on a phone is not read a table they cannot see. --}}
    <dl {{ $attributes->merge([
        'class' => 'hidden grid-cols-2 gap-px overflow-hidden rounded-lg md:grid md:grid-cols-3 lg:grid-cols-5 '
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
