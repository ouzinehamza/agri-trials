<?php

namespace App\Http\Controllers;

use App\Domain\Metadata\Referentiels;
use App\Models\FieldDefinition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class ConfigurationController extends Controller
{
    private const TYPES = ['text', 'textarea', 'number', 'integer', 'decimal', 'email', 'url', 'tel', 'select', 'multiselect', 'reference', 'boolean', 'date', 'datetime', 'color', 'rating', 'icon', 'media'];

    public function index(Request $request): Response
    {
        $models = collect(Referentiels::MAP)->map(fn ($cfg, $slug) => [
            'model_type' => $cfg['model']::MODEL_TYPE,
            'label' => $cfg['label'],
        ])->values();

        $active = $request->query('model', $models->first()['model_type']);
        abort_unless($models->contains('model_type', $active), 404);

        return Inertia::render('Configuration/Index', [
            'models' => $models,
            'active' => $active,
            'fields' => FieldDefinition::where('model_type', $active)->orderBy('sort_order')->get(),
            'types' => collect(self::TYPES)->map(fn ($t) => ['value' => $t, 'label' => $t]),
            'referenceModels' => $models,
        ]);
    }

    public function storeField(Request $request): RedirectResponse
    {
        $modelTypes = collect(Referentiels::MAP)->map(fn ($c) => $c['model']::MODEL_TYPE)->all();

        $data = $request->validate([
            'model_type' => ['required', Rule::in($modelTypes)],
            'key' => ['required', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('field_definitions', 'key')->where('model_type', $request->model_type)],
            'label' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(self::TYPES)],
            'required' => ['boolean'],
            'translatable' => ['boolean'],
            'is_unique' => ['boolean'],
            'is_primary' => ['boolean'],
            'show_in_table' => ['boolean'],
            'help_text' => ['nullable', 'string', 'max:160'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        $data['is_system'] = false;
        $data['sort_order'] = (int) FieldDefinition::where('model_type', $data['model_type'])->max('sort_order') + 1;
        $field = FieldDefinition::create($data);
        $this->enforceSinglePrimary($field);

        return redirect()->route('configuration.index', ['model' => $data['model_type']])->with('success', 'Champ ajouté.');
    }

    public function updateField(Request $request, FieldDefinition $fieldDefinition): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(self::TYPES)],
            'required' => ['boolean'],
            'translatable' => ['boolean'],
            'is_unique' => ['boolean'],
            'is_primary' => ['boolean'],
            'show_in_table' => ['boolean'],
            'help_text' => ['nullable', 'string', 'max:160'],
            'options' => ['nullable', 'array'],
            'options.*.value' => ['required_with:options', 'string'],
            'options.*.label' => ['required_with:options', 'string'],
            'settings' => ['nullable', 'array'],
        ]);

        // System fields: type/key are locked, but constraints & presentation stay editable.
        if ($fieldDefinition->is_system) {
            $data = array_intersect_key($data, array_flip(['label', 'show_in_table', 'help_text', 'is_unique', 'is_primary', 'settings']));
        }

        $fieldDefinition->update($data);
        $this->enforceSinglePrimary($fieldDefinition);

        return redirect()->route('configuration.index', ['model' => $fieldDefinition->model_type])->with('success', 'Champ mis à jour.');
    }

    /** At most one primary/display field per model. */
    private function enforceSinglePrimary(FieldDefinition $field): void
    {
        if (! $field->is_primary) {
            return;
        }
        FieldDefinition::where('model_type', $field->model_type)
            ->whereKeyNot($field->getKey())
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    public function destroyField(FieldDefinition $fieldDefinition): RedirectResponse
    {
        abort_if($fieldDefinition->is_system, 403, 'Un champ système ne peut pas être supprimé.');
        $model = $fieldDefinition->model_type;
        $fieldDefinition->delete();

        return redirect()->route('configuration.index', ['model' => $model])->with('success', 'Champ supprimé.');
    }

    public function referenceOptions(Request $request, string $modelType): JsonResponse
    {
        $entry = collect(Referentiels::MAP)->first(fn ($cfg) => $cfg['model']::MODEL_TYPE === $modelType);
        abort_unless($entry, 404);
        $term = trim((string) $request->query('q'));
        $options = $entry['model']::query()->limit(200)->get()->map(function ($record) { $raw = $record->name ?? $record->commercial_name ?? null; $label = is_array($raw) ? ($raw[config('agri.default_locale')] ?? collect($raw)->first()) : ($raw ?? $record->code ?? '#'.$record->getKey()); return ['value' => $record->getKey(), 'label' => $label, 'meta' => $record->code ?? $record->ref_code ?? null]; });
        if ($term !== '') $options = $options->filter(fn ($option) => str_contains(mb_strtolower($option['label'].' '.$option['meta']), mb_strtolower($term)));
        return response()->json($options->take(40)->values());
    }
}
