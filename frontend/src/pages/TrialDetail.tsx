import { Link, useParams } from 'react-router-dom'
import { Check, ArrowLeft, Paperclip, ScrollText, Coins, Image, Printer, FileSpreadsheet, Scale } from 'lucide-react'
import { Badge, Button, Card } from '../components/ui'
import { trials } from '../data/mock'
import { cn } from '../lib/cn'

function InfoRow({ label, value }: { label: string; value: string }) {
  return (
    <div>
      <div className="text-[11px] uppercase tracking-wide text-ink-faint">{label}</div>
      <div className="mt-0.5 text-sm text-ink">{value}</div>
    </div>
  )
}

export default function TrialDetail() {
  const { id } = useParams()
  const trial = trials.find((t) => t.id === id) ?? trials[0]

  return (
    <div>
      <Link to="/trials" className="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
        <ArrowLeft size={15} /> Retour aux essais
      </Link>

      <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-[22px] font-medium text-ink">{trial.variety}</h1>
            <Badge tone={trial.statusTone}>{trial.status}</Badge>
          </div>
          <p className="mt-1 text-sm text-ink-muted">
            {trial.code} · {trial.culture} · {trial.conduct} · {trial.site}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="ghost"><Printer size={15} /> PDF</Button>
          <Button variant="ghost"><FileSpreadsheet size={15} /> Export</Button>
          {trial.status === 'Décision' && (
            <Link to={`/trials/${trial.id}/decision`}>
              <Button variant="primary"><Scale size={15} /> Décision</Button>
            </Link>
          )}
        </div>
      </div>

      <Card className="mb-5 overflow-x-auto p-5">
        <div className="flex min-w-[720px] items-center">
          {trial.stages.map((s, i) => (
            <div key={s.key} className="flex flex-1 items-center last:flex-none">
              <div className="flex flex-col items-center gap-2">
                <div
                  className={cn(
                    'flex h-9 w-9 items-center justify-center rounded-full border text-xs font-medium',
                    s.status === 'done' && 'border-brand bg-brand text-brand-fg',
                    s.status === 'current' && 'border-brand bg-brand/10 text-brand',
                    s.status === 'todo' && 'border-line bg-surface text-ink-faint',
                  )}
                >
                  {s.status === 'done' ? <Check size={16} /> : i + 1}
                </div>
                <span className={cn('whitespace-nowrap text-xs', s.status === 'todo' ? 'text-ink-faint' : 'text-ink')}>
                  {s.label}
                </span>
              </div>
              {i < trial.stages.length - 1 && (
                <div className={cn('mx-1 h-0.5 flex-1', s.status === 'done' ? 'bg-brand' : 'bg-line')} />
              )}
            </div>
          ))}
        </div>
      </Card>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <Card className="p-5">
          <div className="mb-4 text-[15px] font-medium text-ink">Informations</div>
          <div className="grid grid-cols-2 gap-4">
            <InfoRow label="Variété essai" value={trial.variety} />
            <InfoRow label="Culture" value={trial.culture} />
            <InfoRow label="Fournisseur" value={trial.supplier} />
            <InfoRow label="Conduite" value={trial.conduct} />
            <div className="col-span-2">
              <InfoRow label="Segment" value={trial.segment} />
            </div>
            <InfoRow label="Responsable" value={trial.owner} />
            <InfoRow label="Site" value={trial.site} />
            {trial.partner && <InfoRow label="Partenaire" value={trial.partner} />}
            <InfoRow label="Campagne" value={trial.season} />
          </div>
          <div className="mt-4 border-t border-line pt-4">
            <div className="text-[11px] uppercase tracking-wide text-ink-faint">Témoins</div>
            <div className="mt-2 flex flex-wrap gap-1.5">
              {trial.controls.length ? (
                trial.controls.map((c) => <Badge key={c}>{c}</Badge>)
              ) : (
                <span className="text-sm text-ink-faint">Aucun témoin</span>
              )}
            </div>
          </div>
        </Card>

        <div className="space-y-5 lg:col-span-2">
          <Card className="p-5">
            <div className="mb-1 flex items-center gap-2 text-[15px] font-medium text-ink">
              <ScrollText size={17} /> Étape courante — {trial.stages.find((s) => s.status === 'current')?.label ?? 'Clôturé'}
            </div>
            <p className="text-sm text-ink-muted">
              Saisie dynamique des données de l'étape (champs configurables + mesures du modèle d'essai).
            </p>
            <div className="mt-4 grid grid-cols-2 gap-3">
              {trial.measures.slice(0, 4).map((m) => (
                <div key={m.code} className="rounded-md bg-surface-2 p-3">
                  <div className="text-xs text-ink-muted">{m.label}</div>
                  <div className="mt-1 text-lg font-medium text-ink">
                    {m.essai} <span className="text-xs font-normal text-ink-faint">{m.unit}</span>
                  </div>
                </div>
              ))}
            </div>
          </Card>

          <div className="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <Card className="flex items-center gap-3 p-4">
              <Paperclip size={18} className="text-ink-faint" />
              <div>
                <div className="text-sm font-medium text-ink">Pièces jointes</div>
                <div className="text-xs text-ink-faint">À chaque phase</div>
              </div>
            </Card>
            <Card className="flex items-center gap-3 p-4">
              <Coins size={18} className="text-ink-faint" />
              <div>
                <div className="text-sm font-medium text-ink">Charges</div>
                <div className="text-xs text-ink-faint">{trial.cost ?? '—'}</div>
              </div>
            </Card>
            <Card className="flex items-center gap-3 p-4">
              <Image size={18} className="text-ink-faint" />
              <div>
                <div className="text-sm font-medium text-ink">Galerie photos</div>
                <div className="text-xs text-ink-faint">0 photo</div>
              </div>
            </Card>
          </div>
        </div>
      </div>
    </div>
  )
}
