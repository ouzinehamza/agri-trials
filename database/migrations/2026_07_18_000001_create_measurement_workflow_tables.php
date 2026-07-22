<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('measurements', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->string('code')->unique();
            $table->string('unit')->nullable();
            $table->string('data_type');
            $table->string('aggregation');
            $table->string('direction');
            $table->string('category')->nullable();
            $table->jsonb('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('measurement_sets', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->timestamps();
        });
        Schema::create('measurement_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('measurement_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('measurement_id')->constrained()->restrictOnDelete();
            $table->decimal('default_weight', 6, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['measurement_set_id', 'measurement_id']);
        });

        Schema::create('workflow_templates', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('allow_trial_overrides')->default(true);
            $table->timestamps();
        });
        Schema::create('workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_template_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->jsonb('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->jsonb('config')->nullable();
            $table->unique(['workflow_template_id', 'key']);
        });
        Schema::create('trial_templates', function (Blueprint $table) {
            $table->id();
            $table->jsonb('name');
            $table->foreignId('workflow_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('measurement_set_id')->constrained()->restrictOnDelete();
            $table->foreignId('culture_id')->nullable();
            $table->jsonb('config')->nullable();
            $table->timestamps();
        });

        Schema::table('trials', function (Blueprint $table) {
            $table->foreignId('trial_template_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->foreignId('workflow_template_id')->nullable()->after('trial_template_id')->constrained()->restrictOnDelete();
            $table->foreignId('measurement_set_id')->nullable()->after('workflow_template_id')->constrained()->restrictOnDelete();
        });
        Schema::create('trial_stage_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_stage_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->jsonb('data')->nullable();
            $table->unique(['trial_id', 'workflow_stage_id']);
        });
        Schema::create('measurement_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stage_record_id')->nullable()->constrained('trial_stage_records')->cascadeOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label');
            $table->foreignId('measurement_id')->constrained()->restrictOnDelete();
            $table->decimal('value', 16, 4)->nullable();
            $table->jsonb('value_json')->nullable();
            $table->timestamps();
            $table->index(['trial_id', 'measurement_id', 'subject_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('measurement_values');
        Schema::dropIfExists('trial_stage_records');
        Schema::table('trials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trial_template_id');
            $table->dropConstrainedForeignId('workflow_template_id');
            $table->dropConstrainedForeignId('measurement_set_id');
        });
        Schema::dropIfExists('trial_templates');
        Schema::dropIfExists('workflow_stages');
        Schema::dropIfExists('workflow_templates');
        Schema::dropIfExists('measurement_set_items');
        Schema::dropIfExists('measurement_sets');
        Schema::dropIfExists('measurements');
    }
};
