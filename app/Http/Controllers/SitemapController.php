<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Support\Locales;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * One sitemap for all three languages.
 *
 * Each URL is emitted once per locale, and every entry carries the full set of
 * xhtml:link alternates (including a self-referencing one and x-default), which
 * is what Google requires to treat the three versions as one page in three
 * languages rather than as duplicates.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHours(6), fn () => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /*/search',
            'Disallow: /*/quote',
            'Disallow: /*?*sort=',
            'Allow: /',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        // Keep staging and preview environments out of the index entirely.
        if (! app()->environment('production')) {
            $lines = ['User-agent: *', 'Disallow: /'];
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    private function build(): string
    {
        $entries = [];

        foreach ($this->staticRoutes() as $name => [$priority, $changefreq]) {
            $entries[] = $this->entry(fn (string $l) => route($name, ['locale' => $l]), null, $priority, $changefreq);
        }

        foreach (ProductCategory::query()->active()->ordered()->get() as $category) {
            $entries[] = $this->entry(
                fn (string $l) => route('products.category', ['locale' => $l, 'category' => $category]),
                $category->updated_at, 0.8, 'weekly',
            );
        }

        foreach (Product::query()->active()->ordered()->get() as $product) {
            $entries[] = $this->entry(
                fn (string $l) => route('products.show', ['locale' => $l, 'product' => $product]),
                $product->updated_at, 0.9, 'weekly',
            );
        }

        foreach (Application::query()->active()->ordered()->get() as $application) {
            $entries[] = $this->entry(
                fn (string $l) => route('applications.show', ['locale' => $l, 'application' => $application]),
                $application->updated_at, 0.8, 'monthly',
            );
        }

        foreach (Project::query()->active()->recentFirst()->get() as $project) {
            $entries[] = $this->entry(
                fn (string $l) => route('projects.show', ['locale' => $l, 'project' => $project]),
                $project->updated_at, 0.7, 'monthly',
            );
        }

        foreach (Post::query()->published()->latestFirst()->get() as $post) {
            $entries[] = $this->entry(
                fn (string $l) => route('articles.show', ['locale' => $l, 'post' => $post]),
                $post->updated_at, 0.6, 'monthly',
            );
        }

        foreach (Page::query()->active()->ordered()->get() as $page) {
            $entries[] = $this->entry(
                fn (string $l) => route('pages.show', ['locale' => $l, 'page' => $page]),
                $page->updated_at, 0.5, 'monthly',
            );
        }

        return $this->document(array_merge(...$entries));
    }

    /** @return array<string, array{0: float, 1: string}> */
    private function staticRoutes(): array
    {
        return [
            'home' => [1.0, 'weekly'],
            'about' => [0.8, 'monthly'],
            'about.quality' => [0.6, 'yearly'],
            'about.plant' => [0.6, 'yearly'],
            'products.index' => [0.9, 'weekly'],
            'applications.index' => [0.8, 'monthly'],
            'projects.index' => [0.8, 'monthly'],
            'articles.index' => [0.7, 'weekly'],
            'downloads.index' => [0.7, 'monthly'],
            'gallery' => [0.5, 'monthly'],
            'faq' => [0.5, 'monthly'],
            'contact' => [0.7, 'yearly'],
        ];
    }

    /**
     * One <url> block per locale for a single logical page, each carrying the
     * complete alternate set.
     *
     * @param  callable(string): string  $url
     * @return list<string>
     */
    private function entry(callable $url, ?CarbonInterface $lastmod, float $priority, string $changefreq): array
    {
        $alternates = '';

        foreach (Locales::codes() as $code) {
            $alternates .= sprintf(
                '<xhtml:link rel="alternate" hreflang="%s" href="%s"/>',
                Locales::hreflang($code),
                htmlspecialchars($url($code), ENT_XML1),
            );
        }

        $alternates .= sprintf(
            '<xhtml:link rel="alternate" hreflang="x-default" href="%s"/>',
            htmlspecialchars($url(Locales::default()), ENT_XML1),
        );

        $blocks = [];

        foreach (Locales::codes() as $code) {
            $blocks[] = sprintf(
                '<url><loc>%s</loc>%s%s<changefreq>%s</changefreq><priority>%s</priority></url>',
                htmlspecialchars($url($code), ENT_XML1),
                $lastmod ? '<lastmod>'.$lastmod->toAtomString().'</lastmod>' : '',
                $alternates,
                $changefreq,
                number_format($priority, 1),
            );
        }

        return $blocks;
    }

    /** @param  list<string>  $entries */
    private function document(array $entries): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
            .'xmlns:xhtml="http://www.w3.org/1999/xhtml">'."\n"
            .implode("\n", $entries)."\n"
            .'</urlset>'."\n";
    }
}
