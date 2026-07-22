<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();   // e.g. supplier, variety, trial
            $table->string('key');                    // e.g. linkedin_url
            $table->string('label');                  // display label (FR)
            $table->string('type')->default('text');  // text|textarea|number|email|url|tel|select|boolean|date
            $table->jsonb('options')->nullable();     // for select: [{value,label}]
            $table->boolean('required')->default(false);
            $table->boolean('translatable')->default(false);
            $table->boolean('is_system')->default(false);   // system fields can't be deleted
            $table->boolean('show_in_table')->default(true);
            $table->string('help_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['model_type', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_definitions');
    }
};
