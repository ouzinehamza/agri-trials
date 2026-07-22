<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Production readiness check for a per-company install (SPEC §10). Run before going live:
 * `php artisan agri:preflight`. Exits non-zero if any hard requirement fails, so it can gate a deploy.
 */
class Preflight extends Command
{
    protected $signature = 'agri:preflight';

    protected $description = 'Verify this install is configured safely for production';

    private int $failures = 0;

    public function handle(): int
    {
        $this->line('Agri-Trials — production preflight');
        $this->newLine();

        $prod = app()->environment('production');

        $this->assert('APP_KEY is set', ! empty(config('app.key')));
        $this->assert('APP_DEBUG is off', config('app.debug') === false, hard: $prod);
        $this->assert('APP_ENV is production', $prod, hard: false);
        $this->assert('APP_URL uses HTTPS', str_starts_with((string) config('app.url'), 'https://'), hard: false);

        $this->assert('DB password is not the default', env('DB_PASSWORD') && env('DB_PASSWORD') !== 'secret');
        $this->assert('MinIO password is not the default', env('MINIO_ROOT_PASSWORD') !== 'agri-minio-secret', hard: false);
        $this->assert('Secure session cookies enabled', (bool) config('session.secure'), hard: $prod && str_starts_with((string) config('app.url'), 'https://'));

        $this->assert('Database reachable', $this->check(fn () => DB::connection()->getPdo()));
        $this->assert('Redis reachable', $this->check(fn () => Redis::connection()->ping()));
        $this->assert('Media/storage disk writable', $this->check(function () {
            Storage::disk('local')->put('preflight.tmp', 'ok');
            Storage::disk('local')->delete('preflight.tmp');
        }));

        $this->assert('An admin user exists', $this->check(fn () => \App\Models\User::where('role', 'admin')->exists()));

        $this->newLine();
        if ($this->failures > 0) {
            $this->error("Preflight failed: {$this->failures} blocking issue(s). Resolve before serving traffic.");

            return self::FAILURE;
        }
        $this->info('Preflight passed.');

        return self::SUCCESS;
    }

    private function assert(string $label, bool $ok, bool $hard = true): void
    {
        if ($ok) {
            $this->line("  <fg=green>✓</> {$label}");

            return;
        }
        if ($hard) {
            $this->failures++;
            $this->line("  <fg=red>✗</> {$label}");
        } else {
            $this->line("  <fg=yellow>!</> {$label} <fg=gray>(recommended)</>");
        }
    }

    private function check(callable $probe): bool
    {
        try {
            $probe();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
