<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_record_id')->nullable()->constrained('trial_stage_records')->nullOnDelete();
            $table->unsignedInteger('sequence');
            $table->date('harvested_on');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();
            $table->unique(['trial_id','sequence']);
            $table->index(['trial_id','harvested_on']);
        });
        Schema::table('measurement_values', function (Blueprint $table) {
            $table->foreignId('harvest_id')->nullable()->after('stage_record_id')->constrained()->cascadeOnDelete();
            $table->index(['harvest_id','measurement_id','subject_type']);
        });
        Schema::table('decisions', function (Blueprint $table) {
            $table->jsonb('context_snapshot')->nullable()->after('scorecard_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('decisions',fn(Blueprint $t)=>$t->dropColumn('context_snapshot'));
        Schema::table('measurement_values',function(Blueprint $t){$t->dropConstrainedForeignId('harvest_id');});
        Schema::dropIfExists('harvests');
    }
};
