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
        ]);

        // Seeded content invalidates the navigation and homepage caches.
        Navigation::flush();
    }
}
