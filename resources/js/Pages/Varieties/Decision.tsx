import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Layers, MapPin } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card } from '@/Components/ui';
import DecisionPanel, { type Measure, type DecisionRecord } from '@/Components/DecisionPanel';

type Contrib = { id: number; code: string; site: string | null; season: string | null; status: string; status_tone: 'success' | 'warning' | 'info' | 'neutral' | 'danger' };

export default function Decision({
    variety,
    measures,
    trials,
    sites,
    seasons,
    decisions,
}: {
    variety: string;
    measures: Measure[];
    trials: Contrib[];
    sites: string[];
    seasons: string[];
    decisions: DecisionRecord[];
}) {
    return (
        <AppLayout>
            <Head title={`Décision variété — ${variety}`} />
            <Link href="/trials" className="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft size={15} /> Retour aux essais
            </Link>

            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="flex items-center gap-2 text-[22px] font-medium text-ink">
                        <Layers size={20} /> Décision variété — {variety}
                    </h1>
                    <p className="mt-1 text-sm text-ink-muted">
                        {trials.length} essais agrégés · {sites.length} site(s) · {seasons.length} saison(s)
                    </p>
                </div>
            </div>

            <Card className="mb-5 p-5">
                <div className="mb-3 text-[15px] font-medium text-ink">Essais agrégés</div>
                <div className="flex flex-wrap gap-2">
                    {trials.map((t) => (
                        <Link
                            key={t.id}
                            href={`/trials/${t.id}`}
                            className="flex items-center gap-2 rounded-md border border-line px-3 py-1.5 text-xs hover:bg-surface-2"
                        >
                            <span className="font-medium text-ink">{t.code}</span>
                            <span className="flex items-center gap-1 text-ink-faint"><MapPin size={12} /> {t.site} · {t.season}</span>
                            <Badge tone={t.status_tone}>{t.status}</Badge>
                        </Link>
                    ))}
                </div>
            </Card>

            <DecisionPanel
                measures={measures}
                postUrl={`/varieties/${encodeURIComponent(variety)}/decision`}
                decisions={decisions}
                contextNote="(moyennes agrégées sur tous les sites & saisons)"
            />
        </AppLayout>
    );
}
