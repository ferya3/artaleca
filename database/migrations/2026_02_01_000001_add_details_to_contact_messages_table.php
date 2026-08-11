<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A place for the fields that belong to one form rather than to every enquiry.
 *
 * The representation application asks six things no other form asks —
 * territory, line of business, years trading, warehouse area, expected volume.
 * Six columns that are null on every contact message and every RFQ would be
 * the wrong shape, and folding them into the message body would lose the
 * structure the sales desk needs to compare applicants.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->json('details')->nullable()->after('delivery_terms');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
