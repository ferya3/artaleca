<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('client')->nullable();
            $table->json('location')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->json('summary')->nullable();
            $table->json('body')->nullable();
            $table->json('scope')->nullable();          // [{fa,en,ar}, ...]
            $table->unsignedInteger('volume_m3')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'year']);
            $table->index(['is_featured', 'is_active']);
        });

        Schema::create('product_project', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->primary(['product_id', 'project_id']);
            $table->index('project_id');
        });

        Schema::create('application_project', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            $table->primary(['application_id', 'project_id']);
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_project');
        Schema::dropIfExists('product_project');
        Schema::dropIfExists('projects');
    }
};
