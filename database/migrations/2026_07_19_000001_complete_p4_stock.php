<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_items', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->after('id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->string('unit', 24)->default('graines')->after('ref_code');
            $table->boolean('is_archived')->default(false)->after('last_germ_test_on');
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('supplier_name')->nullable();
            $table->date('received_on')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedTinyInteger('germination_pct')->nullable();
            $table->unsignedTinyInteger('purity_pct')->nullable();
            $table->date('last_germ_test_on')->nullable();
            $table->string('location')->nullable();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();
            $table->unique(['stock_item_id', 'code']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('stock_lot_id')->nullable()->after('stock_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('trial_id')->nullable()->after('reason')->constrained()->nullOnDelete();
            $table->foreignId('operator_id')->nullable()->after('trial_id')->constrained('users')->nullOnDelete();
            $table->foreignId('stage_record_id')->nullable()->after('operator_id')->constrained('trial_stage_records')->nullOnDelete();
            $table->string('reference')->nullable()->after('stage_record_id');
            $table->text('notes')->nullable()->after('reference');
            $table->index(['stock_lot_id', 'moved_on']);
        });

        foreach (DB::table('stock_movements')->whereNotNull('lot_number')->distinct()->pluck('stock_item_id') as $itemId) {
            foreach (DB::table('stock_movements')->where('stock_item_id', $itemId)->whereNotNull('lot_number')->distinct()->pluck('lot_number') as $code) {
                $lotId = DB::table('stock_lots')->insertGetId([
                    'stock_item_id' => $itemId, 'code' => $code, 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('stock_movements')->where('stock_item_id', $itemId)->where('lot_number', $code)->update(['stock_lot_id' => $lotId]);
            }
        }

        DB::table('stock_movements')->whereNotNull('trial_code')->orderBy('id')->each(function ($movement) {
            $trialId = DB::table('trials')->where('code', $movement->trial_code)->value('id');
            if ($trialId) DB::table('stock_movements')->where('id', $movement->id)->update(['trial_id' => $trialId]);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_lot_id');
            $table->dropConstrainedForeignId('trial_id');
            $table->dropConstrainedForeignId('operator_id');
            $table->dropConstrainedForeignId('stage_record_id');
            $table->dropColumn(['reference', 'notes']);
        });
        Schema::dropIfExists('stock_lots');
        Schema::table('stock_items', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['subject_type', 'subject_id', 'unit', 'is_archived']);
        });
    }
};
