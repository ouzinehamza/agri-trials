import { Head, Link } from '@inertiajs/react';
import { History, ArrowLeft, ArrowRight } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Card, PageHeader } from '@/Components/ui';

type Activity = {
    id: number;
    event: string;
    subject_type: string;
    subject_id: number | null;
    subject_label: string | null;
    causer: string | null;
    changed: string[];
    created_at: string | null;
};
type Paginated = {
    data: Activity[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

const eventLabel = (e: string) => (e === 'created' ? 'Créé' : e === 'updated' ? 'Modifié' : e === 'deleted' ? 'Supprimé' : e);
const eventTone = (e: string): 'success' | 'info' | 'danger' | 'neutral' =>
    e === 'created' ? 'success' : e === 'updated' ? 'info' : e === 'deleted' ? 'danger' : 'neutral';

export default function Index({ activities }: { activities: Paginated }) {
    return (
        <AppLayout>
            <Head title="Journal d'activité" />
            <PageHeader
                title="Journal d'activité"
                subtitle="Trace auditable — qui a modifié quoi et quand, sur la configuration et les données."
            />

            <Card className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                            <th className="px-4 py-3 font-medium">Date</th>
                            <th className="px-4 py-3 font-medium">Utilisateur</th>
                            <th className="px-4 py-3 font-medium">Action</th>
                            <th className="px-4 py-3 font-medium">Objet</th>
                            <th className="px-4 py-3 font-medium">Champs modifiés</th>
                        </tr>
                    </thead>
                    <tbody>
                        {activities.data.map((a) => (
                            <tr key={a.id} className="border-b border-line last:border-0 hover:bg-surface-2">
                                <td className="whitespace-nowrap px-4 py-3 text-ink-muted">{a.created_at}</td>
                                <td className="px-4 py-3 text-ink">{a.causer ?? 'Système'}</td>
                                <td className="px-4 py-3"><Badge tone={eventTone(a.event)}>{eventLabel(a.event)}</Badge></td>
                                <td className="px-4 py-3">
                                    <span className="font-medium text-ink">{a.subject_type}</span>
                                    <span className="text-ink-faint"> {a.subject_label ? `· ${a.subject_label}` : `#${a.subject_id}`}</span>
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        {a.changed.slice(0, 6).map((k) => (
                                            <code key={k} className="rounded bg-surface-2 px-1.5 py-0.5 text-[11px] text-ink-muted">{k}</code>
                                        ))}
                                        {a.changed.length === 0 && <span className="text-ink-faint">—</span>}
                                    </div>
                                </td>
                            </tr>
                        ))}
                        {activities.data.length === 0 && (
                            <tr><td colSpan={5} className="px-4 py-12 text-center text-ink-faint">
                                <History size={22} className="mx-auto mb-2 opacity-50" /> Aucune activité enregistrée pour l'instant.
                            </td></tr>
                        )}
                    </tbody>
                </table>
            </Card>

            <div className="mt-4 flex items-center justify-between text-sm text-ink-muted">
                <span>{activities.total} événement(s) · page {activities.current_page}/{activities.last_page}</span>
                <div className="flex gap-2">
                    {activities.prev_page_url && (
                        <Link href={activities.prev_page_url} className="inline-flex items-center gap-1 rounded-md border border-line px-3 py-1.5 hover:bg-surface-2">
                            <ArrowLeft size={14} /> Précédent
                        </Link>
                    )}
                    {activities.next_page_url && (
                        <Link href={activities.next_page_url} className="inline-flex items-center gap-1 rounded-md border border-line px-3 py-1.5 hover:bg-surface-2">
                            Suivant <ArrowRight size={14} />
                        </Link>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
