<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number');
            $table->string('partner');                 // third-party (e.g. external nursery)
            $table->string('trial_code')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('MAD');
            $table->string('status')->default('draft'); // draft | sent | paid | overdue
            $table->date('issued_on');
            $table->date('due_on')->nullable();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('category')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('MAD');
            $table->date('incurred_on');
            $table->string('trial_code')->nullable();
            $table->string('partner')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('custom_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('invoices');
    }
};
