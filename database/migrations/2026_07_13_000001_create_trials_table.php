<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trials', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            // Denormalized display fields for the P1 slice. These become FKs to the
            // référentiels tables (variety_id, culture_id, supplier_id, …) in later phases.
            $table->string('variety');
            $table->string('culture');
            $table->string('conduct')->nullable();
            $table->string('supplier')->nullable();
            $table->string('segment')->nullable();
            $table->string('owner')->nullable();
            $table->string('site')->nullable();
            $table->string('season')->nullable();

            // Structural workflow state (app logic branches on these → real columns).
            $table->string('status')->default('Création');
            $table->string('status_tone')->default('neutral');
            $table->string('current_stage')->default('creation');

            // Admin-defined custom fields (golden rule: metadata-driven).
            $table->jsonb('custom_data')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trials');
    }
};
