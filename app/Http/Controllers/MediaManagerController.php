<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaManagerController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = MediaAsset::with(['media', 'uploader', 'workspace'])->latest();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($builder) => $builder
                ->where('title', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%"));
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return Inertia::render('Media/Index', [
            'assets' => $query->limit(100)->get()->map(fn ($asset) => $this->present($asset)),
            'filters' => $request->only('q', 'category'),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $term = mb_strtolower((string) $request->query('q'));

        return response()->json(MediaAsset::with('media')->latest()->limit(80)->get()
            ->filter(fn ($asset) => $term === '' || str_contains(mb_strtolower($asset->title), $term))
            ->map(fn ($asset) => [
                'value' => $asset->id,
                'label' => $asset->title,
                'meta' => $asset->getFirstMedia('file')?->human_readable_size,
            ])->values());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeWrite($request);
        $data = $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt,zip'],
            'category' => ['required', Rule::in(['image', 'document', 'report', 'spreadsheet', 'archive', 'other'])],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        foreach ($request->file('files') as $file) {
            $asset = MediaAsset::create([
                'created_by' => $request->user()->id,
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'category' => $data['category'],
                'tags' => collect(explode(',', $data['tags'] ?? ''))
                    ->map(fn (string $tag) => trim($tag))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
            $asset->addMedia($file)->usingFileName($file->hashName())->toMediaCollection('file');
        }

        return back()->with('success', 'Médias importés.');
    }

    public function update(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $this->authorizeWrite($request);
        $mediaAsset->update($request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['image', 'document', 'report', 'spreadsheet', 'archive', 'other'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
        ]));

        return back()->with('success', 'Média mis à jour.');
    }

    public function destroy(Request $request, MediaAsset $mediaAsset): RedirectResponse
    {
        $this->authorizeWrite($request);
        $mediaAsset->delete();

        return back()->with('success', 'Média supprimé.');
    }

    public function file(Media $media, Request $request): StreamedResponse
    {
        $asset = $media->model;
        abort_unless($asset instanceof MediaAsset, 404);
        if (! $request->user()->isAdmin() && $asset->workspace_id !== null) {
            abort_unless(in_array($asset->workspace_id, $request->user()->workspaceIds(), true), 403);
        }

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            [
                'Content-Type' => $media->mime_type,
                'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$media->file_name.'"',
            ],
        );
    }

    private function present(MediaAsset $asset): array
    {
        $media = $asset->getFirstMedia('file');

        return [
            'id' => $asset->id,
            'title' => $asset->title,
            'category' => $asset->category,
            'description' => $asset->description,
            'tags' => $asset->tags ?? [],
            'created_at' => $asset->created_at->format('d/m/Y H:i'),
            'uploader' => $asset->uploader?->name,
            'workspace' => $asset->workspace?->name,
            'file' => $media ? [
                'id' => $media->id,
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->human_readable_size,
                'url' => route('media.file', $media),
            ] : null,
        ];
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'manager', 403);
    }
}
