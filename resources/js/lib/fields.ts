export type FieldOption = { value: string; label: string };

export type FieldDef = {
    id: number;
    model_type: string;
    key: string;
    label: string;
    type: 'text' | 'textarea' | 'number' | 'integer' | 'decimal' | 'email' | 'url' | 'tel' | 'select' | 'multiselect' | 'reference' | 'boolean' | 'date' | 'datetime' | 'color' | 'rating' | 'icon' | 'media';
    options: FieldOption[] | null;
    settings: any;
    required: boolean;
    translatable: boolean;
    is_system: boolean;
    is_unique?: boolean;
    is_primary?: boolean;
    show_in_table: boolean;
    help_text: string | null;
    sort_order: number;
};

export type WithCustomData = { id?: number | string; custom_data?: Record<string, unknown> | null } & Record<string, unknown>;

/** True when a select/multiselect sources its options from a référentiel entity instead of a static list. */
export function usesEntityOptions(f: FieldDef): boolean {
    return (f.type === 'select' || f.type === 'multiselect') && f.settings?.option_source === 'entity' && !!f.settings?.reference_model;
}

/** True when the field points at other records (reference, or entity-sourced select/multiselect). */
export function isReferenceLike(f: FieldDef): boolean {
    return f.type === 'reference' || usesEntityOptions(f);
}

/** True when the value is a list. */
export function isMultiValue(f: FieldDef): boolean {
    return f.type === 'multiselect' || (f.type === 'reference' && !!f.settings?.multiple);
}

/** Resolve a field's value from a record: system fields are top-level, custom fields live in custom_data. */
export function fieldValue(row: WithCustomData, f: FieldDef): unknown {
    return f.is_system ? row[f.key] : row.custom_data?.[f.key];
}

/** Resolve a translatable value ({locale: value}) to the active locale, falling back to any set locale. */
export function resolveTranslatable(value: unknown, locale: string): string {
    if (value && typeof value === 'object' && !Array.isArray(value)) {
        const obj = value as Record<string, unknown>;
        const hit = obj[locale] ?? Object.values(obj).find((v) => v !== null && v !== undefined && v !== '');
        return hit === null || hit === undefined ? '' : String(hit);
    }
    return value === null || value === undefined ? '' : String(value);
}

/** Human-readable display for a value given its field type + active content locale.
 *  `refLabel` is a pre-resolved label for reference-like fields (from the server display map). */
export function displayValue(row: WithCustomData, f: FieldDef, locale = 'fr', refLabel?: string): string {
    const v = fieldValue(row, f);
    if (f.translatable) {
        const r = resolveTranslatable(v, locale);
        return r === '' ? '—' : r;
    }
    if (isReferenceLike(f)) return refLabel && refLabel !== '' ? refLabel : '—';
    if (v === null || v === undefined || v === '' || (Array.isArray(v) && v.length === 0)) return '—';
    if (f.type === 'multiselect') return (v as string[]).map((x) => f.options?.find((o) => o.value === x)?.label ?? x).join(', ');
    if (f.type === 'select') return f.options?.find((o) => o.value === v)?.label ?? String(v);
    if (f.type === 'boolean') return v ? 'Oui' : 'Non';
    return String(v);
}
