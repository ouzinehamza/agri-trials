import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import DecisionPanel, { type Measure, type DecisionRecord } from '@/Components/DecisionPanel';
import { cn } from '@/lib/cn';

type Trial = {
    id: number; code: string; variety: string; culture: string; conduct: string | null;
    controls: string[] | null; measures: Measure[] | null; decisions?: DecisionRecord[];
};

export default function Decision({ trial }: { trial: Trial }) {
    const controls = trial.controls ?? [];

    return (
        <AppLayout>
            <Head title={`Décision — ${trial.variety}`} />
            <Link href={`/trials/${trial.id}`} className="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
                <ArrowLeft size={15} /> Retour à l'essai
            </Link>

            <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-[22px] font-medium text-ink">Décision — {trial.variety}</h1>
                    <p className="mt-1 text-sm text-ink-muted">
                        {trial.code} · {trial.culture} · {trial.conduct} · vs {controls.length} témoins
                    </p>
                </div>
                <div className="flex gap-1.5">
                    <span className={cn('rounded-md px-3 py-1.5 text-xs font-medium', 'bg-info-soft text-info')}>Cet essai</span>
                    <Link
                        href={`/varieties/${encodeURIComponent(trial.variety)}/decision`}
                        className="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-ink-muted hover:bg-surface-2"
                    >
                        Variété — multi-sites / saisons
                    </Link>
                </div>
            </div>

            <DecisionPanel measures={trial.measures ?? []} postUrl={`/trials/${trial.id}/decision`} decisions={trial.decisions ?? []} />
        </AppLayout>
    );
}
