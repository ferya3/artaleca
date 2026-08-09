<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Partner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentativesPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeRepresentative(array $attributes = []): Partner
    {
        return Partner::create([
            'slug' => 'rep-shiraz',
            'name' => ['fa' => 'نمایندگی شیراز', 'en' => 'Shiraz representative', 'ar' => 'وكيل شيراز'],
            'kind' => 'representative',
            'is_active' => true,
            ...$attributes,
        ]);
    }

    public function test_the_page_lists_active_representatives(): void
    {
        $this->makeRepresentative();

        $this->get('/fa/representatives')
            ->assertOk()
            ->assertSee('نمایندگی شیراز');
    }

    /**
     * A representative is a Partner with a kind, so the page must filter — or a
     * client logo would be published as though it were a sales agent.
     */
    public function test_partners_of_other_kinds_are_not_listed(): void
    {
        $this->makeRepresentative();

        Partner::create([
            'slug' => 'a-client',
            'name' => ['fa' => 'یک مشتری', 'en' => 'A client', 'ar' => 'عميل'],
            'kind' => 'client',
            'is_active' => true,
        ]);

        $this->get('/fa/representatives')
            ->assertOk()
            ->assertSee('نمایندگی شیراز')
            ->assertDontSee('یک مشتری');
    }

    public function test_an_unpublished_representative_is_hidden(): void
    {
        $this->makeRepresentative(['is_active' => false]);

        $this->get('/fa/representatives')->assertOk()->assertDontSee('نمایندگی شیراز');
    }

    /** An outbound link to a company we do not control must not leak the opener. */
    public function test_an_outbound_website_link_is_safely_marked(): void
    {
        $this->makeRepresentative(['website' => 'https://example.com']);

        $html = $this->get('/fa/representatives')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<a[^>]+href="https:\/\/example\.com"[^>]*rel="noopener noreferrer"/',
            $html,
        );
    }

    public function test_the_page_answers_in_every_language(): void
    {
        $this->makeRepresentative();

        foreach (['fa' => 'نمایندگی شیراز', 'en' => 'Shiraz representative', 'ar' => 'وكيل شيراز'] as $locale => $name) {
            $this->get("/{$locale}/representatives")->assertOk()->assertSee($name);
        }
    }

    public function test_the_page_appears_in_the_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/fa/representatives')
            ->assertSee('/en/representatives');
    }
}
