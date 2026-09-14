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
    {{-- The column count comes from `.stat-strip`, which measures the space it
         has rather than the width of the window.

         It used to be `lg:grid-cols-5`, which is right for the full-width band
         on the about pages and wrong in the quote page's sidebar: five columns
         in a 420px aside is 83px each, and the annual capacity — a six-figure
         number — was being clipped to its last three digits. It read as "۰۰۰".
         A viewport breakpoint cannot know that; `auto-fit` can. --}}
    <dl {{ $attributes->merge([
        'class' => 'stat-strip hidden gap-px overflow-hidden rounded-lg md:grid '
            .($dark ? 'bg-night-line' : 'bg-hairline'),
    ]) }}>
        @foreach ($items as $item)
            <div class="{{ $dark ? 'bg-night-950' : 'bg-surface' }} px-5 py-7">
                {{-- `flex-wrap`, so a narrow track puts the unit under the number
                     instead of pushing it past the edge of the cell. This is
                     what actually clipped the capacity figure: a no-wrap flex
                     row has no way to give, so it simply overflowed. --}}
                <dd class="tabular flex flex-wrap items-baseline gap-x-1.5">
                    {{-- One size, where this was `text-2xl lg:text-3xl`. The `lg:`
                         was the same mistake as the column count: it grew the
                         number when the *window* was wide, and the cell it has
                         to fit in is sized by its container. At 30px a
                         six-figure figure needs 130px and the narrowest cell
                         gives 115. --}}
                    <span class="ltr-run text-2xl font-bold {{ $dark ? 'text-white' : 'text-ink-950' }}">{{ $item['value'] }}</span>
                    <span class="text-xs {{ $dark ? 'text-night-400' : 'text-ink-500' }}">{{ $item['unit'] }}</span>
                </dd>
                <dt class="mt-2 text-xs leading-snug {{ $dark ? 'text-night-400' : 'text-ink-500' }}">{{ $item['label'] }}</dt>
            </div>
        @endforeach
    </dl>
@endif
