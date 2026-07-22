<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trial ↔ User assignment (SPEC §4). Narrows external-partner visibility to the specific
 * trials they are assigned to, instead of every trial in their workspace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['trial_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_user');
    }
};
