<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Faq;
use App\Models\Post;
use App\Models\Product;
use App\Models\Project;
use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * schema.org JSON-LD builders.
 *
 * Structured data is what turns a catalogue page into a rich result, so each
 * public template gets a node describing exactly what it is — Organization on
 * every page, plus Product / Article / FAQPage / BreadcrumbList where relevant.
 */
final class Schema
{
    public static function organization(): array
    {
        $contact = config('site.contact');
        $locale = Locales::current();

        return array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('site.company.legal_name'),
            'alternateName' => config('site.company.brand'),
            'url' => url('/'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => url('/images/brand/logo-mark.svg'),
            ],
            'foundingDate' => (string) config('site.company.founded'),
            'description' => content('seo.default_description'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $contact['hq']['lines'][$locale] ?? $contact['hq']['lines']['en'],
                'postalCode' => $contact['hq']['postal_code'],
                'addressCountry' => 'IR',
            ],
            'contactPoint' => [
                [
                    '@type' => 'ContactPoint',
                    'contactType' => 'sales',
                    'telephone' => $contact['sales_phone'],
                    'email' => $contact['sales_email'],
                    'availableLanguage' => ['fa', 'en', 'ar'],
                ],
            ],
            'sameAs' => array_values(array_filter(config('site.social'))),
        ]);
    }

    /**
     * LocalBusiness for the plant.
     *
     * Only emitted when an administrator has switched it on and supplied a
     * street and locality — publishing a LocalBusiness node with placeholder
     * data is worse than publishing none, because search engines will show it.
     */
    public static function localBusiness(): ?array
    {
        if (! Setting::get('business.enabled')) {
            return null;
        }

        $street = Setting::get('business.street');
        $locality = Setting::get('business.locality');

        if (blank($street) || blank($locality)) {
            return null;
        }

        $latitude = Setting::get('business.latitude');
        $longitude = Setting::get('business.longitude');

        return array_filter([
            '@type' => 'LocalBusiness',
            '@id' => url('/').'#plant',
            'name' => config('site.company.legal_name'),
            'url' => url('/'),
            'image' => url(config('site.seo.default_og_image')),
            'telephone' => config('site.contact.sales_phone'),
            'email' => config('site.contact.sales_email'),
            'parentOrganization' => ['@id' => url('/').'#organization'],
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $street,
                'addressLocality' => $locality,
                'postalCode' => Setting::get('business.postal_code'),
                'addressCountry' => 'IR',
            ]),
            'geo' => filled($latitude) && filled($longitude) ? [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
            ] : null,
            'openingHours' => Setting::get('business.opening_hours') ?: null,
        ]);
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => config('site.company.brand'),
            'inLanguage' => Locales::hreflang(),
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => route('search', ['locale' => Locales::current()]).'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function product(Product $product): array
    {
        $properties = collect([
            ['name' => content('product.grain_size'), 'value' => $product->grainRange(), 'unit' => 'MMT'],
            ['name' => content('product.bulk_density'), 'value' => $product->bulkDensityRange(), 'unit' => 'KGM'],
            ['name' => content('product.crushing_strength'), 'value' => $product->crushing_strength, 'unit' => 'MPA'],
            ['name' => content('product.thermal_conductivity'), 'value' => $product->thermal_conductivity, 'unit' => null],
            ['name' => content('product.water_absorption'), 'value' => $product->water_absorption_24h, 'unit' => 'P1'],
        ])
            ->filter(fn (array $row) => filled($row['value']))
            ->map(fn (array $row) => array_filter([
                '@type' => 'PropertyValue',
                'name' => $row['name'],
                'value' => (string) $row['value'],
                'unitCode' => $row['unit'],
            ]))
            ->values()
            ->all();

        return array_filter([
            '@type' => 'Product',
            '@id' => route('products.show', ['locale' => Locales::current(), 'product' => $product]).'#product',
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->summary ?? $product->tagline,
            'image' => $product->primaryImage() ? url($product->primaryImage()) : null,
            'category' => $product->category?->name,
            'brand' => ['@type' => 'Brand', 'name' => config('site.company.brand')],
            'manufacturer' => ['@id' => url('/').'#organization'],
            'additionalProperty' => $properties ?: null,
        ]);
    }

    public static function article(Post $post): array
    {
        return array_filter([
            '@type' => $post->type === 'news' ? 'NewsArticle' : 'Article',
            'headline' => $post->title,
            'description' => $post->excerpt,
            'image' => $post->cover_image ? url($post->cover_image) : null,
            'datePublished' => $post->published_at?->toIso8601String(),
            'dateModified' => $post->updated_at?->toIso8601String(),
            'inLanguage' => Locales::hreflang(),
            'author' => [
                '@type' => 'Organization',
                'name' => config('site.company.brand'),
            ],
            'publisher' => ['@id' => url('/').'#organization'],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('articles.show', ['locale' => Locales::current(), 'post' => $post]),
            ],
        ]);
    }

    public static function project(Project $project): array
    {
        return array_filter([
            '@type' => 'CreativeWork',
            'name' => $project->title,
            'description' => $project->summary,
            'image' => $project->cover_image ? url($project->cover_image) : null,
            'dateCreated' => $project->year ? (string) $project->year : null,
            'locationCreated' => $project->location
                ? ['@type' => 'Place', 'name' => $project->location]
                : null,
            'creator' => ['@id' => url('/').'#organization'],
        ]);
    }

    /** @param  Collection<int, Faq>  $faqs */
    public static function faqPage(Collection $faqs): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (Faq $faq) => [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags((string) $faq->answer),
                ],
            ])->values()->all(),
        ];
    }

    /**
     * @param  list<array{label:string,url:?string}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): ?array
    {
        if (count($crumbs) < 2) {
            return null;
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_map(
                fn (int $index, array $crumb) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $crumb['label'],
                    'item' => $crumb['url'] ?? null,
                ]),
                array_keys($crumbs),
                $crumbs,
            )),
        ];
    }

    /**
     * ItemList for catalogue and index pages — tells search engines the page is
     * a listing and in what order, instead of leaving it to guess.
     *
     * @param  list<array{name:string,url:string}>  $items
     */
    public static function itemList(array $items): ?array
    {
        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'ItemList',
            'numberOfItems' => count($items),
            'itemListElement' => array_values(array_map(
                fn (int $index, array $item) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'url' => $item['url'],
                ],
                array_keys($items),
                $items,
            )),
        ];
    }
}
