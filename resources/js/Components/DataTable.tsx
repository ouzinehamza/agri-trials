import { type ReactNode, useEffect, useMemo, useRef, useState } from 'react';
import { ArrowDown, ArrowUp, ChevronDown, ChevronLeft, ChevronRight, ChevronUp, Columns3, Filter, GripVertical, Search, X } from 'lucide-react';
import { cn } from '@/lib/cn';

export type FilterType = 'text' | 'number' | 'select' | 'date' | 'none';

export type Column<T> = {
    key: string;
    label: string;
    /** Plain value used for search / sort / filter / aggregation. */
    value?: (row: T) => string | number | null | undefined;
    render?: (row: T) => ReactNode;
    align?: 'left' | 'right';
    sortable?: boolean;
    filterType?: FilterType;
    filterOptions?: string[];
    /** Show a column total in the footer ('sum' | 'avg'). Implies a numeric column. */
    aggregate?: 'sum' | 'avg';
    defaultHidden?: boolean;
};

type Range = { min?: string; max?: string };
type DateRange = { from?: string; to?: string };
type FilterVal = string | Range | DateRange;
type Prefs = { order: string[]; hidden: string[]; pageSize: number };

type Props<T> = {
    id: string;
    columns: Column<T>[];
    rows: T[];
    rowKey?: (row: T, i: number) => string | number;
    actions?: (row: T) => ReactNode;
    pageSize?: number;
    searchPlaceholder?: string;
    emptyText?: string;
};

function loadPrefs(id: string): Partial<Prefs> {
    try { return JSON.parse(localStorage.getItem(`agri-table-${id}`) || '{}'); } catch { return {}; }
}

const text = <T,>(col: Column<T>, row: T) => String(col.value?.(row) ?? '');
const num = <T,>(col: Column<T>, row: T) => { const v = col.value?.(row); const n = typeof v === 'number' ? v : parseFloat(String(v ?? '').replace(',', '.')); return Number.isFinite(n) ? n : null; };
const fmt = (n: number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(n);

function hasFilter(type: FilterType, v: FilterVal | undefined): boolean {
    if (v == null) return false;
    if (type === 'number') return !!((v as Range).min || (v as Range).max);
    if (type === 'date') return !!((v as DateRange).from || (v as DateRange).to);
    return String(v) !== '';
}

export default function DataTable<T>({ id, columns, rows, rowKey, actions, pageSize = 10, searchPlaceholder = 'Rechercher…', emptyText = 'Aucun enregistrement.' }: Props<T>) {
    const saved = useRef(loadPrefs(id)).current;
    const [order, setOrder] = useState<string[]>(() => {
        const keys = columns.map((c) => c.key);
        const valid = (saved.order ?? []).filter((k) => keys.includes(k));
        return [...valid, ...keys.filter((k) => !valid.includes(k))];
    });
    const [hidden, setHidden] = useState<Set<string>>(() => new Set(saved.hidden ?? columns.filter((c) => c.defaultHidden).map((c) => c.key)));
    const [size, setSize] = useState<number>(saved.pageSize ?? pageSize);
    const [query, setQuery] = useState('');
    const [filters, setFilters] = useState<Record<string, FilterVal>>({});
    const [filtersOn, setFiltersOn] = useState(false);
    const [columnsOpen, setColumnsOpen] = useState(false);
    const [sort, setSort] = useState<{ key: string; dir: 'asc' | 'desc' } | null>(null);
    const [page, setPage] = useState(0);
    const dragKey = useRef<string | null>(null);
    const colRef = useRef<HTMLDivElement>(null);

    useEffect(() => { localStorage.setItem(`agri-table-${id}`, JSON.stringify({ order, hidden: [...hidden], pageSize: size })); }, [id, order, hidden, size]);
    useEffect(() => { setPage(0); }, [query, filters, size, rows]);
    useEffect(() => {
        const close = (e: MouseEvent) => { if (!colRef.current?.contains(e.target as Node)) setColumnsOpen(false); };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, []);

    const byKey = useMemo(() => Object.fromEntries(columns.map((c) => [c.key, c])) as Record<string, Column<T>>, [columns]);
    const visibleCols = useMemo(() => order.map((k) => byKey[k]).filter((c): c is Column<T> => !!c && !hidden.has(c.key)), [order, byKey, hidden]);
    const filterableCols = columns.filter((c) => (c.filterType ?? 'text') !== 'none');
    const activeFilters = Object.entries(filters).filter(([k, v]) => hasFilter(byKey[k]?.filterType ?? 'text', v)).length;

    const matches = (col: Column<T>, row: T, v: FilterVal): boolean => {
        const type = col.filterType ?? 'text';
        if (type === 'number') {
            const { min, max } = v as Range; const n = num(col, row);
            if (n === null) return !min && !max;
            if (min && n < parseFloat(min)) return false;
            if (max && n > parseFloat(max)) return false;
            return true;
        }
        if (type === 'date') {
            const { from, to } = v as DateRange; const d = text(col, row);
            if (from && d < from) return false;
            if (to && d > to) return false;
            return true;
        }
        if (type === 'select') return !v || text(col, row) === String(v);
        return text(col, row).toLowerCase().includes(String(v).toLowerCase());
    };

    const processed = useMemo(() => {
        const q = query.trim().toLowerCase();
        let out = rows.filter((row) => {
            if (q && !columns.some((c) => text(c, row).toLowerCase().includes(q))) return false;
            for (const [key, v] of Object.entries(filters)) {
                const col = byKey[key];
                if (col && hasFilter(col.filterType ?? 'text', v) && !matches(col, row, v)) return false;
            }
            return true;
        });
        if (sort) {
            const col = byKey[sort.key];
            out = [...out].sort((a, b) => {
                const av = col.value?.(a) ?? ''; const bv = col.value?.(b) ?? '';
                const cmp = typeof av === 'number' && typeof bv === 'number' ? av - bv : String(av).localeCompare(String(bv), undefined, { numeric: true });
                return sort.dir === 'asc' ? cmp : -cmp;
            });
        }
        return out;
    }, [rows, columns, query, filters, sort, byKey]);

    const pageCount = Math.max(1, Math.ceil(processed.length / size));
    const current = Math.min(page, pageCount - 1);
    const pageRows = processed.slice(current * size, current * size + size);
    const hasAggregates = visibleCols.some((c) => c.aggregate);

    const toggleSort = (key: string) => setSort((s) => (s?.key !== key ? { key, dir: 'asc' } : s.dir === 'asc' ? { key, dir: 'desc' } : null));
    const move = (key: string, delta: number) => setOrder((o) => { const i = o.indexOf(key); const j = i + delta; if (j < 0 || j >= o.length) return o; const next = [...o]; [next[i], next[j]] = [next[j], next[i]]; return next; });
    const drop = (targetKey: string) => { const from = dragKey.current; dragKey.current = null; if (!from || from === targetKey) return; setOrder((o) => { const next = o.filter((k) => k !== from); next.splice(next.indexOf(targetKey), 0, from); return next; }); };
    const setFilter = (key: string, v: FilterVal) => setFilters((f) => ({ ...f, [key]: v }));

    const inputCls = 'w-full rounded-md border border-line bg-page px-2 py-1 text-xs font-normal normal-case outline-none focus:border-brand';

    return (
        <div>
            <div className="mb-3 flex flex-wrap items-center gap-2">
                <div className="relative min-w-[220px] flex-1">
                    <Search size={15} className="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-ink-faint" />
                    <input value={query} onChange={(e) => setQuery(e.target.value)} placeholder={searchPlaceholder} className="w-full rounded-lg border border-line bg-page py-2 pe-8 ps-9 text-sm outline-none transition-colors focus:border-brand" />
                    {query && <button onClick={() => setQuery('')} className="absolute end-2.5 top-1/2 -translate-y-1/2 text-ink-faint hover:text-ink" aria-label="Effacer"><X size={14} /></button>}
                </div>
                {filterableCols.length > 0 && (
                    <button onClick={() => setFiltersOn((v) => !v)} className={cn('inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors', filtersOn || activeFilters ? 'border-brand/40 bg-brand/5 text-brand' : 'border-line bg-surface text-ink-muted hover:bg-surface-2')}>
                        <Filter size={15} /> Filtres{activeFilters ? ` (${activeFilters})` : ''}
                    </button>
                )}
                <div className="relative" ref={colRef}>
                    <button onClick={() => setColumnsOpen((v) => !v)} className={cn('inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm transition-colors', columnsOpen ? 'border-brand/40 bg-brand/5 text-brand' : 'border-line bg-surface text-ink-muted hover:bg-surface-2')}>
                        <Columns3 size={15} /> Colonnes
                    </button>
                    {columnsOpen && (
                        <div className="absolute end-0 top-full z-40 mt-2 w-64 origin-top rounded-xl border border-line bg-surface p-2 shadow-xl animate-scale-in">
                            <div className="px-2 py-1.5 text-[11px] font-medium uppercase text-ink-faint">Colonnes affichées & ordre</div>
                            {order.map((key, i) => { const c = byKey[key]; if (!c) return null; const visible = !hidden.has(key); return (
                                <div key={key} className="flex items-center gap-1.5 rounded-md px-2 py-1.5 hover:bg-surface-2">
                                    <label className="flex flex-1 cursor-pointer items-center gap-2 text-sm">
                                        <input type="checkbox" checked={visible} onChange={() => setHidden((h) => { const n = new Set(h); n.has(key) ? n.delete(key) : n.add(key); return n; })} />
                                        <span className={cn(!visible && 'text-ink-faint')}>{c.label}</span>
                                    </label>
                                    <button onClick={() => move(key, -1)} disabled={i === 0} className="rounded p-0.5 text-ink-faint hover:text-ink disabled:opacity-30" aria-label="Monter"><ChevronUp size={14} /></button>
                                    <button onClick={() => move(key, 1)} disabled={i === order.length - 1} className="rounded p-0.5 text-ink-faint hover:text-ink disabled:opacity-30" aria-label="Descendre"><ChevronDown size={14} /></button>
                                </div>
                            ); })}
                        </div>
                    )}
                </div>
            </div>

            <div className="scroll-slim overflow-x-auto rounded-lg border border-line bg-surface">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                            {visibleCols.map((c) => (
                                <th key={c.key} draggable onDragStart={() => (dragKey.current = c.key)} onDragOver={(e) => e.preventDefault()} onDrop={() => drop(c.key)} className={cn('group/th whitespace-nowrap px-4 py-3 font-medium', c.align === 'right' && 'text-right')}>
                                    <span className={cn('inline-flex items-center gap-1', c.align === 'right' && 'flex-row-reverse')}>
                                        <GripVertical size={12} className="cursor-grab text-ink-faint opacity-0 transition-opacity group-hover/th:opacity-60" />
                                        <button onClick={() => c.sortable !== false && toggleSort(c.key)} className={cn('inline-flex items-center gap-1 uppercase', c.sortable !== false && 'hover:text-ink')}>
                                            {c.label}
                                            {sort?.key === c.key && (sort.dir === 'asc' ? <ArrowUp size={12} /> : <ArrowDown size={12} />)}
                                        </button>
                                    </span>
                                </th>
                            ))}
                            {actions && <th className="w-px whitespace-nowrap px-4 py-3 text-right font-medium">Actions</th>}
                        </tr>
                        {filtersOn && (
                            <tr className="border-b border-line bg-surface-2/50">
                                {visibleCols.map((c) => {
                                    const type = c.filterType ?? 'text';
                                    const v = filters[c.key];
                                    return (
                                        <th key={c.key} className="px-3 py-2 align-top">
                                            {type === 'number' ? (
                                                <div className="flex gap-1">
                                                    <input inputMode="decimal" value={(v as Range)?.min ?? ''} onChange={(e) => setFilter(c.key, { ...(v as Range), min: e.target.value })} placeholder="min" className={inputCls} />
                                                    <input inputMode="decimal" value={(v as Range)?.max ?? ''} onChange={(e) => setFilter(c.key, { ...(v as Range), max: e.target.value })} placeholder="max" className={inputCls} />
                                                </div>
                                            ) : type === 'date' ? (
                                                <div className="flex gap-1">
                                                    <input type="date" value={(v as DateRange)?.from ?? ''} onChange={(e) => setFilter(c.key, { ...(v as DateRange), from: e.target.value })} className={inputCls} />
                                                    <input type="date" value={(v as DateRange)?.to ?? ''} onChange={(e) => setFilter(c.key, { ...(v as DateRange), to: e.target.value })} className={inputCls} />
                                                </div>
                                            ) : type === 'select' ? (
                                                <select value={(v as string) ?? ''} onChange={(e) => setFilter(c.key, e.target.value)} className={inputCls}>
                                                    <option value="">Tous</option>
                                                    {(c.filterOptions ?? []).map((o) => <option key={o} value={o}>{o}</option>)}
                                                </select>
                                            ) : type === 'none' ? null : (
                                                <input value={(v as string) ?? ''} onChange={(e) => setFilter(c.key, e.target.value)} placeholder="contient…" className={inputCls} />
                                            )}
                                        </th>
                                    );
                                })}
                                {actions && <th className="px-3 py-2 text-right align-top">{activeFilters > 0 && <button onClick={() => setFilters({})} className="text-[11px] text-ink-faint hover:text-danger">effacer</button>}</th>}
                            </tr>
                        )}
                    </thead>
                    <tbody>
                        {pageRows.map((row, i) => (
                            <tr key={rowKey?.(row, i) ?? i} className="group border-b border-line transition-colors last:border-0 hover:bg-surface-2">
                                {visibleCols.map((c) => (
                                    <td key={c.key} className={cn('whitespace-nowrap px-4 py-3 text-ink-muted', c.align === 'right' && 'text-right')}>
                                        {c.render ? c.render(row) : text(c, row) || '—'}
                                    </td>
                                ))}
                                {actions && <td className="whitespace-nowrap px-4 py-3 text-right">{actions(row)}</td>}
                            </tr>
                        ))}
                        {pageRows.length === 0 && (
                            <tr><td colSpan={visibleCols.length + (actions ? 1 : 0)} className="px-4 py-12 text-center text-ink-faint">{query || activeFilters ? 'Aucun résultat.' : emptyText}</td></tr>
                        )}
                    </tbody>
                    {hasAggregates && processed.length > 0 && (
                        <tfoot>
                            <tr className="border-t-2 border-line bg-surface-2/60 text-xs font-medium text-ink">
                                {visibleCols.map((c, i) => {
                                    if (!c.aggregate) return <td key={c.key} className="px-4 py-2.5 text-ink-faint">{i === 0 ? `Total (${processed.length})` : ''}</td>;
                                    const nums = processed.map((r) => num(c, r)).filter((n): n is number => n !== null);
                                    const sum = nums.reduce((a, b) => a + b, 0);
                                    const val = c.aggregate === 'avg' ? (nums.length ? sum / nums.length : 0) : sum;
                                    return <td key={c.key} className={cn('px-4 py-2.5', c.align === 'right' && 'text-right')}>{fmt(val)}{c.aggregate === 'avg' ? ' (moy.)' : ''}</td>;
                                })}
                                {actions && <td />}
                            </tr>
                        </tfoot>
                    )}
                </table>
            </div>

            <div className="mt-3 flex flex-wrap items-center justify-between gap-3 text-sm text-ink-muted">
                <div className="flex items-center gap-2">
                    <span>{processed.length} résultat(s)</span>
                    <select value={size} onChange={(e) => setSize(Number(e.target.value))} className="rounded-md border border-line bg-surface px-2 py-1 text-xs outline-none focus:border-brand">
                        {[10, 25, 50, 100].map((n) => <option key={n} value={n}>{n} / page</option>)}
                    </select>
                </div>
                <div className="flex items-center gap-1">
                    <button onClick={() => setPage(current - 1)} disabled={current === 0} className="rounded-md border border-line p-1.5 transition-colors hover:bg-surface-2 disabled:opacity-40" aria-label="Précédent"><ChevronLeft size={15} /></button>
                    <span className="px-2 text-xs">Page {current + 1} / {pageCount}</span>
                    <button onClick={() => setPage(current + 1)} disabled={current >= pageCount - 1} className="rounded-md border border-line p-1.5 transition-colors hover:bg-surface-2 disabled:opacity-40" aria-label="Suivant"><ChevronRight size={15} /></button>
                </div>
            </div>
        </div>
    );
}
