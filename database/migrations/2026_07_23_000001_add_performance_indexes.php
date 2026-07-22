<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance pass: index the foreign-key / scoping columns that list & dashboard queries filter on.
 * Postgres does not auto-index the referencing side of a FK, so these were sequential scans:
 *  - trials.workspace_id       — every trial list, dashboard and RBAC scope filters on it (hottest path)
 *  - trial_notes.trial_id      — notes are loaded per trial
 *  - stock_movements.*_id      — ledger lookups by item and by trial
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trials', fn (Blueprint $t) => $t->index('workspace_id'));
        Schema::table('trial_notes', fn (Blueprint $t) => $t->index('trial_id'));
        Schema::table('stock_movements', function (Blueprint $t) {
            $t->index('stock_item_id');
            $t->index('trial_id');
        });
    }

    public function down(): void
    {
        Schema::table('trials', fn (Blueprint $t) => $t->dropIndex(['workspace_id']));
        Schema::table('trial_notes', fn (Blueprint $t) => $t->dropIndex(['trial_id']));
        Schema::table('stock_movements', function (Blueprint $t) {
            $t->dropIndex(['stock_item_id']);
            $t->dropIndex(['trial_id']);
        });
    }
};
