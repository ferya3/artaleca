<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * A category with one product, in all three languages.
     *
     * Nearly every feature test needs a real catalogue row to hit a URL, and
     * building it here keeps the tests about behaviour rather than fixtures.
     */
    protected function makeProduct(array $overrides = []): Product
    {
        // firstOrCreate, not create: several tests build more than one product
        // and they should share the category rather than collide on its slug.
        $category = ProductCategory::firstOrCreate(
            ['slug' => 'structural'],
            [
                'name' => ['fa' => 'سبکدانه سازه‌ای', 'en' => 'Structural grades', 'ar' => 'درجات إنشائية'],
                'summary' => ['fa' => 'خلاصه فارسی', 'en' => 'English summary', 'ar' => 'ملخص عربي'],
                'is_active' => true,
            ],
        );

        return Product::create(array_merge([
            'product_category_id' => $category->id,
            'slug' => 'leca-4-10',
            'sku' => 'ALS-0410',
            'name' => ['fa' => 'لیکا ۴–۱۰', 'en' => 'Leca 4–10', 'ar' => 'ليكا ٤–١٠'],
            'summary' => ['fa' => 'خلاصه', 'en' => 'Summary', 'ar' => 'ملخص'],
            'grain_min_mm' => 4,
            'grain_max_mm' => 10,
            'bulk_density_min' => 320,
            'bulk_density_max' => 400,
            'is_active' => true,
            'is_featured' => true,
        ], $overrides));
    }

    protected function makeAdmin(string $role = User::ROLE_ADMIN): User
    {
        // Emails are unique, and a test may need two accounts with one role.
        static $sequence = 0;
        $sequence++;

        return User::create([
            'name' => 'Test '.ucfirst($role),
            'email' => "{$role}-{$sequence}@example.test",
            'password' => 'password-for-tests',
            'role' => $role,
            'is_active' => true,
            'locale' => 'fa',
        ]);
    }

    /**
     * A `started_at` token that looks like a form a human filled in: minted a
     * minute ago, correctly signed.
     */
    protected function humanFormToken(int $secondsAgo = 60): string
    {
        $timestamp = (string) (now()->getTimestamp() - $secondsAgo);

        return $timestamp.'.'.hash_hmac('sha256', $timestamp, (string) config('app.key'));
    }
}
