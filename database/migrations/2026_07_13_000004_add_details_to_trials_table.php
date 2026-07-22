<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->jsonb('controls')->nullable();   // témoins: ["Magenta","Novitus",...]
            $table->jsonb('measures')->nullable();    // measurement rows (essai vs témoin + weight)
        });
    }

    public function down(): void
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->dropColumn(['controls', 'measures']);
        });
    }
};
