<?php

namespace App\Jobs;

use App\Domain\Import\CsvImporter;
use App\Domain\Metadata\Referentiels;
use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Commits a previewed import off the request cycle (SPEC §3.6) so large files don't block the UI.
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $importJobId) {}

    public function handle(): void
    {
        $job = ImportJob::find($this->importJobId);
        if (! $job || $job->status === 'completed') {
            return;
        }

        $cfg = Referentiels::resolve($job->slug);
        if ($cfg === null) {
            $job->update(['status' => 'failed']);

            return;
        }

        $model = $cfg['model'];
        $result = CsvImporter::commit($model, $model::MODEL_TYPE, $model::SYSTEM_FIELDS, 'name', Storage::disk('local')->path($job->path));

        $job->update([
            'status' => 'completed',
            'total' => $result['total'],
            'imported' => $result['imported'],
            'failed' => $result['failed'],
            'errors' => $result['errors'],
        ]);
        Storage::disk('local')->delete($job->path);
    }

    public function failed(\Throwable $e): void
    {
        ImportJob::where('id', $this->importJobId)->update(['status' => 'failed']);
    }
}
