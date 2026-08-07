<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editor-owned strings that would otherwise be hard-coded in Blade
        // (hero headline, homepage intro, CTA copy). Values are JSON so a
        // setting can hold a per-locale map or a plain scalar.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->json('value')->nullable();
            $table->string('group', 40)->default('general');
            $table->boolean('is_translatable')->default(true);
            $table->timestamps();

            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
