import { useMemo, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ArrowLeft, Rocket, RefreshCw, X, SlidersHorizontal } from 'lucide-react'
import { Badge, Button, Card } from '../components/ui'
import { trials, scoreRow, devPct, weightedScore, type MeasurementRow } from '../data/mock'
import { cn } from '../lib/cn'

export default function Decision() {
  const { id } = useParams()
  const trial = trials.find((t) => t.id === id) ?? trials[0]
  const [rows, setRows] = useState<MeasurementRow[]>(trial.measures.map((m) => ({ ...m })))
  const [level, setLevel] = useState<'trial' | 'variety'>('trial')

  const score = useMemo(() => Math.round(weightedScore(rows)), [rows])
  const reco =
    score >= 70
      ? { label: 'Lancement recommandé', tone: 'success' as const }
      : score >= 50
        ? { label: 'Re-test conseillé', tone: 'warning' as const }
        : { label: 'Rejet conseillé', tone: 'danger' as const }

  const setWeight = (code: string, w: number) =>
    setRows((rs) => rs.map((m) => (m.code === code ? { ...m, weight: w } : m)))

  return (
    <div>
      <Link to={`/trials/${trial.id}`} className="mb-4 inline-flex items-center gap-1.5 text-sm text-ink-muted hover:text-ink">
        <ArrowLeft size={15} /> Retour à l'essai
      </Link>

      <div className="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-[22px] font-medium text-ink">Décision — {trial.variety}</h1>
          </div>
          <p className="mt-1 text-sm text-ink-muted">
            {trial.code} · {trial.culture} · {trial.conduct} · vs {trial.controls.length} témoins
          </p>
        </div>
        <div className="flex gap-1.5">
          <button
            onClick={() => setLevel('trial')}
            className={cn('rounded-md px-3 py-1.5 text-xs font-medium', level === 'trial' ? 'bg-info-soft text-info' : 'border border-line text-ink-muted')}
          >
            Cet essai
          </button>
          <button
            onClick={() => setLevel('variety')}
            className={cn('rounded-md px-3 py-1.5 text-xs font-medium', level === 'variety' ? 'bg-info-soft text-info' : 'border border-line text-ink-muted')}
          >
            Variété — multi-sites / saisons
          </button>
        </div>
      </div>

      <div className="mb-3 flex items-center gap-1.5 text-xs text-ink-faint">
        <SlidersHorizontal size={13} /> Ajustez le poids de chaque mesure — le score se recalcule en direct.
        {level === 'variety' && <span className="ml-1 text-info">(agrégé sur tous les sites & saisons)</span>}
      </div>

      <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
        {rows.map((m) => {
          const s = Math.round(scoreRow(m))
          const d = devPct(m)
          return (
            <Card key={m.code} className="p-4">
              <div className="flex items-baseline justify-between">
                <span className="text-sm font-medium text-ink">{m.label}</span>
                <span className={cn('text-xs font-medium', d >= 0 ? 'text-success' : 'text-danger')}>
                  {d >= 0 ? '+' : ''}
                  {d.toFixed(1)}% vs témoin
                </span>
              </div>
              <div className="mt-1 text-xs text-ink-muted">
                Essai <span className="font-medium text-ink">{m.essai}</span> · Témoin {m.temoin}{' '}
                <span className="text-ink-faint">{m.unit}</span>
              </div>
              <div className="mt-2 h-1.5 overflow-hidden rounded-full bg-surface-2">
                <div className={cn('h-full rounded-full', s >= 50 ? 'bg-success' : 'bg-danger')} style={{ width: `${s}%` }} />
              </div>
              <div className="mt-3 flex items-center gap-2.5">
                <span className="w-10 shrink-0 text-[11px] text-ink-faint">Poids</span>
                <input
                  type="range"
                  min={0}
                  max={50}
                  step={1}
                  value={m.weight}
                  onChange={(e) => setWeight(m.code, Number(e.target.value))}
                  className="flex-1"
                />
                <span className="w-9 text-right text-xs font-medium text-ink">{m.weight}%</span>
              </div>
            </Card>
          )
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
          <Badge tone={reco.tone} className="text-[13px]">
            {reco.label}
          </Badge>
        </div>
        <div className="my-3 h-2.5 overflow-hidden rounded-full bg-surface">
          <div
            className={cn('h-full rounded-full', reco.tone === 'success' ? 'bg-success' : reco.tone === 'warning' ? 'bg-warning' : 'bg-danger')}
            style={{ width: `${score}%` }}
          />
        </div>
        <div className="flex justify-between text-[11px] text-ink-faint">
          <span>Rejet</span>
          <span>Re-test</span>
          <span>Lancement</span>
        </div>

        <div className="mt-4 flex flex-wrap gap-2">
          <Button variant="primary" className="bg-success hover:bg-success">
            <Rocket size={15} /> Lancer en production
          </Button>
          <Button variant="secondary">
            <RefreshCw size={15} /> Re-tester
          </Button>
          <Button variant="danger">
            <X size={15} /> Rejeter
          </Button>
        </div>
        <p className="mt-3 text-xs text-ink-faint">
          La décision, les poids et le scorecard sont figés (journal immuable) au moment de l'arbitrage.
        </p>
      </Card>
    </div>
  )
}
