import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { Plus, X, Coins, ReceiptText, AlertTriangle } from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout';
import { Badge, Button, Card, PageHeader, StatCard } from '@/Components/ui';
import { cn } from '@/lib/cn';

type Expense = { id: number; label: string; category: string | null; amount: string | number; currency: string; incurred_on: string; trial_code: string | null; partner: string | null };
type Invoice = { id: number; number: string; partner: string; trial_code: string | null; amount: number; currency: string; status: string; status_label: string; issued_on: string | null; due_on: string | null };
type Totals = { expenses: number; invoices: number; unpaid: number; overdue: number };

const money = (a: number | string, c: string) => `${Number(a).toLocaleString('fr-FR')} ${c}`;
const date = (iso: string | null) => (iso ? new Date(iso).toLocaleDateString('fr-FR') : '—');
const statusTone = (s: string): string => (s === 'paid' ? 'success' : s === 'overdue' ? 'danger' : s === 'sent' ? 'info' : 'neutral');
const inputClass = 'w-full rounded-md border border-line bg-page px-3 py-2 text-sm text-ink outline-none focus:border-brand';

export default function Index({ expenses, invoices, totals }: { expenses: Expense[]; invoices: Invoice[]; totals: Totals }) {
    const [tab, setTab] = useState<'charges' | 'factures'>('charges');
    const [expOpen, setExpOpen] = useState(false);
    const [invOpen, setInvOpen] = useState(false);

    const today = new Date().toISOString().slice(0, 10);
    const expForm = useForm({ label: '', category: 'Semences', amount: '', currency: 'MAD', incurred_on: today, trial_code: '', partner: '' });
    const invForm = useForm({ number: '', partner: '', trial_code: '', amount: '', currency: 'MAD', status: 'draft', issued_on: today, due_on: '' });

    const submitExp = (e: React.FormEvent) => { e.preventDefault(); expForm.post('/expenses/charges', { preserveScroll: true, onSuccess: () => { expForm.reset(); setExpOpen(false); } }); };
    const submitInv = (e: React.FormEvent) => { e.preventDefault(); invForm.post('/expenses/factures', { preserveScroll: true, onSuccess: () => { invForm.reset(); setInvOpen(false); } }); };
    const setStatus = (id: number, status: string) => router.patch(`/expenses/factures/${id}/statut`, { status }, { preserveScroll: true });

    return (
        <AppLayout>
            <Head title="Charges & factures" />
            <PageHeader
                title="Charges & factures"
                subtitle="Charges par essai / partenaire et factures des tiers (ex. pépinière externe)."
                actions={
                    tab === 'charges'
                        ? <Button variant="primary" onClick={() => setExpOpen(true)}><Plus size={15} /> Nouvelle charge</Button>
                        : <Button variant="primary" onClick={() => setInvOpen(true)}><Plus size={15} /> Nouvelle facture</Button>
                }
            />

            <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Total charges" value={money(totals.expenses, 'MAD')} icon={<Coins size={18} />} />
                <StatCard label="Total factures" value={money(totals.invoices, 'MAD')} icon={<ReceiptText size={18} />} />
                <StatCard label="Impayé" value={money(totals.unpaid, 'MAD')} sub="Envoyées + en retard" />
                <StatCard label="En retard" value={totals.overdue} sub="factures" icon={<AlertTriangle size={18} />} />
            </div>

            <div className="my-5 flex gap-2">
                {(['charges', 'factures'] as const).map((t) => (
                    <button key={t} onClick={() => setTab(t)}
                        className={cn('rounded-full px-3.5 py-1.5 text-sm transition', tab === t ? 'bg-ink text-page' : 'border border-line bg-surface text-ink-muted hover:bg-surface-2')}>
                        {t === 'charges' ? 'Charges' : 'Factures'}
                    </button>
                ))}
            </div>

            {tab === 'charges' ? (
                <Card className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                                <th className="px-4 py-3 font-medium">Libellé</th><th className="px-4 py-3 font-medium">Catégorie</th>
                                <th className="px-4 py-3 font-medium">Essai</th><th className="px-4 py-3 font-medium">Partenaire</th>
                                <th className="px-4 py-3 font-medium">Montant</th><th className="px-4 py-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {expenses.map((e) => (
                                <tr key={e.id} className="border-b border-line last:border-0 hover:bg-surface-2">
                                    <td className="px-4 py-3 font-medium text-ink">{e.label}</td>
                                    <td className="px-4 py-3 text-ink-muted">{e.category ?? '—'}</td>
                                    <td className="px-4 py-3 text-ink-muted">{e.trial_code ?? '—'}</td>
                                    <td className="px-4 py-3 text-ink-muted">{e.partner ?? '—'}</td>
                                    <td className="px-4 py-3 font-medium text-ink">{money(e.amount, e.currency)}</td>
                                    <td className="px-4 py-3 text-ink-muted">{date(e.incurred_on)}</td>
                                </tr>
                            ))}
                            {expenses.length === 0 && <tr><td colSpan={6} className="px-4 py-10 text-center text-ink-faint">Aucune charge.</td></tr>}
                        </tbody>
                    </table>
                </Card>
            ) : (
                <Card className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                                <th className="px-4 py-3 font-medium">N°</th><th className="px-4 py-3 font-medium">Partenaire</th>
                                <th className="px-4 py-3 font-medium">Essai</th><th className="px-4 py-3 font-medium">Montant</th>
                                <th className="px-4 py-3 font-medium">Échéance</th><th className="px-4 py-3 font-medium">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.map((i) => (
                                <tr key={i.id} className="border-b border-line last:border-0 hover:bg-surface-2">
                                    <td className="px-4 py-3 font-medium text-ink">{i.number}</td>
                                    <td className="px-4 py-3 text-ink-muted">{i.partner}</td>
                                    <td className="px-4 py-3 text-ink-muted">{i.trial_code ?? '—'}</td>
                                    <td className="px-4 py-3 font-medium text-ink">{money(i.amount, i.currency)}</td>
                                    <td className="px-4 py-3 text-ink-muted">{i.due_on ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <select value={i.status} onChange={(e) => setStatus(i.id, e.target.value)}
                                            className={cn('rounded-md border-0 px-2 py-1 text-xs font-medium',
                                                statusTone(i.status) === 'success' ? 'bg-success-soft text-success' : statusTone(i.status) === 'danger' ? 'bg-danger-soft text-danger' : statusTone(i.status) === 'info' ? 'bg-info-soft text-info' : 'bg-surface-2 text-ink-muted')}>
                                            <option value="draft">Brouillon</option><option value="sent">Envoyée</option>
                                            <option value="paid">Payée</option><option value="overdue">En retard</option>
                                        </select>
                                    </td>
                                </tr>
                            ))}
                            {invoices.length === 0 && <tr><td colSpan={6} className="px-4 py-10 text-center text-ink-faint">Aucune facture.</td></tr>}
                        </tbody>
                    </table>
                </Card>
            )}

            {expOpen && (
                <Modal title="Nouvelle charge" onClose={() => setExpOpen(false)} onSubmit={submitExp} processing={expForm.processing}>
                    <Field label="Libellé"><input className={inputClass} value={expForm.data.label} onChange={(e) => expForm.setData('label', e.target.value)} /></Field>
                    <Field label="Catégorie">
                        <select className={inputClass} value={expForm.data.category} onChange={(e) => expForm.setData('category', e.target.value)}>
                            {['Semences', 'Pépinière', 'Main d\'œuvre', 'Analyse', 'Transport', 'Autre'].map((c) => <option key={c}>{c}</option>)}
                        </select>
                    </Field>
                    <Field label="Montant (MAD)"><input type="number" className={inputClass} value={expForm.data.amount} onChange={(e) => expForm.setData('amount', e.target.value)} /></Field>
                    <Field label="Date"><input type="date" className={inputClass} value={expForm.data.incurred_on} onChange={(e) => expForm.setData('incurred_on', e.target.value)} /></Field>
                    <Field label="Essai"><input className={inputClass} value={expForm.data.trial_code} onChange={(e) => expForm.setData('trial_code', e.target.value)} placeholder="P00017" /></Field>
                    <Field label="Partenaire"><input className={inputClass} value={expForm.data.partner} onChange={(e) => expForm.setData('partner', e.target.value)} /></Field>
                </Modal>
            )}

            {invOpen && (
                <Modal title="Nouvelle facture" onClose={() => setInvOpen(false)} onSubmit={submitInv} processing={invForm.processing}>
                    <Field label="N°"><input className={inputClass} value={invForm.data.number} onChange={(e) => invForm.setData('number', e.target.value)} placeholder="FAC-2026-004" /></Field>
                    <Field label="Partenaire"><input className={inputClass} value={invForm.data.partner} onChange={(e) => invForm.setData('partner', e.target.value)} /></Field>
                    <Field label="Montant (MAD)"><input type="number" className={inputClass} value={invForm.data.amount} onChange={(e) => invForm.setData('amount', e.target.value)} /></Field>
                    <Field label="Essai"><input className={inputClass} value={invForm.data.trial_code} onChange={(e) => invForm.setData('trial_code', e.target.value)} placeholder="P00017" /></Field>
                    <Field label="Statut">
                        <select className={inputClass} value={invForm.data.status} onChange={(e) => invForm.setData('status', e.target.value)}>
                            <option value="draft">Brouillon</option><option value="sent">Envoyée</option><option value="paid">Payée</option><option value="overdue">En retard</option>
                        </select>
                    </Field>
                    <Field label="Échéance"><input type="date" className={inputClass} value={invForm.data.due_on} onChange={(e) => invForm.setData('due_on', e.target.value)} /></Field>
                </Modal>
            )}
        </AppLayout>
    );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
    return <div><label className="mb-1.5 block text-[13px] font-medium text-ink">{label}</label>{children}</div>;
}

function Modal({ title, onClose, onSubmit, processing, children }: { title: string; onClose: () => void; onSubmit: (e: React.FormEvent) => void; processing: boolean; children: React.ReactNode }) {
    return (
        <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-ink/40 p-4 sm:p-10">
            <div className="w-full max-w-2xl rounded-xl border border-line bg-surface shadow-xl">
                <div className="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 className="text-[17px] font-medium text-ink">{title}</h2>
                    <button onClick={onClose} className="text-ink-faint hover:text-ink" aria-label="Fermer"><X size={18} /></button>
                </div>
                <form onSubmit={onSubmit}>
                    <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">{children}</div>
                    <div className="flex justify-end gap-2 border-t border-line px-5 py-4">
                        <Button variant="secondary" onClick={onClose}>Annuler</Button>
                        <Button variant="primary" type="submit">{processing ? 'Enregistrement…' : 'Enregistrer'}</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}
