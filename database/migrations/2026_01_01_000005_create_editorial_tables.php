<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('type', 20)->default('news');   // news | article | case-study
            $table->json('title');
            $table->json('excerpt')->nullable();
            $table->json('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->unsignedSmallInteger('reading_minutes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'published_at']);
            $table->index(['type', 'is_active']);
        });

        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('category', 40)->default('catalogue'); // catalogue | datasheet | certificate | guide
            $table->string('file_path');
            $table->string('file_extension', 10)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();  // bytes
            // Null = language-neutral (e.g. a drawing); otherwise the PDF's language.
            $table->string('locale', 5)->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('requires_form')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category', 'position']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('issuer')->nullable();
            $table->string('image')->nullable();
            $table->string('reference', 60)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->json('question');
            $table->json('answer');
            $table->string('group', 40)->default('general'); // general | technical | ordering | export
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'group', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('posts');
    }
};
