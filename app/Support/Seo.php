<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Per-request SEO state, resolved as a singleton and shared with every view.
 *
 * Controllers describe the page fluently:
 *
 *     seo()->title($product->name)
 *          ->description($product->summary)
 *          ->image($product->hero_image)
 *          ->type('product')
 *          ->schema(Schema::product($product));
 *
 * and the layout renders <title>, meta, Open Graph, canonical, hreflang and
 * JSON-LD from it. Nothing SEO-related is written inline in a template.
 */
final class Seo
{
    private ?string $title = null;

    private ?string $description = null;

    private ?string $image = null;

    private string $type = 'website';

    private ?string $canonical = null;

    private bool $noindex = false;

    /** @var list<array<string, mixed>> */
    private array $schemas = [];

    /** @var list<array{label:string,url:?string}> */
    private array $breadcrumbs = [];

    public function title(?string $title): self
    {
        $this->title = $title === null ? null : trim(strip_tags($title));

        return $this;
    }

    public function description(?string $description): self
    {
        if ($description === null) {
            $this->description = null;

            return $this;
        }

        // Search engines truncate around 160 characters; do it deliberately
        // rather than letting them cut mid-word.
        $this->description = Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags($description)) ?? ''), 158);

        return $this;
    }

    public function image(?string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function canonical(?string $url): self
    {
        $this->canonical = $url;

        return $this;
    }

    public function noindex(bool $noindex = true): self
    {
        $this->noindex = $noindex;

        return $this;
    }

    /**
     * Add a JSON-LD node.
     *
     * Nullable and empty by design: builders like `Schema::itemList()` return
     * null when there is nothing to describe (an empty catalogue page, a FAQ
     * with no entries), and callers should not each have to guard for that.
     *
     * @param  array<string, mixed>|null  $schema
     */
    public function schema(?array $schema): self
    {
        if (filled($schema)) {
            $this->schemas[] = $schema;
        }

        return $this;
    }

    /** @param  list<array{label:string,url:?string}>  $crumbs */
    public function breadcrumbs(array $crumbs): self
    {
        $this->breadcrumbs = $crumbs;

        return $this;
    }

    /** @return list<array{label:string,url:?string}> */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    public function getTitle(): string
    {
        $brand = config('site.company.brand');

        if (blank($this->title)) {
            return $brand.' — '.content('seo.brand_tagline');
        }

        // Avoid "ARTA LECA | ARTA LECA" on the homepage.
        return Str::contains($this->title, $brand)
            ? $this->title
            : $this->title.' | '.$brand;
    }

    public function getDescription(): string
    {
        return $this->description ?? content('seo.default_description');
    }

    public function getImage(): string
    {
        $image = $this->image ?? config('site.seo.default_og_image');

        return Str::startsWith($image, ['http://', 'https://'])
            ? $image
            : url($image);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCanonical(): string
    {
        return $this->canonical ?? Url::canonical();
    }

    public function isNoindex(): bool
    {
        return $this->noindex;
    }

    /**
     * Every JSON-LD block for the page, wrapped in a single @graph so the page
     * emits one <script type="application/ld+json"> instead of several.
     *
     * @return array<string, mixed>|null
     */
    public function schemaGraph(): ?array
    {
        $schemas = $this->schemas;

        if ($this->breadcrumbs !== []) {
            $schemas[] = Schema::breadcrumbs($this->breadcrumbs);
        }

        $schemas = array_values(array_filter($schemas));

        if ($schemas === []) {
            return null;
        }

        return ['@context' => 'https://schema.org', '@graph' => $schemas];
    }
}
