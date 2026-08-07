<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            // One Latin slug shared by all locales: alternate-language URLs then
            // differ only by prefix, which keeps hreflang/canonical trivial.
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            $table->string('image')->nullable();
            $table->string('icon', 40)->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('sku', 40)->nullable()->unique();

            $table->json('name');
            $table->json('tagline')->nullable();
            $table->json('summary')->nullable();
            $table->json('description')->nullable();

            // Headline technical properties get real columns so the catalogue
            // can be filtered and sorted on them without touching JSON.
            $table->decimal('grain_min_mm', 5, 2)->nullable();
            $table->decimal('grain_max_mm', 5, 2)->nullable();
            $table->unsignedSmallInteger('bulk_density_min')->nullable();   // kg/m³
            $table->unsignedSmallInteger('bulk_density_max')->nullable();   // kg/m³
            $table->unsignedSmallInteger('particle_density')->nullable();   // kg/m³
            $table->decimal('crushing_strength', 5, 2)->nullable();         // MPa
            $table->decimal('thermal_conductivity', 5, 3)->nullable();      // W/m·K
            $table->decimal('water_absorption_24h', 5, 2)->nullable();      // %
            $table->decimal('ph_value', 3, 1)->nullable();
            $table->decimal('fire_resistance_c', 6, 1)->nullable();         // °C

            // Free-form rows: [{label:{fa,en,ar}, value:{fa,en,ar}}, ...]
            $table->json('specs')->nullable();
            // Packaging options: [{type:{...}, volume:'1 m³', weight:'...'}]
            $table->json('packaging')->nullable();
            $table->json('standards')->nullable();

            $table->string('hero_image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('datasheet_path')->nullable();

            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
            $table->index(['product_category_id', 'is_active']);
            $table->index(['is_featured', 'is_active']);
            $table->index(['grain_min_mm', 'grain_max_mm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
