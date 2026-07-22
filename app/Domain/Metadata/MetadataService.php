<?php

namespace App\Domain\Metadata;

use App\Models\FieldDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Builds validation rules and splits input into system columns vs custom_data,
 * driven entirely by field_definitions. Adding a custom field makes it validated
 * and persisted with no code change (SPEC §3.1).
 */
class MetadataService
{
    /** @return array<int, string> Enabled content locales (SPEC §3.5). */
    public static function locales(): array
    {
        return array_values(config('agri.locales', ['fr']));
    }

    public static function defaultLocale(): string
    {
        return config('agri.default_locale', 'fr');
    }

    /**
     * Laravel validation rules keyed by field key.
     *
     * @param  string|null  $modelClass  the owning model (enables `unique` enforcement)
     * @param  array<int, string>  $systemFields  which keys are real columns vs custom_data
     * @return array<string, mixed>
     */
    public static function rules(Collection $fields, ?string $modelClass = null, array $systemFields = [], int|string|null $ignoreId = null): array
    {
        $rules = [];
        $default = self::defaultLocale();

        foreach ($fields as $f) {
            $typeRule = self::typeRule($f);
            $inRule = self::optionRule($f);

            // Multi-value fields validate the container as an array and each element separately.
            if ($f->isMultiValue()) {
                $rules[$f->key] = [$f->required ? 'required' : 'nullable', 'array'];
                $element = ['nullable'];
                if ($f->isReferenceLike() && ($table = self::tableFor((string) ($f->settings['reference_model'] ?? '')))) {
                    $element[] = Rule::exists($table, 'id');
                } elseif ($inRule) {
                    $element[] = $inRule;
                }
                $rules["{$f->key}.*"] = $element;

                continue;
            }

            if ($f->translatable) {
                foreach (self::locales() as $locale) {
                    $r = [($f->required && $locale === $default) ? 'required' : 'nullable', $typeRule];
                    if ($inRule) {
                        $r[] = $inRule;
                    }
                    $rules["{$f->key}.{$locale}"] = $r;
                }

                continue;
            }

            $r = [$f->required ? 'required' : 'nullable', $typeRule];
            if ($inRule) {
                $r[] = $inRule;
            }
            // Single entity reference → the value must exist in the target table.
            if ($f->isReferenceLike() && ($table = self::tableFor((string) ($f->settings['reference_model'] ?? '')))) {
                $r[] = Rule::exists($table, 'id');
            }
            if ($f->is_unique && $modelClass) {
                $r[] = self::uniqueRule($f, $modelClass, in_array($f->key, $systemFields, true), $ignoreId);
            }
            $rules[$f->key] = $r;
        }

        return $rules;
    }

    private static function typeRule(FieldDefinition $f): string
    {
        return match ($f->type) {
            'number', 'decimal', 'rating' => 'numeric',
            'integer' => 'integer',
            'email' => 'email',
            'url' => 'url',
            'date', 'datetime' => 'date',
            'boolean' => 'boolean',
            'media', 'reference' => 'integer',
            'select' => $f->usesEntityOptions() ? 'integer' : 'string',
            default => 'string',
        };
    }

    /** `in:` rule for static select/multiselect; null for entity-sourced or free fields. */
    private static function optionRule(FieldDefinition $f): ?string
    {
        if (! in_array($f->type, ['select', 'multiselect'], true) || $f->usesEntityOptions() || ! is_array($f->options)) {
            return null;
        }

        return 'in:'.implode(',', array_map(fn ($o) => $o['value'], $f->options));
    }

    /** Uniqueness rule: a real column for system fields, a JSONB probe for custom fields. */
    private static function uniqueRule(FieldDefinition $f, string $modelClass, bool $isSystem, int|string|null $ignoreId): mixed
    {
        if ($isSystem) {
            $rule = Rule::unique((new $modelClass)->getTable(), $f->key);

            return $ignoreId ? $rule->ignore($ignoreId) : $rule;
        }

        $key = $f->key;

        return function ($attribute, $value, $fail) use ($modelClass, $key, $ignoreId) {
            if ($value === null || $value === '') {
                return;
            }
            $query = $modelClass::query()->whereRaw('custom_data->>? = ?', [$key, (string) $value]);
            if ($ignoreId) {
                $query->whereKeyNot($ignoreId);
            }
            if ($query->exists()) {
                $fail('Cette valeur est déjà utilisée.');
            }
        };
    }

    /**
     * Split validated input into [systemAttributes, customData] using the model's system field list.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $systemFields
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    public static function split(array $data, Collection $fields, array $systemFields): array
    {
        $attrs = [];
        $custom = [];
        foreach ($fields as $f) {
            $value = $data[$f->key] ?? null;
            if (in_array($f->key, $systemFields, true)) {
                $attrs[$f->key] = $value;
            } else {
                $custom[$f->key] = $value;
            }
        }

        return [$attrs, $custom];
    }

    /**
     * Auto-generate slug fields (settings.slug_from) from their source field when left empty.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function applySlugs(array $data, Collection $fields): array
    {
        foreach ($fields as $f) {
            $source = $f->settings['slug_from'] ?? null;
            if (! $source || ! empty($data[$f->key])) {
                continue;
            }
            $raw = $data[$source] ?? null;
            if (is_array($raw)) {
                $raw = $raw[self::defaultLocale()] ?? collect($raw)->first();
            }
            if ($raw) {
                $data[$f->key] = Str::slug((string) $raw);
            }
        }

        return $data;
    }

    public static function fieldsFor(string $modelType): Collection
    {
        return FieldDefinition::for($modelType);
    }

    public static function tableFor(string $modelType): ?string
    {
        $class = Referentiels::modelClassFor($modelType);

        return $class ? (new $class)->getTable() : null;
    }

    /**
     * Resolve reference ids → human labels for a given field, for display in tables/detail views.
     *
     * @param  array<int, int|string>  $ids
     * @return array<int|string, string>
     */
    public static function labelsFor(string $modelType, array $ids): array
    {
        $class = Referentiels::modelClassFor($modelType);
        $ids = array_values(array_filter($ids, fn ($id) => $id !== null && $id !== ''));
        if (! $class || empty($ids)) {
            return [];
        }

        return $class::whereIn((new $class)->getKeyName(), $ids)->get()
            ->mapWithKeys(fn ($record) => [$record->getKey() => self::recordLabel($record)])
            ->all();
    }

    /**
     * Lookup index for resolving import cells to reference ids: numeric ids and case-insensitive labels.
     *
     * @return array{ids: array<int|string, int>, labels: array<string, int>}
     */
    public static function referenceIndex(string $modelType): array
    {
        $class = Referentiels::modelClassFor($modelType);
        if (! $class) {
            return ['ids' => [], 'labels' => []];
        }
        $ids = [];
        $labels = [];
        foreach ($class::all() as $record) {
            $key = $record->getKey();
            $ids[(string) $key] = $key;
            $labels[mb_strtolower(self::recordLabel($record))] = $key;
        }

        return ['ids' => $ids, 'labels' => $labels];
    }

    /**
     * Resolve one import token (an id or a label) to a reference id, or the raw token if unresolved
     * (so the downstream `exists` rule reports a clear error).
     *
     * @param  array{ids: array<int|string, int>, labels: array<string, int>}  $index
     */
    public static function resolveReferenceToken(array $index, ?string $token): int|string|null
    {
        $token = trim((string) $token);
        if ($token === '') {
            return null;
        }
        if (isset($index['ids'][$token])) {
            return $index['ids'][$token];
        }

        return $index['labels'][mb_strtolower($token)] ?? $token;
    }

    public static function recordLabel(object $record): string
    {
        $raw = $record->name ?? $record->code ?? null;
        if (is_array($raw)) {
            return (string) ($raw[self::defaultLocale()] ?? collect($raw)->first() ?? '#'.$record->getKey());
        }

        return (string) ($raw ?? $record->code ?? '#'.$record->getKey());
    }

    /**
     * Build a per-row display map { rowKey: { fieldKey: label } } resolving reference-like fields to labels.
     *
     * @return array<int|string, array<string, string>>
     */
    public static function displayMap(Collection $rows, Collection $fields): array
    {
        $refFields = $fields->filter(fn ($f) => $f->isReferenceLike());
        if ($refFields->isEmpty() || $rows->isEmpty()) {
            return [];
        }

        // Preload labels per referenced model in one query each.
        $labels = [];
        foreach ($refFields as $f) {
            $model = (string) ($f->settings['reference_model'] ?? '');
            $ids = [];
            foreach ($rows as $row) {
                $val = $f->is_system ? ($row->{$f->key} ?? null) : (($row->custom_data ?? [])[$f->key] ?? null);
                foreach ((array) $val as $id) {
                    $ids[] = $id;
                }
            }
            $labels[$f->key] = self::labelsFor($model, $ids);
        }

        $map = [];
        foreach ($rows as $row) {
            $rowLabels = [];
            foreach ($refFields as $f) {
                $val = $f->is_system ? ($row->{$f->key} ?? null) : (($row->custom_data ?? [])[$f->key] ?? null);
                $resolved = array_map(fn ($id) => $labels[$f->key][$id] ?? ('#'.$id), array_filter((array) $val, fn ($v) => $v !== null && $v !== ''));
                $rowLabels[$f->key] = implode(', ', $resolved);
            }
            $map[$row->getKey()] = $rowLabels;
        }

        return $map;
    }
}
