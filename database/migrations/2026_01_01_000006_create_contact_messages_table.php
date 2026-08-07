<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('contact');   // contact | quote
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');

            // Quote-specific
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quantity', 60)->nullable();
            $table->string('delivery_terms', 40)->nullable();  // EXW | FOB | CIF ...

            $table->string('locale', 5)->default('fa');
            // Hashed, not raw: enough to rate-limit and spot abuse without
            // retaining a plain-text identifier for every enquiry.
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('referer', 255)->nullable();

            $table->string('status', 20)->default('new');      // new | read | replied | spam
            $table->text('internal_note')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
