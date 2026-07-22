<?php

namespace App\Http\Controllers;

use App\Domain\Import\CsvImporter;
use App\Domain\Metadata\Referentiels;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Two-step, metadata-driven import (SPEC §3.6): preview (validated dry-run) then commit. Files above
 * SYNC_LIMIT rows are committed on the queue; the client polls the ImportJob for status.
 */
class ImportController extends Controller
{
    /** Row count above which the commit runs on the queue instead of inline. */
    private const SYNC_LIMIT = 500;

    /** Step 1 — validate the whole file without persisting and return a preview. */
    public function preview(Request $request, string $slug): RedirectResponse
    {
        [$model] = $this->resolve($slug);
        $request->validate(['file' => ['required', 'file', 'max:10240', 'mimes:csv,txt']]);

        $path = $request->file('file')->store('imports', 'local');
        $analysis = CsvImporter::analyze($model, $model::MODEL_TYPE, $model::SYSTEM_FIELDS, 'name', Storage::disk('local')->path($path));

        $job = ImportJob::create([
            'model_type' => $model::MODEL_TYPE,
            'slug' => $slug,
            'user_id' => $request->user()->id,
            'filename' => $request->file('file')->getClientOriginalName(),
            'path' => $path,
            'status' => 'previewed',
            'total' => $analysis['total'],
            'imported' => $analysis['imported'],
            'failed' => $analysis['failed'],
            'errors' => $analysis['errors'],
        ]);

        return back()->with('import_preview', [
            'job_id' => $job->id,
            'filename' => $job->filename,
            'columns' => $analysis['columns'],
            'total' => $analysis['total'],
            'valid' => $analysis['imported'],
            'invalid' => $analysis['failed'],
            'rows' => $analysis['rows'],
        ]);
    }

    /** Step 2 — commit the previewed file (inline for small files, queued for large ones). */
    public function commit(Request $request, string $slug, ImportJob $importJob): RedirectResponse
    {
        [$model] = $this->resolve($slug);
        abort_unless($importJob->slug === $slug && $importJob->model_type === $model::MODEL_TYPE, 404);
        $this->authorize('commit', $importJob);
        abort_unless($importJob->status === 'previewed', 422, 'Cet import a déjà été traité.');

        if ($importJob->total > self::SYNC_LIMIT) {
            $importJob->update(['status' => 'processing']);
            ProcessImportJob::dispatch($importJob->id);

            return back()->with('import_result', ['queued' => true, 'job_id' => $importJob->id, 'total' => $importJob->total]);
        }

        $result = CsvImporter::commit($model, $model::MODEL_TYPE, $model::SYSTEM_FIELDS, 'name', Storage::disk('local')->path($importJob->path));
        $importJob->update(['status' => 'completed', 'total' => $result['total'], 'imported' => $result['imported'], 'failed' => $result['failed'], 'errors' => $result['errors']]);
        Storage::disk('local')->delete($importJob->path);

        return back()->with('import_result', [
            'imported' => $result['imported'],
            'failed' => $result['failed'],
            'errors' => $result['errors'],
        ]);
    }

    /** JSON status for polling a queued import (owner only). */
    public function status(Request $request, ImportJob $importJob): JsonResponse
    {
        $this->authorize('view', $importJob);

        return response()->json($importJob->toStatusArray());
    }

    /** @return array{0: class-string, 1: string} */
    private function resolve(string $slug): array
    {
        $cfg = Referentiels::resolve($slug);
        abort_if($cfg === null, 404);

        return [$cfg['model'], $cfg['label']];
    }
}
