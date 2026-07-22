import { FlaskConical, Rocket, Boxes, AlertTriangle } from 'lucide-react'
import { Card, PageHeader, StatCard, Badge } from '../components/ui'
import { trials } from '../data/mock'
import { Link } from 'react-router-dom'

const pipeline = [
  { label: 'Création', n: 1 },
  { label: 'Semis', n: 1 },
  { label: 'Greffage', n: 1 },
  { label: 'Évaluation', n: 1 },
  { label: 'Décision', n: 1 },
  { label: 'Clôturé', n: 2 },
]

export default function Dashboard() {
  const max = Math.max(...pipeline.map((p) => p.n))
  return (
    <div>
      <PageHeader title="Tableau de bord" subtitle="Vue d'ensemble des essais variétaux — campagne 2026" />

      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <StatCard label="Essais actifs" value="4" sub="+1 cette semaine" icon={<FlaskConical size={18} />} />
        <StatCard label="En décision" value="1" sub="À arbitrer" icon={<Rocket size={18} />} />
        <StatCard label="Variétés lancées" value="6" sub="Cumul 2025-2026" icon={<Boxes size={18} />} />
        <StatCard label="Alertes stock" value="2" sub="Ruptures de semences" icon={<AlertTriangle size={18} />} />
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <Card className="p-5 lg:col-span-2">
          <div className="mb-4 text-[15px] font-medium text-ink">Pipeline des essais</div>
          <div className="space-y-3">
            {pipeline.map((p) => (
              <div key={p.label} className="flex items-center gap-3">
                <div className="w-28 shrink-0 text-sm text-ink-muted">{p.label}</div>
                <div className="h-6 flex-1 overflow-hidden rounded-md bg-surface-2">
                  <div className="h-full rounded-md bg-brand/80" style={{ width: `${(p.n / max) * 100}%` }} />
                </div>
                <div className="w-6 text-right text-sm font-medium text-ink">{p.n}</div>
              </div>
            ))}
          </div>
        </Card>

        <Card className="p-5">
          <div className="mb-4 text-[15px] font-medium text-ink">À arbitrer</div>
          <div className="space-y-3">
            {trials
              .filter((t) => t.status === 'Décision' || t.status === 'Évaluation')
              .map((t) => (
                <Link key={t.id} to={`/trials/${t.id}`} className="block rounded-md border border-line p-3 hover:bg-surface-2">
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-ink">{t.variety}</span>
                    <Badge tone={t.statusTone}>{t.status}</Badge>
                  </div>
                  <div className="mt-1 text-xs text-ink-faint">
                    {t.code} · {t.culture} · {t.site}
                  </div>
                </Link>
              ))}
          </div>
        </Card>
      </div>
    </div>
  )
}
