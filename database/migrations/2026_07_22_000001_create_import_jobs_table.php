<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import runs (SPEC §3.6 / §5). Every CSV/XLSX import is previewed (validated dry-run) then committed;
 * large files are committed on the queue. This table tracks status, counts and per-row errors.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->index();
            $table->string('slug');                       // référentiel slug used to resolve routes
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('filename');                   // original upload name (display)
            $table->string('path');                       // stored path on the local disk
            $table->string('status')->default('previewed'); // previewed|processing|completed|failed
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('imported')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->jsonb('errors')->nullable();          // [{line, errors:[]}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
