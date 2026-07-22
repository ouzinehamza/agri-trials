import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Plus, X, Building2, UserPlus, FlaskConical } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader } from '@/Components/ui';

type Member = { id: number; name: string; email: string; is_external: boolean; role: string; role_label: string };
type Workspace = { id: number; name: string; description: string | null; trials_count: number; members: Member[] };
type Usr = { id: number; name: string; email: string };
type Role = { value: string; label: string };

const inputClass = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Index({ workspaces, users, roles }: { workspaces: Workspace[]; users: Usr[]; roles: Role[] }) {
    const [createOpen, setCreateOpen] = useState(false);
    const [addTo, setAddTo] = useState<Workspace | null>(null);

    const createForm = useForm({ name: '', description: '' });
    const memberForm = useForm({ user_id: '', role: 'viewer' });

    const submitCreate = (e: React.FormEvent) => { e.preventDefault(); createForm.post('/workspaces', { preserveScroll: true, onSuccess: () => { createForm.reset(); setCreateOpen(false); } }); };
    const submitMember = (e: React.FormEvent) => { e.preventDefault(); if (!addTo) return; memberForm.post(`/workspaces/${addTo.id}/members`, { preserveScroll: true, onSuccess: () => { memberForm.reset(); setAddTo(null); } }); };
    const removeMember = (w: number, u: number) => router.delete(`/workspaces/${w}/members/${u}`, { preserveScroll: true });

    return (
        <AppLayout>
            <Head title="Espaces de travail" />
            <PageHeader
                title="Espaces de travail"
                subtitle="Conteneurs créés par l'admin — assignez-y des membres (rôles) et des essais. Chacun ne voit que ses espaces."
                actions={<Button variant="primary" onClick={() => setCreateOpen(true)}><Plus size={15} /> Nouvel espace</Button>}
            />

            <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {workspaces.map((w) => (
                    <Card key={w.id} className="p-5">
                        <div className="flex items-start justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand/10 text-brand"><Building2 size={20} /></div>
                                <div>
                                    <div className="text-[15px] font-medium text-ink">{w.name}</div>
                                    <div className="text-xs text-ink-faint">{w.description}</div>
                                </div>
                            </div>
                            <Badge tone="neutral"><FlaskConical size={12} /> {w.trials_count} essais</Badge>
                        </div>

                        <div className="mt-4 border-t border-line pt-4">
                            <div className="mb-2 flex items-center justify-between">
                                <span className="text-[11px] uppercase tracking-wide text-ink-faint">Membres ({w.members.length})</span>
                                <button onClick={() => { memberForm.reset(); setAddTo(w); }} className="inline-flex items-center gap-1 text-xs font-medium text-brand hover:underline">
                                    <UserPlus size={13} /> Ajouter
                                </button>
                            </div>
                            <div className="space-y-1.5">
                                {w.members.length === 0 && <p className="text-sm text-ink-faint">Aucun membre.</p>}
                                {w.members.map((m) => (
                                    <div key={m.id} className="flex items-center justify-between gap-2 rounded-md bg-surface-2 px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-info-soft text-[10px] font-medium text-info">
                                                {m.name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()}
                                            </div>
                                            <div>
                                                <div className="text-sm font-medium text-ink">{m.name}</div>
                                                <div className="text-[11px] text-ink-faint">{m.email}</div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge tone={m.is_external ? 'warning' : 'info'}>{m.role_label}</Badge>
                                            <button onClick={() => removeMember(w.id, m.id)} className="text-ink-faint hover:text-danger" aria-label="Retirer"><X size={15} /></button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </Card>
                ))}
            </div>

            {createOpen && (
                <Modal title="Nouvel espace de travail" onClose={() => setCreateOpen(false)} onSubmit={submitCreate} processing={createForm.processing}>
                    <div>
                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Nom</label>
                        <input className={inputClass} value={createForm.data.name} onChange={(e) => createForm.setData('name', e.target.value)} />
                        {createForm.errors.name && <p className="mt-1 text-[11px] text-danger">{createForm.errors.name}</p>}
                    </div>
                    <div>
                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Description</label>
                        <input className={inputClass} value={createForm.data.description} onChange={(e) => createForm.setData('description', e.target.value)} />
                    </div>
                </Modal>
            )}

            {addTo && (
                <Modal title={`Ajouter un membre — ${addTo.name}`} onClose={() => setAddTo(null)} onSubmit={submitMember} processing={memberForm.processing}>
                    <div>
                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Utilisateur</label>
                        <select className={inputClass} value={memberForm.data.user_id} onChange={(e) => memberForm.setData('user_id', e.target.value)}>
                            <option value="">—</option>
                            {users.map((u) => <option key={u.id} value={u.id}>{u.name} · {u.email}</option>)}
                        </select>
                        {memberForm.errors.user_id && <p className="mt-1 text-[11px] text-danger">{memberForm.errors.user_id}</p>}
                    </div>
                    <div>
                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Rôle</label>
                        <select className={inputClass} value={memberForm.data.role} onChange={(e) => memberForm.setData('role', e.target.value)}>
                            {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </select>
                    </div>
                </Modal>
            )}
        </AppLayout>
    );
}

function Modal({ title, onClose, onSubmit, processing, children }: { title: string; onClose: () => void; onSubmit: (e: React.FormEvent) => void; processing: boolean; children: React.ReactNode }) {
    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:p-10">
            <div className="w-full max-w-md rounded-xl border border-line bg-surface shadow-xl">
                <div className="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 className="text-[17px] font-medium text-ink">{title}</h2>
                    <button onClick={onClose} className="text-ink-faint hover:text-ink" aria-label="Fermer"><X size={18} /></button>
                </div>
                <form onSubmit={onSubmit}>
                    <div className="space-y-4 p-5">{children}</div>
                    <div className="flex justify-end gap-2 border-t border-line px-5 py-4">
                        <Button variant="secondary" onClick={onClose}>Annuler</Button>
                        <Button variant="primary" type="submit">{processing ? 'Enregistrement…' : 'Enregistrer'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
