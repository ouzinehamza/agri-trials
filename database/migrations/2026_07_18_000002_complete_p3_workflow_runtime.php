<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_templates', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('allow_trial_overrides');
        });
        Schema::table('trial_templates', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('config');
        });
        Schema::table('trials', function (Blueprint $table) {
            $table->jsonb('workflow_snapshot')->nullable()->after('measurement_set_id');
            $table->jsonb('measurement_snapshot')->nullable()->after('workflow_snapshot');
        });
        Schema::table('trial_stage_records', function (Blueprint $table) {
            $table->string('stage_key')->nullable()->after('workflow_stage_id');
            $table->jsonb('stage_name')->nullable()->after('stage_key');
            $table->unsignedInteger('sort_order')->default(0)->after('stage_name');
            $table->jsonb('config_snapshot')->nullable()->after('sort_order');
        });
        Schema::create('trial_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_record_id')->nullable()->constrained('trial_stage_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_notes');
        Schema::table('trial_stage_records', fn (Blueprint $table) => $table->dropColumn(['stage_key','stage_name','sort_order','config_snapshot']));
        Schema::table('trials', fn (Blueprint $table) => $table->dropColumn(['workflow_snapshot','measurement_snapshot']));
        Schema::table('trial_templates', fn (Blueprint $table) => $table->dropColumn('is_archived'));
        Schema::table('workflow_templates', fn (Blueprint $table) => $table->dropColumn('is_archived'));
    }
};
