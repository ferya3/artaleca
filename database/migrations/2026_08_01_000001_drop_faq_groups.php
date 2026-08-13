<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The FAQ becomes one flat, editor-owned list.
 *
 * It shipped with four fixed groups — general, technical, ordering, export —
 * and a set of questions written to fill them. The plant writes its own
 * questions, so both go: the seeded entries are cleared and the column that
 * forced every new question into one of four buckets is dropped.
 *
 * Clearing the table rather than deleting the known seeded rows: the seeder
 * keyed them on group + position, which is exactly what is being removed, so
 * there is nothing left to match them by. This runs before the plant has
 * written any questions of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('faqs')->delete();

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'group', 'position']);
            $table->dropColumn('group');
            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'position']);
            $table->string('group', 40)->default('general');
            $table->index(['is_active', 'group', 'position']);
        });
    }
};
