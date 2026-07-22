<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = Activity::with('causer:id,name')
            ->latest()
            ->paginate(30)
            ->through(function (Activity $a) {
                // Model-event changes live in `attribute_changes` in this activitylog version;
                // `properties` only holds manually-attached custom properties.
                $attrs = (array) data_get($a->attribute_changes, 'attributes', []);
                $old = (array) data_get($a->attribute_changes, 'old', []);
                $keys = array_values(array_unique([...array_keys($attrs), ...array_keys($old)]));

                return [
                    'id' => $a->id,
                    'event' => $a->event ?? $a->description,
                    'subject_type' => class_basename((string) $a->subject_type),
                    'subject_id' => $a->subject_id,
                    'subject_label' => $attrs['name'] ?? $attrs['code'] ?? $attrs['label'] ?? null,
                    'causer' => $a->causer?->name,
                    'changed' => $keys,
                    'attributes' => $attrs,
                    'old' => $old,
                    'created_at' => $a->created_at?->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Audit/Index', [
            'activities' => $activities,
        ]);
    }
}
