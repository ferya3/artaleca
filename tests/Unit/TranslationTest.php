<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Locales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_an_attribute_returns_the_active_locale(): void
    {
        $product = $this->makeProduct();

        $this->app->setLocale('en');
        $this->assertSame('Leca 4–10', $product->name);

        $this->app->setLocale('ar');
        $this->assertSame('ليكا ٤–١٠', $product->name);
    }

    public function test_an_empty_translation_falls_back_to_the_default_locale(): void
    {
        $product = $this->makeProduct([
            'name' => ['fa' => 'فارسی', 'en' => '', 'ar' => null],
        ]);

        $this->app->setLocale('en');
        $this->assertSame('فارسی', $product->name);

        $this->app->setLocale('ar');
        $this->assertSame('فارسی', $product->name);
    }

    public function test_it_falls_back_to_any_non_empty_value_as_a_last_resort(): void
    {
        $product = $this->makeProduct(['name' => ['fa' => '', 'en' => 'Only English', 'ar' => '']]);

        $this->app->setLocale('ar');
        $this->assertSame('Only English', $product->name);
    }

    /**
     * The important guarantee: an editor working in one language can never
     * silently erase the other two.
     */
    public function test_writing_a_plain_string_only_touches_the_active_locale(): void
    {
        $product = $this->makeProduct();

        $this->app->setLocale('en');
        $product->name = 'Renamed in English';
        $product->save();

        $fresh = $product->fresh();

        $this->assertSame('Renamed in English', $fresh->getTranslation('name', 'en'));
        $this->assertSame('لیکا ۴–۱۰', $fresh->getTranslation('name', 'fa'));
        $this->assertSame('ليكا ٤–١٠', $fresh->getTranslation('name', 'ar'));
    }

    public function test_set_translations_ignores_unknown_locales(): void
    {
        $product = $this->makeProduct();

        $product->setTranslations('name', ['fa' => 'الف', 'de' => 'Deutsch', 'en' => 'Beta']);
        $product->save();

        $this->assertSame(
            ['fa' => 'الف', 'en' => 'Beta'],
            $product->fresh()->getTranslations('name'),
        );
    }

    public function test_serialisation_resolves_translatable_attributes(): void
    {
        $product = $this->makeProduct();

        $this->app->setLocale('en');

        $this->assertSame('Leca 4–10', $product->toArray()['name']);
    }

    public function test_untranslated_attributes_are_untouched(): void
    {
        $product = $this->makeProduct();

        $this->assertSame('leca-4-10', $product->slug);
        $this->assertSame('ALS-0410', $product->sku);
        $this->assertSame(4.0, $product->grain_min_mm);
    }

    public function test_direction_is_reported_per_locale(): void
    {
        $this->assertTrue(Locales::isRtl('fa'));
        $this->assertTrue(Locales::isRtl('ar'));
        $this->assertFalse(Locales::isRtl('en'));
    }
}
