<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->default('variety');   // variety | control
            $table->string('name');
            $table->string('ref_code')->nullable();
            $table->integer('alert_threshold')->default(0);
            $table->integer('germination_pct')->nullable();
            $table->integer('purity_pct')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('last_germ_test_on')->nullable();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_item_id')->constrained()->cascadeOnDelete();
            $table->date('moved_on');
            $table->string('type');                        // in | out
            $table->integer('quantity');
            $table->string('reason')->nullable();          // achat, semis, ajustement…
            $table->string('trial_code')->nullable();      // when consumed by a trial's Semis
            $table->string('lot_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_items');
    }
};
