import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Plus, X, UserPlus } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader } from '@/Components/ui';
import { cn } from '@/lib/cn';

type Role = { value: string; label: string };
type Usr = { id: number; name: string; email: string; role: string; role_label: string; is_external: boolean; status: string; is_self: boolean };

const statusTone = (s: string): 'success' | 'info' | 'danger' => (s === 'active' ? 'success' : s === 'invited' ? 'info' : 'danger');
const inputClass = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Index({ users, roles }: { users: Usr[]; roles: Role[] }) {
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', email: '', role: 'viewer', is_external: false, password: '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/users', { preserveScroll: true, onSuccess: () => { form.reset(); setOpen(false); } });
    };
    const patch = (id: number, payload: Record<string, string>) => router.patch(`/users/${id}`, payload as any, { preserveScroll: true });

    return (
        <AppLayout>
            <Head title="Utilisateurs" />
            <PageHeader
                title="Utilisateurs"
                subtitle="Membres internes / externes et leurs rôles (RBAC). Seul l'administrateur gère les accès."
                actions={<Button variant="primary" onClick={() => setOpen(true)}><UserPlus size={15} /> Inviter</Button>}
            />

            <Card className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                            <th className="px-4 py-3 font-medium">Utilisateur</th>
                            <th className="px-4 py-3 font-medium">Type</th>
                            <th className="px-4 py-3 font-medium">Rôle</th>
                            <th className="px-4 py-3 font-medium">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        {users.map((u) => (
                            <tr key={u.id} className="border-b border-line last:border-0 hover:bg-surface-2">
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-8 w-8 items-center justify-center rounded-full bg-info-soft text-xs font-medium text-info">
                                            {u.name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase()}
                                        </div>
                                        <div>
                                            <div className="font-medium text-ink">{u.name}{u.is_self && <span className="ml-1 text-[10px] text-ink-faint">(vous)</span>}</div>
                                            <div className="text-xs text-ink-faint">{u.email}</div>
                                        </div>
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    <Badge tone={u.is_external ? 'warning' : 'neutral'}>{u.is_external ? 'Externe' : 'Interne'}</Badge>
                                </td>
                                <td className="px-4 py-3">
                                    <select value={u.role} disabled={u.is_self} onChange={(e) => patch(u.id, { role: e.target.value })}
                                        className={cn('rounded-md bg-surface-2 px-2 py-1 text-xs font-medium text-ink disabled:opacity-60')}>
                                        {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                                    </select>
                                </td>
                                <td className="px-4 py-3">
                                    {u.is_self ? (
                                        <Badge tone={statusTone(u.status)}>{u.status}</Badge>
                                    ) : (
                                        <select value={u.status} onChange={(e) => patch(u.id, { status: e.target.value })}
                                            className={cn('rounded-md px-2 py-1 text-xs font-medium',
                                                statusTone(u.status) === 'success' ? 'bg-success-soft text-success' : statusTone(u.status) === 'info' ? 'bg-info-soft text-info' : 'bg-danger-soft text-danger')}>
                                            <option value="active">active</option><option value="invited">invited</option><option value="disabled">disabled</option>
                                        </select>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </Card>

            {open && (
                <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:p-10">
                    <div className="w-full max-w-lg rounded-xl border border-line bg-surface shadow-xl">
                        <div className="flex items-center justify-between border-b border-line px-5 py-4">
                            <h2 className="text-[17px] font-medium text-ink">Inviter un utilisateur</h2>
                            <button onClick={() => setOpen(false)} className="text-ink-faint hover:text-ink" aria-label="Fermer"><X size={18} /></button>
                        </div>
                        <form onSubmit={submit}>
                            <div className="space-y-4 p-5">
                                <div>
                                    <label className="mb-1.5 block text-[13px] font-medium text-ink">Nom</label>
                                    <input className={inputClass} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                                    {form.errors.name && <p className="mt-1 text-[11px] text-danger">{form.errors.name}</p>}
                                </div>
                                <div>
                                    <label className="mb-1.5 block text-[13px] font-medium text-ink">E-mail</label>
                                    <input type="email" className={inputClass} value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                                    {form.errors.email && <p className="mt-1 text-[11px] text-danger">{form.errors.email}</p>}
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Rôle</label>
                                        <select className={inputClass} value={form.data.role} onChange={(e) => form.setData('role', e.target.value)}>
                                            {roles.map((r) => <option key={r.value} value={r.value}>{r.label}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="mb-1.5 block text-[13px] font-medium text-ink">Mot de passe</label>
                                        <input type="text" className={inputClass} value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                                        {form.errors.password && <p className="mt-1 text-[11px] text-danger">{form.errors.password}</p>}
                                    </div>
                                </div>
                                <label className="flex items-center gap-2 text-sm text-ink">
                                    <input type="checkbox" checked={form.data.is_external} onChange={(e) => form.setData('is_external', e.target.checked)} />
                                    Utilisateur externe (tiers)
                                </label>
                            </div>
                            <div className="flex justify-end gap-2 border-t border-line px-5 py-4">
                                <Button variant="secondary" onClick={() => setOpen(false)}>Annuler</Button>
                                <Button variant="primary" type="submit">{form.processing ? 'Création…' : 'Créer'}</Button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
