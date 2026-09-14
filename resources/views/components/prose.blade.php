@props(['text', 'long' => false])

{{-- One element for every body the panel holds: articles, projects, uses,
     products, pages and the FAQ answers. They all used to build their own
     `nl2br(e(…))` div, which is how eight places ended up agreeing by accident
     rather than by design.

     `long` is for a body somebody actually sits and reads, where a slightly
     larger size and a looser line make the difference over a thousand words.
     A product description or an FAQ answer is read standing up, next to a spec
     table, and keeps the compact setting.

     One thing to know before adding `long` somewhere new: it hands the measure
     back to its wrapper (see `.prose-long`), so it belongs inside a column that
     has one — `.article-measure`, or a grid track. Drop it into a full-width
     container and the lines run the whole way across. --}}
@if (\App\Support\Prose::filled($text))
    <div {{ $attributes->merge(['class' => 'prose-industrial'.($long ? ' prose-long' : '')]) }}>
        {!! \App\Support\Prose::toHtml($text) !!}
    </div>
@endif
