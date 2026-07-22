<?php

namespace App\Http\Controllers;

use App\Domain\Import\CsvImporter;
use App\Domain\Metadata\MetadataService;
use App\Domain\Metadata\Referentiels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Inertia\Inertia;
use Inertia\Response;

class ReferentielController extends Controller
{
    public function index(string $slug): Response
    {
        [$model, $label] = $this->resolve($slug);
        $fields = MetadataService::fieldsFor($model::MODEL_TYPE);
        // Deferred so the table skeleton shows immediately; both props resolve in one follow-up
        // request and share a single memoized row query (SPEC §2 design-led loading).
        $rows = function () use ($model) { static $r; return $r ??= $model::orderBy('name')->get(); };

        return Inertia::render('Referentiels/Index', [
            'slug' => $slug,
            'label' => $label,
            'tabs' => Referentiels::tabs(),
            'fields' => $fields->values(),
            'rows' => Inertia::defer($rows, 'table'),
            'display' => Inertia::defer(fn () => MetadataService::displayMap($rows(), $fields), 'table'),
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        [$model] = $this->resolve($slug);
        $fields = MetadataService::fieldsFor($model::MODEL_TYPE);

        $data = $request->validate(MetadataService::rules($fields, $model, $model::SYSTEM_FIELDS));
        $data = MetadataService::applySlugs($data, $fields);
        [$attrs, $custom] = MetadataService::split($data, $fields, $model::SYSTEM_FIELDS);
        $attrs['custom_data'] = $custom;
        $model::create($attrs);

        return redirect()->route('referentiels.index', $slug)->with('success', 'Enregistrement ajouté.');
    }

    public function update(Request $request, string $slug, int $id): RedirectResponse
    {
        [$model] = $this->resolve($slug);
        $record = $model::findOrFail($id);
        $fields = MetadataService::fieldsFor($model::MODEL_TYPE);

        $data = $request->validate(MetadataService::rules($fields, $model, $model::SYSTEM_FIELDS, $record->getKey()));
        $data = MetadataService::applySlugs($data, $fields);
        [$attrs, $custom] = MetadataService::split($data, $fields, $model::SYSTEM_FIELDS);
        // Merge custom_data so a partial edit never drops keys not present in the form.
        $attrs['custom_data'] = array_merge($record->custom_data ?? [], $custom);
        $record->update($attrs);

        return redirect()->route('referentiels.index', $slug)->with('success', 'Enregistrement mis à jour.');
    }

    public function destroy(string $slug, int $id): RedirectResponse
    {
        [$model] = $this->resolve($slug);
        $model::findOrFail($id)->delete();

        return redirect()->route('referentiels.index', $slug)->with('success', 'Enregistrement supprimé.');
    }

    public function template(string $slug): SymfonyResponse
    {
        [$model] = $this->resolve($slug);

        return response(CsvImporter::template($model::MODEL_TYPE), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$slug}_modele.csv\"",
        ]);
    }

    /** @return array{0: class-string, 1: string} */
    private function resolve(string $slug): array
    {
        $cfg = Referentiels::resolve($slug);
        abort_if($cfg === null, 404);

        return [$cfg['model'], $cfg['label']];
    }
}
