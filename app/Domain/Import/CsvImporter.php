<?php

namespace App\Domain\Import;

use App\Domain\Metadata\MetadataService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Metadata-driven CSV import (SPEC §3.6). Column mapping and per-row validation are derived from
 * field_definitions, so any entity — and any custom field — is importable with no bespoke code.
 *
 * The engine runs in two modes over the same parser: `analyze()` validates every row without
 * persisting (preview-before-commit), and `commit()` persists the valid rows. Reference-like fields
 * accept ids or labels; multi-value cells are `;`-separated.
 */
class CsvImporter
{
    /** Rows returned for the preview table are capped; counts always cover the whole file. */
    public const PREVIEW_LIMIT = 200;

    private const MULTI_DELIMITER = ';';

    /**
     * Dry-run: validate every row and return a preview without touching the database.
     *
     * @return array{columns:array<int,string>, total:int, imported:int, failed:int, errors:array<int,array{line:int,errors:array<int,string>}>, rows:array<int,array<string,mixed>>}
     */
    public static function analyze(string $modelClass, string $modelType, array $systemFields, string $uniqueKey, string $path): array
    {
        return self::run($modelClass, $modelType, $systemFields, $uniqueKey, $path, commit: false);
    }

    /**
     * Persist the valid rows of the file. Returns the same counts shape (without the preview rows).
     *
     * @return array{total:int, imported:int, failed:int, errors:array<int,array{line:int,errors:array<int,string>}>}
     */
    public static function commit(string $modelClass, string $modelType, array $systemFields, string $uniqueKey, string $path): array
    {
        $result = self::run($modelClass, $modelType, $systemFields, $uniqueKey, $path, commit: true);
        unset($result['rows'], $result['columns']);

        return $result;
    }

    private static function run(string $modelClass, string $modelType, array $systemFields, string $uniqueKey, string $path, bool $commit): array
    {
        $fields = MetadataService::fieldsFor($modelType);

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['columns' => [], 'total' => 0, 'imported' => 0, 'failed' => 1, 'errors' => [['line' => 0, 'errors' => ['Fichier illisible.']]], 'rows' => []];
        }

        $header = fgetcsv($handle) ?: [];
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]); // strip UTF-8 BOM
        }

        $locales = MetadataService::locales();
        $default = MetadataService::defaultLocale();
        $map = self::columnMap($header, $fields, $locales, $default);

        // Preload reference lookup indexes once so label→id resolution is cheap per row.
        $refIndexes = [];
        foreach ($fields as $f) {
            if ($f->isReferenceLike() && ($model = $f->settings['reference_model'] ?? null)) {
                $refIndexes[$f->key] = MetadataService::referenceIndex((string) $model);
            }
        }

        $columns = collect($map)->pluck('key')->unique()->values()->all();
        $imported = 0;
        $errors = [];
        $rows = [];
        $line = 1;

        while (($raw = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($raw, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue; // skip blank lines
            }

            [$data, $display] = self::buildRow($raw, $map, $fields, $refIndexes);

            $uniqueValue = $data[$uniqueKey] ?? null;
            $existing = ($uniqueValue !== null && $uniqueValue !== '') ? $modelClass::where($uniqueKey, $uniqueValue)->first() : null;
            $rules = MetadataService::rules($fields, $modelClass, $systemFields, $existing?->getKey());

            $validator = Validator::make($data, $rules);
            $valid = ! $validator->fails();

            if (! $valid) {
                $errors[] = ['line' => $line, 'errors' => $validator->errors()->all()];
            } else {
                if ($commit) {
                    $clean = MetadataService::applySlugs($validator->validated(), $fields);
                    [$attrs, $custom] = MetadataService::split($clean, $fields, $systemFields);
                    $attrs['custom_data'] = array_merge($existing?->custom_data ?? [], $custom);
                    $modelClass::updateOrCreate([$uniqueKey => $attrs[$uniqueKey] ?? null], $attrs);
                }
                $imported++;
            }

            if (! $commit && count($rows) < self::PREVIEW_LIMIT) {
                $rows[] = [
                    'line' => $line,
                    'values' => $display,
                    'valid' => $valid,
                    'action' => $existing ? 'update' : 'create',
                    'errors' => $valid ? [] : $validator->errors()->all(),
                ];
            }
        }
        fclose($handle);

        return [
            'columns' => $columns,
            'total' => $imported + count($errors),
            'imported' => $imported,
            'failed' => count($errors),
            'errors' => array_slice($errors, 0, 10),
            'rows' => $rows,
        ];
    }

    /**
     * Map each column index to a field key (+ locale for translatable fields). Translatable columns
     * may be "key.locale" (e.g. description.en); a plain "key"/"label" column maps to the default locale.
     *
     * @return array<int, array{key:string, locale:?string}>
     */
    private static function columnMap(array $header, Collection $fields, array $locales, string $default): array
    {
        $map = [];
        foreach ($header as $i => $col) {
            $col = trim((string) $col);
            foreach ($fields as $f) {
                if ($f->translatable) {
                    foreach ($locales as $loc) {
                        if (strcasecmp($col, "{$f->key}.{$loc}") === 0) {
                            $map[$i] = ['key' => $f->key, 'locale' => $loc];
                            break 2;
                        }
                    }
                    if (strcasecmp($col, $f->key) === 0 || strcasecmp($col, $f->label) === 0) {
                        $map[$i] = ['key' => $f->key, 'locale' => $default];
                        break;
                    }
                } elseif (strcasecmp($col, $f->key) === 0 || strcasecmp($col, $f->label) === 0) {
                    $map[$i] = ['key' => $f->key, 'locale' => null];
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Turn a raw CSV row into [validationData, displayValues], resolving reference labels to ids and
     * splitting multi-value cells.
     *
     * @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    private static function buildRow(array $raw, array $map, Collection $fields, array $refIndexes): array
    {
        $byKey = $fields->keyBy('key');
        $data = [];
        $display = [];
        foreach ($map as $i => $m) {
            $cell = $raw[$i] ?? null;
            $cell = ($cell === '' ? null : $cell);
            $display[$m['key']] = (string) ($cell ?? '');

            if ($m['locale'] !== null) {
                $data[$m['key']][$m['locale']] = $cell;

                continue;
            }

            $f = $byKey->get($m['key']);
            if ($f && isset($refIndexes[$m['key']])) {
                $tokens = $cell === null ? [] : array_filter(array_map('trim', explode(self::MULTI_DELIMITER, (string) $cell)), fn ($t) => $t !== '');
                $resolved = array_map(fn ($t) => MetadataService::resolveReferenceToken($refIndexes[$m['key']], $t), $tokens);
                $data[$m['key']] = $f->isMultiValue() ? array_values($resolved) : ($resolved[0] ?? null);
            } else {
                $data[$m['key']] = $cell;
            }
        }

        return [$data, $display];
    }

    /** CSV template header row for a model type (translatable fields expand to key.locale columns). */
    public static function template(string $modelType): string
    {
        $locales = MetadataService::locales();
        $cols = [];
        foreach (MetadataService::fieldsFor($modelType) as $f) {
            if ($f->translatable) {
                foreach ($locales as $loc) {
                    $cols[] = "{$f->key}.{$loc}";
                }
            } else {
                $cols[] = $f->key;
            }
        }

        return implode(',', $cols)."\n";
    }
}
