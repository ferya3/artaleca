<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\Locales;
use App\Support\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ProductCategory::query()->active()->ordered()->get();
        $products = $this->query($request)->paginate(12)->withQueryString();

        seo()
            ->title(__('seo.products_title'))
            ->description(__('seo.products_description'))
            ->breadcrumbs($this->trail([
                ['label' => __('nav.products'), 'url' => null],
            ]))
            ->schema(Schema::itemList($this->listItems($products->getCollection())))
            // Filtered/paginated views are canonicalised to themselves but kept
            // out of the index beyond page 1 to avoid thin duplicate results.
            ->noindex($products->currentPage() > 1);

        return view('pages.products.index', [
            'categories' => $categories,
            'products' => $products,
            'activeCategory' => null,
        ]);
    }

    public function category(Request $request, ProductCategory $category): View
    {
        abort_unless($category->is_active, 404);

        $products = $this->query($request)
            ->where('product_category_id', $category->id)
            ->paginate(12)
            ->withQueryString();

        seo()
            ->title($category->meta_title ?? $category->name)
            ->description($category->meta_description ?? $category->summary)
            ->image($category->image)
            ->breadcrumbs($this->trail([
                ['label' => __('nav.products'), 'url' => route('products.index')],
                ['label' => (string) $category->name, 'url' => null],
            ]))
            ->schema(Schema::itemList($this->listItems($products->getCollection())))
            ->noindex($products->currentPage() > 1);

        return view('pages.products.index', [
            'categories' => ProductCategory::query()->active()->ordered()->get(),
            'products' => $products,
            'activeCategory' => $category,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'applications' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
            'downloads' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
        ]);

        $related = Product::query()
            ->active()
            ->where('product_category_id', $product->product_category_id)
            ->whereKeyNot($product->getKey())
            ->ordered()
            ->take(3)
            ->get();

        // The URL is flat, but the breadcrumb still carries the hierarchy — it
        // is what tells a visitor (and BreadcrumbList) where the grade sits.
        $trail = [['label' => __('nav.products'), 'url' => route('products.index')]];

        if ($product->category) {
            $trail[] = [
                'label' => (string) $product->category->name,
                'url' => route('products.category', ['category' => $product->category]),
            ];
        }

        $trail[] = ['label' => (string) $product->name, 'url' => null];

        seo()
            ->title($product->meta_title ?? $product->name)
            ->description($product->meta_description ?? $product->summary)
            ->image($product->primaryImage())
            ->type('product')
            ->breadcrumbs($this->trail($trail))
            ->schema(Schema::product($product));

        return view('pages.products.show', compact('product', 'related'));
    }

    /**
     * Stream a product's datasheet from private storage.
     *
     * Datasheets live on the private disk like every other document, so they
     * are served through the app rather than linked directly — an unpublished
     * product cannot leak its datasheet through a guessable URL.
     */
    public function datasheet(Product $product): StreamedResponse
    {
        abort_unless($product->is_active && filled($product->datasheet_path), 404);

        $disk = Storage::disk('documents');
        $path = ltrim($product->datasheet_path, '/');

        abort_if(str_contains($path, '..'), 404);
        abort_unless($disk->exists($path), 404);

        return $disk->download(
            $path,
            str($product->slug)->slug().'-datasheet.'.pathinfo($path, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }

    /**
     * Shared catalogue query: the same filters apply on the all-products page
     * and inside a category, so they live in one place.
     */
    private function query(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'grain' => ['nullable', 'string', 'max:20'],
            'application' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'in:position,grain_asc,grain_desc,density_asc'],
        ]);

        [$grainMin, $grainMax] = $this->parseGrain($validated['grain'] ?? null);

        return Product::query()
            ->active()
            ->with('category')
            ->search($validated['q'] ?? null)
            ->grainBetween($grainMin, $grainMax)
            ->when(
                filled($validated['application'] ?? null),
                fn ($q) => $q->whereHas('applications', fn ($a) => $a->where('slug', $validated['application']))
            )
            ->when(true, fn ($q) => match ($validated['sort'] ?? 'position') {
                'grain_asc' => $q->orderByRaw('grain_min_mm is null, grain_min_mm asc'),
                'grain_desc' => $q->orderByRaw('grain_max_mm is null, grain_max_mm desc'),
                'density_asc' => $q->orderByRaw('bulk_density_min is null, bulk_density_min asc'),
                default => $q->ordered(),
            });
    }

    /**
     * "3-10" / "0-3" / "20" → numeric bounds.
     *
     * @return array{0: ?float, 1: ?float}
     */
    private function parseGrain(?string $grain): array
    {
        if (blank($grain)) {
            return [null, null];
        }

        $parts = array_map('trim', explode('-', str_replace(['–', '—'], '-', $grain)));

        $min = is_numeric($parts[0] ?? null) ? (float) $parts[0] : null;
        $max = is_numeric($parts[1] ?? null) ? (float) $parts[1] : ($min !== null && count($parts) === 1 ? $min : null);

        return [$min, $max];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array{name:string,url:string}>
     */
    private function listItems(Collection $products): array
    {
        return $products->map(fn (Product $product) => [
            'name' => (string) $product->name,
            'url' => route('products.show', ['locale' => Locales::current(), 'product' => $product]),
        ])->values()->all();
    }
}
