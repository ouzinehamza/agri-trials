<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Field-level constraints (SPEC §3.1): a field can be marked unique (enforced at validation) or as the
 * model's primary/display field (used for the row title, reference labels, table first column).
 * Slug source, default value and option source (static vs entity) live in the existing `settings` jsonb.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_definitions', function (Blueprint $table) {
            $table->boolean('is_unique')->default(false)->after('required');
            $table->boolean('is_primary')->default(false)->after('is_unique');
        });
    }

    public function down(): void
    {
        Schema::table('field_definitions', function (Blueprint $table) {
            $table->dropColumn(['is_unique', 'is_primary']);
        });
    }
};
