<?php

namespace App\Domain\Metadata;

use App\Models\Control;
use App\Models\Partner;
use App\Models\Rootstock;
use App\Models\Segment;
use App\Models\Supplier;
use App\Models\Variety;

/**
 * Registry of metadata-driven référentiels. One generic controller + one page serve every entity
 * here; adding a new référentiel is a registry line + a thin model + seeded field_definitions.
 */
class Referentiels
{
    public const MAP = [
        'fournisseurs' => ['model' => Supplier::class, 'label' => 'Fournisseurs', 'singular' => 'fournisseur'],
        'varietes' => ['model' => Variety::class, 'label' => 'Variétés', 'singular' => 'variété'],
        'temoins' => ['model' => Control::class, 'label' => 'Témoins', 'singular' => 'témoin'],
        'porte-greffes' => ['model' => Rootstock::class, 'label' => 'Porte-greffes', 'singular' => 'porte-greffe'],
        'partenaires' => ['model' => Partner::class, 'label' => 'Partenaires', 'singular' => 'partenaire'],
        'segments' => ['model' => Segment::class, 'label' => 'Segments', 'singular' => 'segment'],
    ];

    /** @return array{model:class-string, label:string, singular:string}|null */
    public static function resolve(string $slug): ?array
    {
        return self::MAP[$slug] ?? null;
    }

    /** @return array<int, array{slug:string, label:string}> */
    public static function tabs(): array
    {
        return array_map(fn ($slug, $cfg) => ['slug' => $slug, 'label' => $cfg['label']], array_keys(self::MAP), self::MAP);
    }

    /** Resolve a model_type (e.g. "supplier") to its Eloquent class, or null if not a référentiel. */
    public static function modelClassFor(string $modelType): ?string
    {
        foreach (self::MAP as $cfg) {
            if ($cfg['model']::MODEL_TYPE === $modelType) {
                return $cfg['model'];
            }
        }

        return null;
    }
}
