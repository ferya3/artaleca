<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Free-form content pages an editor can add without a developer.
         * The bespoke About/Quality/Plant pages keep their designed layouts;
         * this covers everything else ("Careers", "Terms of sale", …) and is
         * routed by a catch-all placed after every named route.
         */
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('lead')->nullable();
            $table->json('body')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('summary')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->string('kind', 20)->default('partner');  // partner | client | association
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'kind', 'position']);
        });

        /*
         * A flat media library. `album` groups images for the public gallery;
         * product and project galleries reference paths directly, so an image
         * can be reused without being duplicated on disk.
         */
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->json('title')->nullable();
            $table->json('alt')->nullable();
            $table->string('album', 40)->default('plant'); // plant | products | projects | events
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'album', 'position']);
        });

        /*
         * Editor-managed redirects. Kept deliberately simple — an exact,
         * normalised source path mapping to a destination — because a regex
         * redirect table is the kind of thing that silently breaks a site.
         */
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source')->unique();
            $table->string('destination');
            $table->unsignedSmallInteger('status')->default(301);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'source']);
        });

        Schema::create('download_product', function (Blueprint $table) {
            $table->foreignId('download_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->primary(['download_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::table('products', function (Blueprint $table) {
            // Distinct from `description`: short, scannable bullets. The brief
            // asks for both "features" (what it is) and "advantages" (why it
            // matters), and specifiers read them differently.
            $table->json('features')->nullable()->after('description');
            $table->json('advantages')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['features', 'advantages']);
        });

        Schema::dropIfExists('download_product');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('gallery_images');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('pages');
    }
};
