<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['varieties', 'controls', 'rootstocks', 'partners', 'segments'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->jsonb('custom_data')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['varieties', 'controls', 'rootstocks', 'partners', 'segments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
