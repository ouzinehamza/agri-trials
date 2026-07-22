import { Plus } from 'lucide-react'
import { Badge, Button, Card, PageHeader } from '../components/ui'
import { stockItems } from '../data/mock'

export default function Stock() {
  return (
    <div>
      <PageHeader
        title="Gestion de stock"
        subtitle="Variétés & témoins — stock, alertes, mouvements. Le semis consomme automatiquement le stock."
        actions={<Button variant="primary"><Plus size={15} /> Mouvement</Button>}
      />

      <Card className="overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
              <th className="px-4 py-3 font-medium">Référence</th>
              <th className="px-4 py-3 font-medium">Stock actuel</th>
              <th className="px-4 py-3 font-medium">Seuil alerte</th>
              <th className="px-4 py-3 font-medium">Germination</th>
              <th className="px-4 py-3 font-medium">Pureté</th>
              <th className="px-4 py-3 font-medium">État</th>
            </tr>
          </thead>
          <tbody>
            {stockItems.map((s) => (
              <tr key={s.ref} className="border-b border-line last:border-0 hover:bg-surface-2">
                <td className="px-4 py-3">
                  <div className="font-medium text-ink">{s.ref}</div>
                  <div className="text-xs text-ink-faint">{s.name}</div>
                </td>
                <td className="px-4 py-3 font-medium text-ink">{s.stock}</td>
                <td className="px-4 py-3 text-ink-muted">{s.threshold}</td>
                <td className="px-4 py-3 text-ink-muted">{s.germination}%</td>
                <td className="px-4 py-3 text-ink-muted">{s.purity}%</td>
                <td className="px-4 py-3">
                  <Badge tone={s.tone}>
                    {s.tone === 'danger' ? 'Rupture' : s.tone === 'warning' ? 'Bas' : 'OK'}
                  </Badge>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </div>
  )
}
