<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Choosing what appears on the home page, and in what order.
 *
 * The machinery was all there — `is_featured` picks the set, `position` orders
 * it, both editable — and it was still not usable, because neither field said
 * what it did and the list screen showed position without showing which
 * records were featured. An editor ticking the box saw four of seven products
 * reach the home page with no way of telling which four, or why.
 *
 * So the guard here is not on the query. It is on the two things that make the
 * query legible from the panel: the featured column on the list, and hints that
 * say how many places the row has and what fills them.
 */
class FeaturedOrderTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $position, bool $featured): Product
    {
        return $this->makeProduct([
            'slug' => 'grade-'.$position,
            'sku' => 'ALS-'.$position,
            'name' => ['fa' => 'گرید '.$position, 'en' => 'Grade '.$position, 'ar' => 'درجة '.$position],
            'position' => $position,
            'is_featured' => $featured,
        ]);
    }

    /** The home row is the featured set, cut to its four places by position. */
    public function test_position_decides_which_featured_products_reach_the_home_page(): void
    {
        // Six featured products for four places, in a deliberately unhelpful
        // creation order so anything falling back to `id` fails here.
        foreach ([60, 10, 50, 20, 40, 30] as $position) {
            $this->product($position, true);
        }

        $html = $this->get('/fa')->assertOk()->getContent();

        foreach ([10, 20, 30, 40] as $shown) {
            $this->assertStringContainsString('گرید '.\App\Support\Digits::text((string) $shown), $html);
        }

        foreach ([50, 60] as $hidden) {
            $this->assertStringNotContainsString('گرید '.\App\Support\Digits::text((string) $hidden), $html);
        }
    }

    /** And the ones it left out are still on the catalogue page. */
    public function test_nothing_featured_or_not_is_missing_from_the_catalogue(): void
    {
        foreach ([10, 20, 30, 40, 50, 60] as $position) {
            $this->product($position, $position <= 40);
        }

        $html = $this->get('/fa/products')->assertOk()->getContent();

        foreach ([10, 20, 30, 40, 50, 60] as $position) {
            $this->assertStringContainsString(
                'گرید '.\App\Support\Digits::text((string) $position),
                $html,
                "Grade at position {$position} is missing from the catalogue.",
            );
        }
    }

    /**
     * The list screen is sorted by position, so a featured column turns it into
     * the home-page row in the order it will appear — readable at a glance
     * instead of by opening every record in turn.
     */
    public function test_the_list_screens_show_which_records_are_featured(): void
    {
        $this->product(10, true);

        // The table — headings and all — only renders where there is a row to
        // put in it, so each of the three needs one.
        \App\Models\Project::create([
            'slug' => 'a-project',
            'title' => ['fa' => 'پروژه', 'en' => 'Project', 'ar' => 'مشروع'],
            'is_featured' => true,
            'is_active' => true,
        ]);

        \App\Models\Post::create([
            'slug' => 'a-post',
            'type' => 'news',
            'title' => ['fa' => 'خبر', 'en' => 'Post', 'ar' => 'خبر'],
            'published_at' => now(),
            'is_featured' => true,
            'is_active' => true,
        ]);

        $admin = $this->makeAdmin();

        foreach (['products', 'projects', 'posts'] as $resource) {
            $html = $this->actingAs($admin)
                ->get('/admin/'.$resource)
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(
                __('admin.featured'),
                $html,
                "The {$resource} list does not say which records are featured.",
            );
        }
    }

    /**
     * A flag in a column of its own needs its own word. The badge reused the
     * active/inactive wording, which put "active" under a heading that says
     * "featured" — two different things wearing one label.
     */
    public function test_the_featured_badge_carries_its_own_word(): void
    {
        $this->product(10, true);

        $html = $this->actingAs($this->makeAdmin())->get('/admin/products')->assertOk()->getContent();

        // Twice: the column heading, and the badge on the one featured row.
        $this->assertSame(
            2,
            substr_count($html, __('admin.featured')),
            'The featured row should be badged with the column\'s own word.',
        );
    }

    /** Both fields say what they do, or the order is guesswork. */
    public function test_the_fields_explain_what_they_control(): void
    {
        $product = $this->product(10, true);

        $html = $this->actingAs($this->makeAdmin())
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('admin.fields.position_hint'), $html);
        $this->assertStringContainsString(__('admin.fields.featured_product_hint'), $html);
    }
}
