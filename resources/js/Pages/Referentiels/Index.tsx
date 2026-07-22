import { useEffect, useState } from 'react';
import { Deferred, Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, Upload, Download, X, FileSpreadsheet, CheckCircle2, AlertTriangle, Loader2 } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Button, PageHeader } from '@/Components/ui';
import DynamicTable from '@/Components/DynamicTable';
import { TableSkeleton } from '@/Components/Skeleton';
import DynamicForm from '@/Components/DynamicForm';
import type { FieldDef, WithCustomData } from '@/lib/fields';
import { isMultiValue } from '@/lib/fields';
import { cn } from '@/lib/cn';

type Tab = { slug: string; label: string };
type RowError = { line: number; errors: string[] };
type ImportResult = { imported?: number; failed?: number; errors?: RowError[]; queued?: boolean; job_id?: number; total?: number };
type PreviewRow = { line: number; values: Record<string, string>; valid: boolean; action: 'create' | 'update'; errors: string[] };
type ImportPreview = { job_id: number; filename: string; columns: string[]; total: number; valid: number; invalid: number; rows: PreviewRow[] };
type DisplayMap = Record<string | number, Record<string, string>>;

export default function Index({ slug, label, tabs, fields, rows, display = {} }: { slug: string; label: string; tabs: Tab[]; fields: FieldDef[]; rows: WithCustomData[]; display?: DisplayMap }) {
    const [editing, setEditing] = useState<WithCustomData | null | undefined>(undefined);
    const [importOpen, setImportOpen] = useState(false);

    const pageProps = usePage().props;
    const flash = pageProps.flash as { import_result?: ImportResult; import_preview?: ImportPreview } | undefined;
    const result = flash?.import_result;
    const preview = flash?.import_preview;
    const locales = (pageProps.locales as string[] | undefined) ?? ['fr'];

    const dataForRow = (row: WithCustomData | null): Record<string, any> => {
        const out: Record<string, any> = {};
        fields.forEach((f) => {
            const raw = row ? (f.is_system ? row[f.key] : row.custom_data?.[f.key]) : undefined;
            if (f.translatable) {
                const o: Record<string, string> = {};
                locales.forEach((l) => (o[l] = ((raw as Record<string, string>) ?? {})[l] ?? ''));
                out[f.key] = o;
            } else if (isMultiValue(f)) {
                out[f.key] = (raw as unknown[]) ?? [];
            } else {
                out[f.key] = f.type === 'boolean' ? !!raw : (raw ?? '');
            }
        });
        return out;
    };

    const form = useForm<Record<string, any>>(dataForRow(null));

    const openCreate = () => { form.clearErrors(); form.setData(dataForRow(null)); setEditing(null); };
    const openEdit = (row: WithCustomData) => { form.clearErrors(); form.setData(dataForRow(row)); setEditing(row); };
    const close = () => setEditing(undefined);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: () => { form.reset(); close(); } };
        if (editing) form.put(`/referentiels/${slug}/${editing.id}`, opts);
        else form.post(`/referentiels/${slug}`, opts);
    };
    const del = (row: WithCustomData) => {
        if (confirm('Supprimer cet enregistrement ?')) router.delete(`/referentiels/${slug}/${row.id}`, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title={`Référentiels — ${label}`} />
            <PageHeader
                title="Référentiels"
                subtitle="Gérez vos variétés, témoins, fournisseurs, porte-greffes, partenaires et segments."
                actions={
                    <div className="flex gap-2">
                        <Button variant="secondary" onClick={() => setImportOpen(true)}><Upload size={15} /> Importer</Button>
                        <a href={`/referentiels/${slug}/modele`}><Button variant="secondary"><Download size={15} /> Modèle</Button></a>
                        <Button variant="primary" onClick={openCreate}><Plus size={15} /> Ajouter</Button>
                    </div>
                }
            />

            <ImportResultBanner result={result} />

            <div className="mb-5 flex flex-wrap gap-2">
                {tabs.map((t) => (
                    <Link
                        key={t.slug}
                        href={`/referentiels/${t.slug}`}
                        className={cn('rounded-full px-3.5 py-1.5 text-sm transition', t.slug === slug ? 'bg-ink text-page' : 'border border-line bg-surface text-ink-muted hover:bg-surface-2')}
                    >
                        {t.label}
                    </Link>
                ))}
            </div>

            <Deferred data={['rows', 'display']} fallback={<div className="mt-8"><TableSkeleton rows={8} cols={Math.min(fields.filter((f) => f.show_in_table).length || 4, 6)} /></div>}>
                <>
                    <div className="mb-3 text-sm text-ink-muted">{(rows ?? []).length} {label.toLowerCase()}</div>
                    <DynamicTable fields={fields} rows={rows ?? []} display={display} onEdit={openEdit} onDelete={del} />
                </>
            </Deferred>

            <p className="mt-4 text-xs text-ink-faint">
                Le tableau et le formulaire sont générés à partir des <span className="text-accent">définitions de champs</span>.
                Les champs marqués <span className="text-accent">•</span> sont personnalisés (ajoutés par l'admin, sans code).
            </p>

            {editing !== undefined && (
                <Modal title={`${editing ? 'Modifier' : 'Nouvel enregistrement'} — ${label}`} onClose={close} width="max-w-2xl">
                    <form onSubmit={submit}>
                        <div className="p-5">
                            <DynamicForm fields={fields} data={form.data} setData={(k, v) => form.setData(k, v as any)} errors={form.errors as Partial<Record<string, string>>} />
                        </div>
                        <div className="flex justify-end gap-2 border-t border-line px-5 py-4">
                            <Button variant="secondary" onClick={close}>Annuler</Button>
                            <Button variant="primary" type="submit" disabled={form.processing}>Enregistrer</Button>
                        </div>
                    </form>
                </Modal>
            )}

            {importOpen && <ImportModal slug={slug} label={label} preview={preview} onClose={() => setImportOpen(false)} />}
        </AppLayout>
    );
}

function Modal({ title, onClose, width = 'max-w-lg', children }: { title: string; onClose: () => void; width?: string; children: React.ReactNode }) {
    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:p-10">
            <div className={cn('w-full rounded-xl border border-line bg-surface shadow-xl', width)}>
                <div className="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 className="text-[17px] font-medium text-ink">{title}</h2>
                    <button onClick={onClose} className="text-ink-faint hover:text-ink" aria-label="Fermer"><X size={18} /></button>
                </div>
                {children}
            </div>
        </div>
    );
}

/** Terminal result of an import, including live polling for a queued (large-file) commit. */
function ImportResultBanner({ result }: { result?: ImportResult }) {
    const [status, setStatus] = useState<ImportResult | null>(null);
    const live = status ?? result;

    useEffect(() => {
        if (!result?.queued || !result.job_id) return;
        setStatus({ ...result });
        const id = setInterval(async () => {
            const res = await fetch(`/import-jobs/${result.job_id}`, { headers: { Accept: 'application/json' } });
            const d = await res.json();
            if (d.status !== 'processing') {
                clearInterval(id);
                setStatus({ imported: d.imported, failed: d.failed, errors: d.errors });
                router.reload({ only: ['rows', 'display'] });
            }
        }, 1500);
        return () => clearInterval(id);
    }, [result?.job_id]);

    if (!live) return null;

    if (live.queued && live.imported === undefined) {
        return (
            <div className="mb-5 flex items-center gap-3 rounded-lg border border-info/30 bg-info-soft p-4 text-sm">
                <Loader2 size={18} className="animate-spin text-info" />
                <span>Import de {live.total} ligne(s) lancé en arrière-plan… il apparaîtra ici une fois terminé.</span>
            </div>
        );
    }

    const failed = live.failed ?? 0;
    return (
        <div className={cn('mb-5 flex items-start gap-3 rounded-lg border p-4', failed === 0 ? 'border-success/30 bg-success-soft' : 'border-warning/30 bg-warning-soft')}>
            {failed === 0 ? <CheckCircle2 size={18} className="mt-0.5 text-success" /> : <AlertTriangle size={18} className="mt-0.5 text-warning" />}
            <div className="text-sm">
                <div className={cn('font-medium', failed === 0 ? 'text-success' : 'text-warning')}>
                    Import terminé — {live.imported} importé(s), {failed} en erreur.
                </div>
                {(live.errors?.length ?? 0) > 0 && (
                    <ul className="mt-1 list-inside list-disc text-ink-muted">
                        {live.errors!.map((e, i) => <li key={i}>Ligne {e.line} : {e.errors.join(', ')}</li>)}
                    </ul>
                )}
            </div>
        </div>
    );
}

/** Two-step import: upload → preview (validated rows) → commit. */
function ImportModal({ slug, label, preview, onClose }: { slug: string; label: string; preview?: ImportPreview; onClose: () => void }) {
    const uploadForm = useForm<{ file: File | null }>({ file: null });
    const commitForm = useForm({});

    const analyze = (e: React.FormEvent) => {
        e.preventDefault();
        uploadForm.post(`/referentiels/${slug}/import/preview`, { forceFormData: true, preserveScroll: true, preserveState: true });
    };
    const commit = () => {
        if (!preview) return;
        commitForm.post(`/referentiels/${slug}/import/${preview.job_id}/commit`, { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Modal title={`Importer — ${label}`} onClose={onClose} width={preview ? 'max-w-4xl' : 'max-w-lg'}>
            {!preview ? (
                <form onSubmit={analyze}>
                    <div className="space-y-4 p-5">
                        <p className="text-sm text-ink-muted">Fichier CSV — colonnes mappées automatiquement aux champs (système et personnalisés). Les références acceptent l'identifiant ou le libellé (valeurs multiples séparées par « ; »). Chaque ligne est validée avant l'import.</p>
                        <a href={`/referentiels/${slug}/modele`} className="inline-flex items-center gap-1.5 text-sm text-accent hover:underline">
                            <FileSpreadsheet size={15} /> Télécharger le modèle CSV
                        </a>
                        <input type="file" accept=".csv,.txt" onChange={(e) => uploadForm.setData('file', e.target.files?.[0] ?? null)}
                            className="block w-full rounded-md border border-line bg-page p-2 text-sm text-ink file:mr-3 file:rounded file:border-0 file:bg-surface-2 file:px-3 file:py-1.5 file:text-sm file:text-ink" />
                        {uploadForm.errors.file && <p className="text-[11px] text-danger">{uploadForm.errors.file}</p>}
                    </div>
                    <div className="flex justify-end gap-2 border-t border-line px-5 py-4">
                        <Button variant="secondary" onClick={onClose}>Annuler</Button>
                        <Button variant="primary" type="submit" disabled={!uploadForm.data.file || uploadForm.processing}>{uploadForm.processing ? 'Analyse…' : 'Analyser'}</Button>
                    </div>
                </form>
            ) : (
                <>
                    <div className="space-y-3 p-5">
                        <div className="flex flex-wrap gap-2 text-sm">
                            <span className="rounded-md bg-surface-2 px-2.5 py-1">{preview.filename}</span>
                            <span className="rounded-md bg-success-soft px-2.5 py-1 text-success">{preview.valid} valide(s)</span>
                            {preview.invalid > 0 && <span className="rounded-md bg-danger-soft px-2.5 py-1 text-danger">{preview.invalid} en erreur</span>}
                            <span className="rounded-md bg-surface-2 px-2.5 py-1 text-ink-muted">{preview.total} ligne(s)</span>
                        </div>
                        <div className="max-h-[50vh] overflow-auto rounded-lg border border-line">
                            <table className="w-full text-sm">
                                <thead className="sticky top-0 bg-surface-2">
                                    <tr className="text-left text-xs uppercase text-ink-faint">
                                        <th className="px-3 py-2">Ligne</th>
                                        <th className="px-3 py-2">Action</th>
                                        {preview.columns.map((c) => <th key={c} className="px-3 py-2">{c}</th>)}
                                        <th className="px-3 py-2">État</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {preview.rows.map((r) => (
                                        <tr key={r.line} className={cn('border-t border-line', !r.valid && 'bg-danger-soft/40')}>
                                            <td className="px-3 py-2 text-ink-faint">{r.line}</td>
                                            <td className="px-3 py-2"><span className={cn('rounded px-1.5 py-0.5 text-[11px]', r.action === 'create' ? 'bg-info-soft text-info' : 'bg-surface-2 text-ink-muted')}>{r.action === 'create' ? 'création' : 'mise à jour'}</span></td>
                                            {preview.columns.map((c) => <td key={c} className="px-3 py-2 text-ink-muted">{r.values[c] || '—'}</td>)}
                                            <td className="px-3 py-2">
                                                {r.valid ? <CheckCircle2 size={15} className="text-success" /> : <span className="text-[11px] text-danger" title={r.errors.join(', ')}>{r.errors[0]}</span>}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        {preview.total > preview.rows.length && <p className="text-xs text-ink-faint">Aperçu limité aux {preview.rows.length} premières lignes ; l'import traitera les {preview.total}.</p>}
                    </div>
                    <div className="flex items-center justify-between gap-2 border-t border-line px-5 py-4">
                        <p className="text-xs text-ink-faint">Seules les lignes valides seront importées.</p>
                        <div className="flex gap-2">
                            <Button variant="secondary" onClick={onClose}>Annuler</Button>
                            <Button variant="primary" onClick={commit} disabled={commitForm.processing || preview.valid === 0}>
                                {commitForm.processing ? 'Import…' : `Importer ${preview.valid} ligne(s)`}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Modal>
    );
}
