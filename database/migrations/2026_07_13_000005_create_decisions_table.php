<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->string('level')->default('trial');        // trial | variety
            $table->foreignId('trial_id')->nullable()->constrained()->nullOnDelete();
            $table->string('variety')->nullable();             // for variety-level (cross-site/season) verdicts
            $table->string('verdict');                         // launch | reject | retrial
            $table->unsignedTinyInteger('score');              // 0..100 weighted score at decision time
            $table->jsonb('weights_snapshot');                 // {code: weight} frozen
            $table->jsonb('scorecard_snapshot');               // per-measure numbers frozen
            $table->text('justification');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at');
            $table->timestamps();

            $table->index(['trial_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
