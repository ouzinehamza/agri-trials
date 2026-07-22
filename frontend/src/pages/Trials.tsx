import { useState } from 'react'
import { Link } from 'react-router-dom'
import { Plus, MapPin, User } from 'lucide-react'
import { Badge, Button, Card, PageHeader } from '../components/ui'
import { trials } from '../data/mock'

const filters = ['Tous', 'Actifs', 'Création', 'Semis', 'Greffage', 'Évaluation', 'Clôturé']

export default function Trials() {
  const [filter, setFilter] = useState('Tous')

  const shown = trials.filter((t) => {
    if (filter === 'Tous') return true
    if (filter === 'Actifs') return t.status !== 'Clôturé'
    return t.status === filter || (filter === 'Clôturé' && t.status === 'Clôturé')
  })

  return (
    <div>
      <PageHeader
        title="Essais"
        subtitle={`${trials.length} essais variétaux`}
        actions={
          <Button variant="primary">
            <Plus size={16} /> Nouvel essai
          </Button>
        }
      />

      <div className="mb-5 flex flex-wrap gap-2">
        {filters.map((f) => (
          <button
            key={f}
            onClick={() => setFilter(f)}
            className={
              'rounded-full px-3.5 py-1.5 text-sm transition ' +
              (filter === f ? 'bg-ink text-page' : 'bg-surface border border-line text-ink-muted hover:bg-surface-2')
            }
          >
            {f}
          </button>
        ))}
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        {shown.map((t) => (
          <Link key={t.id} to={`/trials/${t.id}`}>
            <Card className="h-full p-5 transition hover:border-brand/50">
              <div className="flex items-start justify-between">
                <div>
                  <div className="text-[15px] font-medium text-ink">{t.variety}</div>
                  <div className="mt-0.5 text-xs text-ink-faint">{t.code}</div>
                </div>
                <Badge tone={t.statusTone}>{t.status}</Badge>
              </div>
              <div className="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-ink-muted">
                <span>{t.culture}</span>
                <span className="text-ink-faint">·</span>
                <span>{t.conduct}</span>
              </div>
              <div className="mt-4 flex items-center justify-between border-t border-line pt-3 text-xs text-ink-faint">
                <span className="flex items-center gap-1">
                  <User size={13} /> {t.owner}
                </span>
                <span className="flex items-center gap-1">
                  <MapPin size={13} /> {t.site}
                </span>
              </div>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  )
}
