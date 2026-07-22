import { ExternalLink, Pencil, Trash2 } from 'lucide-react';
import { type FieldDef, type WithCustomData, displayValue, fieldValue, isReferenceLike, usesEntityOptions } from '@/lib/fields';
import { useContentLocale } from '@/i18n/contentLocale';
import DataTable, { type Column, type FilterType } from './DataTable';

/** Map a field type to the matching DataTable filter (min/max, select, date range, or contains). */
function filterFor(f: FieldDef): { filterType: FilterType; filterOptions?: string[]; aggregate?: 'sum' } {
    if (['number', 'integer', 'decimal', 'rating'].includes(f.type)) return { filterType: 'number', aggregate: f.type === 'rating' ? undefined : 'sum' };
    if (['date', 'datetime'].includes(f.type)) return { filterType: 'date' };
    if (f.type === 'boolean') return { filterType: 'select', filterOptions: ['Oui', 'Non'] };
    if (f.type === 'select' && !usesEntityOptions(f)) return { filterType: 'select', filterOptions: (f.options ?? []).map((o) => o.label) };
    return { filterType: 'text' };
}

type DisplayMap = Record<string | number, Record<string, string>>;

type Props = {
    fields: FieldDef[];
    rows: WithCustomData[];
    display?: DisplayMap;
    onEdit?: (row: WithCustomData) => void;
    onDelete?: (row: WithCustomData) => void;
};

/** Metadata-driven table: builds power-table columns from field_definitions (search/sort/paginate/
 *  column control all come from DataTable), and keeps reference-label display + row actions. */
export default function DynamicTable({ fields, rows, display = {}, onEdit, onDelete }: Props) {
    const { locale } = useContentLocale();
    const cols = fields.filter((f) => f.show_in_table);
    const first = cols[0]?.key;
    const label = (row: WithCustomData, f: FieldDef) => displayValue(row, f, locale, display[row.id as number]?.[f.key]);

    const columns: Column<WithCustomData>[] = cols.map((f) => ({
        key: f.key,
        label: f.is_system ? f.label : `${f.label} •`,
        ...filterFor(f),
        align: ['number', 'integer', 'decimal'].includes(f.type) ? ('right' as const) : undefined,
        value: (row) => (['number', 'integer', 'decimal', 'rating'].includes(f.type) ? (fieldValue(row, f) as number) : label(row, f)),
        render: (row) => {
            const raw = fieldValue(row, f);
            if (f.type === 'url' && raw) {
                return (
                    <a href={String(raw)} target="_blank" rel="noreferrer" className="inline-flex items-center gap-1 text-accent hover:underline">
                        Lien <ExternalLink size={12} />
                    </a>
                );
            }
            const value = label(row, f);
            if (f.key === first && !isReferenceLike(f)) return <span className="font-medium text-ink">{value}</span>;
            return value || '—';
        },
    }));

    const modelType = fields[0]?.model_type ?? 'entity';
    const actions = onEdit || onDelete
        ? (row: WithCustomData) => (
            <div className="flex justify-end gap-1 opacity-60 transition-opacity group-hover:opacity-100">
                {onEdit && <button onClick={() => onEdit(row)} className="rounded-md p-1.5 transition-colors hover:bg-page" aria-label="Modifier"><Pencil size={15} /></button>}
                {onDelete && <button onClick={() => onDelete(row)} className="rounded-md p-1.5 text-danger transition-colors hover:bg-danger-soft" aria-label="Supprimer"><Trash2 size={15} /></button>}
            </div>
        )
        : undefined;

    return <DataTable id={`ref-${modelType}`} columns={columns} rows={rows} rowKey={(row, i) => (row.id as number) ?? i} actions={actions} />;
}
