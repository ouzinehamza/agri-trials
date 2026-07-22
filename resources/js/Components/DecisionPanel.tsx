import { useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Rocket, RefreshCw, X, SlidersHorizontal, ShieldCheck } from 'lucide-react';
import { Badge, Button, Card } from '@/Components/ui';
import { cn } from '@/lib/cn';

export type Measure = { code: string; comparison_key?: string; label: string; unit: string; dir: string; essai: number | null; temoin: number | null; weight: number; control_label?: string; beats_control?: boolean | null; aggregation?: string };
export type DecisionRecord = {
    id: number; verdict: string; verdict_label: string; score: number;
    justification: string; decided_by: string | null; decided_at: string | null;
};

function rowScore(m: Measure): number {
    if (m.essai == null || m.temoin == null) return 0;
    if (m.temoin === 0) return 50;
    if (m.dir === 'neutral') return 50;
    let dev = (m.essai - m.temoin) / m.temoin;
    if (m.dir === 'down') dev = -dev;
    return Math.max(0, Math.min(100, 50 + dev * 100 * 2.5));
}
function devPct(m: Measure): number {
    if (m.essai == null || m.temoin == null || m.temoin === 0) return 0;
    let d = ((m.essai - m.temoin) / m.temoin) * 100;
    if (m.dir === 'down') d = -d;
    return d;
}
function weighted(rows: Measure[]): number {
    const tot = rows.reduce((s, m) => s + m.weight, 0);
    if (tot === 0) return 0;
    return rows.reduce((s, m) => s + rowScore(m) * m.weight, 0) / tot;
}

export default function DecisionPanel({
    measures,
    postUrl,
    decisions = [],
    contextNote,
}: {
    measures: Measure[];
    postUrl: string;
    decisions?: DecisionRecord[];
    contextNote?: string;
}) {
    const [rows, setRows] = useState<Measure[]>(measures.map((m) => ({ ...m })));
    const score = useMemo(() => Math.round(weighted(rows)), [rows]);
    const reco =
        score >= 70
            ? { label: 'Lancement recommandé', tone: 'success' as const }
            : score >= 50
                ? { label: 'Re-test conseillé', tone: 'warning' as const }
                : { label: 'Rejet conseillé', tone: 'danger' as const };

    const setWeight = (code: string, w: number) => setRows((rs) => rs.map((m) => (m.code === code ? { ...m, weight: w } : m)));

    const form = useForm<{ verdict: string; justification: string; weights: Record<string, number> }>({
        verdict: '',
        justification: '',
        weights: {},
    });
    const submitVerdict = (verdict: string) => {
        const weights = Object.fromEntries(rows.map((m) => [m.code, m.weight]));
        form.transform((d) => ({ ...d, verdict, weights }));
        form.post(postUrl, { preserveScroll: true });
    };

    return (
        <>
            <div className="mb-3 flex items-center gap-1.5 text-xs text-ink-faint">
                <SlidersHorizontal size={13} /> Ajustez le poids de chaque mesure — le score se recalcule en direct.
                {contextNote && <span className="ml-1 text-info">{contextNote}</span>}
            </div>

            <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                {rows.map((m) => {
                    const s = Math.round(rowScore(m));
                    const d = devPct(m);
                    return (
                        <Card key={m.comparison_key ?? m.code} className="p-4">
                            <div className="flex items-baseline justify-between">
                                <span className="text-sm font-medium text-ink">{m.label} <span className="font-normal text-ink-faint">vs {m.control_label ?? 'Témoin'}</span></span>
                                <span className={cn('text-xs font-medium', d >= 0 ? 'text-success' : 'text-danger')}>
                                    {d >= 0 ? '+' : ''}{d.toFixed(1)}% vs témoin
                                </span>
                            </div>
                            <div className="mt-1 text-xs text-ink-muted">
                                Essai <span className="font-medium text-ink">{m.essai ?? '—'}</span> · Témoin {m.temoin ?? '—'}{' '}
                                <span className="text-ink-faint">{m.unit}</span>
                            </div>
                            <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-2">
                                <div className={cn('h-full rounded-full', s >= 50 ? 'bg-success' : 'bg-danger')} style={{ width: `${s}%` }} />
                            </div>
                            <div className="mt-3 flex items-center gap-2.5">
                                <span className="w-10 shrink-0 text-[11px] text-ink-faint">Poids</span>
                                <input type="range" min={0} max={50} step={1} value={m.weight} onChange={(e) => setWeight(m.code, Number(e.target.value))} className="flex-1" />
                                <span className="w-9 text-right text-xs font-medium text-ink">{m.weight}%</span>
                            </div>
                        </Card>
                    );
                })}
            </div>

            <Card className="mt-4 bg-surface-2 p-5">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <div className="text-sm text-ink-muted">Score global pondéré</div>
                        <div className="flex items-baseline gap-1.5">
                            <span className="text-4xl font-medium text-ink">{score}</span>
                            <span className="text-ink-faint">/ 100</span>
                        </div>
                    </div>
                    <Badge tone={reco.tone} className="text-[13px]">{reco.label}</Badge>
                </div>
                <div className="my-3 h-2.5 overflow-hidden rounded-full bg-surface">
                    <div
                        className={cn('h-full rounded-full', reco.tone === 'success' ? 'bg-success' : reco.tone === 'warning' ? 'bg-warning' : 'bg-danger')}
                        style={{ width: `${score}%` }}
                    />
                </div>
                <div className="flex justify-between text-[11px] text-ink-faint">
                    <span>Rejet</span><span>Re-test</span><span>Lancement</span>
                </div>

                <div className="mt-4">
                    <label className="mb-1.5 block text-[13px] font-medium text-ink">
                        Justification <span className="text-danger">*</span>
                    </label>
                    <textarea
                        rows={2}
                        value={form.data.justification}
                        onChange={(e) => form.setData('justification', e.target.value)}
                        placeholder="Pourquoi cette décision ? (figée dans le journal)"
                        className="w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-ink outline-none focus:border-brand"
                    />
                    {form.errors.justification && <p className="mt-1 text-[11px] text-danger">{form.errors.justification}</p>}
                </div>

                <div className="mt-4 flex flex-wrap gap-2">
                    <Button variant="primary" className="bg-success hover:bg-success" onClick={() => submitVerdict('launch')}>
                        <Rocket size={15} /> Lancer en production
                    </Button>
                    <Button variant="secondary" onClick={() => submitVerdict('retrial')}><RefreshCw size={15} /> Re-tester</Button>
                    <Button variant="danger" onClick={() => submitVerdict('reject')}><X size={15} /> Rejeter</Button>
                </div>
                <p className="mt-3 text-xs text-ink-faint">
                    La décision, les poids et le scorecard sont figés (journal immuable) au moment de l'arbitrage.
                </p>
            </Card>

            {decisions.length > 0 && (
                <Card className="mt-5 p-5">
                    <div className="mb-4 flex items-center gap-2 text-[15px] font-medium text-ink">
                        <ShieldCheck size={17} /> Journal des décisions
                    </div>
                    <div className="space-y-3">
                        {decisions.map((d) => {
                            const tone = d.verdict === 'launch' ? 'success' : d.verdict === 'reject' ? 'danger' : 'warning';
                            return (
                                <div key={d.id} className="rounded-md border border-line p-3">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <Badge tone={tone}>{d.verdict_label}</Badge>
                                        <span className="text-xs text-ink-faint">
                                            Score {d.score}/100 · {d.decided_by ?? '—'} · {d.decided_at ?? ''}
                                        </span>
                                    </div>
                                    <p className="mt-2 text-sm text-ink-muted">{d.justification}</p>
                                </div>
                            );
                        })}
                    </div>
                </Card>
            )}
        </>
    );
}
