<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\GalleryImage;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Redirect;
use App\Models\Setting;
use App\Support\Figures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    // ── Editor pages ────────────────────────────────────────────────────

    private function makePage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'careers',
            'title' => ['fa' => 'فرصت‌های شغلی', 'en' => 'Careers', 'ar' => 'الوظائف'],
            'lead' => ['fa' => 'خلاصه', 'en' => 'Lead', 'ar' => 'ملخص'],
            'body' => ['fa' => 'متن صفحه', 'en' => 'Page body', 'ar' => 'نص الصفحة'],
            'is_active' => true,
        ], $overrides));
    }

    public function test_an_editor_page_is_served_in_every_locale(): void
    {
        $this->makePage();

        $this->get('/fa/careers')->assertOk()->assertSee('فرصت‌های شغلی');
        $this->get('/en/careers')->assertOk()->assertSee('Careers');
        $this->get('/ar/careers')->assertOk()->assertSee('الوظائف');
    }

    public function test_an_unpublished_page_returns_not_found(): void
    {
        $this->makePage(['is_active' => false]);

        $this->get('/fa/careers')->assertNotFound();
    }

    /**
     * The catch-all is registered last precisely so it cannot swallow a real
     * section; this is the regression test for that ordering.
     */
    public function test_a_page_slug_cannot_shadow_a_real_section(): void
    {
        $this->makeProduct();
        $this->makePage(['slug' => 'products', 'title' => ['fa' => 'جعلی', 'en' => 'Fake', 'ar' => 'مزيف']]);

        $this->get('/fa/products')->assertOk()->assertDontSee('جعلی');
    }

    public function test_pages_appear_in_the_sitemap(): void
    {
        $this->makePage();

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.url('/fa/careers').'</loc>', false)
            ->assertSee('<loc>'.url('/ar/careers').'</loc>', false);
    }

    // ── Key numbers ─────────────────────────────────────────────────────

    /** The brief requires these to be editable without a deploy. */
    public function test_key_numbers_come_from_the_database(): void
    {
        Setting::put('figures.annual_capacity_m3', 987654, 'figures', translatable: false);

        $this->assertSame(987654, Figures::value('annual_capacity_m3'));
        $this->get('/fa')->assertSee('987,654');
    }

    public function test_a_figure_set_to_zero_is_dropped_from_the_strip(): void
    {
        Setting::put('figures.kiln_lines', 0, 'figures', translatable: false);

        $labels = array_column(Figures::strip(), 'label');

        $this->assertNotContains(__('common.figures.kilns'), $labels);
    }

    public function test_figures_fall_back_to_config_before_any_setting_exists(): void
    {
        $this->assertSame(config('site.figures.employees'), Figures::value('employees'));
    }

    // ── Partners ────────────────────────────────────────────────────────

    /**
     * Partners no longer surface on the home page — that section became the
     * pinned showcase image. They are still editor-managed, and a partner with
     * `kind = representative` still has a public page of its own, so the guard
     * that matters is that the two do not leak into each other.
     */
    public function test_only_representatives_reach_the_public_partner_page(): void
    {
        Partner::create([
            'slug' => 'acme-association',
            'name' => ['fa' => 'انجمن نمونه', 'en' => 'Acme Association', 'ar' => 'جمعية'],
            'kind' => 'association',
            'is_active' => true,
        ]);

        $this->get('/fa')->assertOk()->assertDontSee('انجمن نمونه');
        $this->get('/fa/representatives')->assertOk()->assertDontSee('انجمن نمونه');
    }

    // ── Gallery ─────────────────────────────────────────────────────────

    public function test_the_gallery_lists_images_and_filters_by_album(): void
    {
        GalleryImage::create([
            'path' => '/storage/media/gallery/kiln.webp',
            'alt' => ['fa' => 'کوره دوار', 'en' => 'Rotary kiln', 'ar' => 'فرن دوّار'],
            'album' => 'plant',
            'is_active' => true,
        ]);

        $this->get('/fa/projects-gallery')->assertOk()->assertSee('کوره دوار');
        $this->get('/fa/projects-gallery?album=events')->assertOk()->assertDontSee('کوره دوار');
    }

    /** An image with no alt is invisible to a screen reader and to image search. */
    public function test_alt_text_falls_back_to_the_caption(): void
    {
        $image = GalleryImage::create([
            'path' => '/storage/media/gallery/a.webp',
            'title' => ['fa' => 'عنوان', 'en' => 'Caption', 'ar' => 'عنوان'],
            'alt' => [],
            'album' => 'plant',
            'is_active' => true,
        ]);

        $this->assertSame('عنوان', $image->altText());
    }

    // ── Redirects ───────────────────────────────────────────────────────

    public function test_a_redirect_rewrites_a_404_into_a_301(): void
    {
        Redirect::create([
            'source' => '/fa/old-catalogue',
            'destination' => '/fa/products',
            'status' => 301,
            'is_active' => true,
        ]);

        $this->get('/fa/old-catalogue')->assertRedirect(url('/fa/products'))->assertStatus(301);
    }

    public function test_redirect_sources_are_normalised_so_case_and_slashes_do_not_matter(): void
    {
        $this->assertSame('/old-page', Redirect::normalise('/Old-Page/'));
        $this->assertSame('/old-page', Redirect::normalise('old-page'));
        $this->assertSame('/old-page', Redirect::normalise('/old-page?utm=x'));
    }

    public function test_an_inactive_redirect_does_not_fire(): void
    {
        Redirect::create([
            'source' => '/fa/retired',
            'destination' => '/fa/products',
            'is_active' => false,
        ]);

        $this->get('/fa/retired')->assertNotFound();
    }

    /** A redirect must never shadow a page that actually exists. */
    public function test_a_redirect_does_not_override_a_live_page(): void
    {
        Redirect::create([
            'source' => '/fa/contact',
            'destination' => '/fa/products',
            'is_active' => true,
        ]);

        $this->get('/fa/contact')->assertOk();
    }

    public function test_redirects_count_their_use(): void
    {
        $redirect = Redirect::create([
            'source' => '/fa/moved',
            'destination' => '/fa/products',
            'is_active' => true,
        ]);

        $this->get('/fa/moved');

        $this->assertSame(1, $redirect->fresh()->hits);
    }

    // ── LocalBusiness ───────────────────────────────────────────────────

    public function test_local_business_is_not_published_until_switched_on(): void
    {
        $this->get('/fa')->assertDontSee('"@type":"LocalBusiness"', false);
    }

    public function test_local_business_is_published_once_configured(): void
    {
        Setting::put('business.enabled', true, 'business', translatable: false);
        Setting::put('business.street', ['fa' => 'شهرک صنعتی محمودآباد', 'en' => 'Mahmoudabad Zone', 'ar' => 'محمودآباد']);
        Setting::put('business.locality', ['fa' => 'قم', 'en' => 'Qom', 'ar' => 'قم']);

        $this->get('/fa')->assertSee('"@type":"LocalBusiness"', false);
    }

    /** Placeholder structured data is worse than none — search engines show it. */
    public function test_local_business_is_withheld_when_the_address_is_incomplete(): void
    {
        Setting::put('business.enabled', true, 'business', translatable: false);
        Setting::put('business.street', ['fa' => 'خیابان نمونه']);
        Setting::put('business.locality', []);

        $this->get('/fa')->assertDontSee('"@type":"LocalBusiness"', false);
    }
}
