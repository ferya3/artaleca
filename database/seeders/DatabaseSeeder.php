<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Navigation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CatalogueSeeder::class,
            ApplicationSeeder::class,
            ProjectSeeder::class,
            EditorialSeeder::class,
            CmsSeeder::class,
            SettingSeeder::class,
            TechnicalArticleSeeder::class,

            // Last: it fills in whatever SEO fields the seeders above left
            // empty, so it has to see every record they create.
            SeoMetadataSeeder::class,
        ]);

        // Seeded content invalidates the navigation and homepage caches.
        Navigation::flush();
    }
}
