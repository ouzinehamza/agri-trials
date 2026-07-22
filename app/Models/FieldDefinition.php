<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldDefinition extends Model
{
    use \App\Models\Concerns\Auditable;
    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
        'settings' => 'array',
        'required' => 'boolean',
        'translatable' => 'boolean',
        'is_system' => 'boolean',
        'is_unique' => 'boolean',
        'is_primary' => 'boolean',
        'show_in_table' => 'boolean',
    ];

    public static function for(string $modelType)
    {
        return static::where('model_type', $modelType)->orderBy('sort_order')->get();
    }

    /** True when this select/multiselect sources its options from a référentiel entity, not a static list. */
    public function usesEntityOptions(): bool
    {
        return in_array($this->type, ['select', 'multiselect'], true)
            && ($this->settings['option_source'] ?? 'static') === 'entity'
            && ! empty($this->settings['reference_model']);
    }

    /** True when this field holds one or many references to another model (reference, or entity-sourced select). */
    public function isReferenceLike(): bool
    {
        return $this->type === 'reference' || $this->usesEntityOptions();
    }

    /** True when the value is a list (multiselect, or a multi-reference). */
    public function isMultiValue(): bool
    {
        return $this->type === 'multiselect'
            || ($this->type === 'reference' && ! empty($this->settings['multiple']));
    }
}
