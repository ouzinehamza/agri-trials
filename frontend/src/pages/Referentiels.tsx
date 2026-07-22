import { useState } from 'react'
import { Plus, Upload, Download } from 'lucide-react'
import { Button, Card, PageHeader } from '../components/ui'
import { varieties, suppliers } from '../data/mock'

const tabs = ['Variétés', 'Témoins', 'Fournisseurs', 'Porte-greffes', 'Partenaires', 'Segments']

export default function Referentiels() {
  const [tab, setTab] = useState('Variétés')

  return (
    <div>
      <PageHeader
        title="Référentiels"
        subtitle="Variétés, témoins, fournisseurs, porte-greffes, partenaires et segments — données de référence."
        actions={
          <div className="flex gap-2">
            <Button variant="secondary"><Upload size={15} /> Importer (CSV/XLSX)</Button>
            <Button variant="secondary"><Download size={15} /> Exporter</Button>
            <Button variant="primary"><Plus size={15} /> Ajouter</Button>
          </div>
        }
      />

      <div className="mb-5 flex flex-wrap gap-2">
        {tabs.map((tb) => (
          <button
            key={tb}
            onClick={() => setTab(tb)}
            className={
              'rounded-full px-3.5 py-1.5 text-sm transition ' +
              (tab === tb ? 'bg-ink text-page' : 'bg-surface border border-line text-ink-muted hover:bg-surface-2')
            }
          >
            {tb}
          </button>
        ))}
      </div>

      <Card className="overflow-hidden">
        {tab === 'Variétés' ? (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                <th className="px-4 py-3 font-medium">Référence</th>
                <th className="px-4 py-3 font-medium">Nom</th>
                <th className="px-4 py-3 font-medium">Fournisseur</th>
                <th className="px-4 py-3 font-medium">Culture</th>
                <th className="px-4 py-3 font-medium">Origine</th>
                <th className="px-4 py-3 font-medium">Segment</th>
              </tr>
            </thead>
            <tbody>
              {varieties.map((v) => (
                <tr key={v.ref} className="border-b border-line last:border-0 hover:bg-surface-2">
                  <td className="px-4 py-3 font-medium text-ink">{v.ref}</td>
                  <td className="px-4 py-3 text-ink">{v.name}</td>
                  <td className="px-4 py-3 text-ink-muted">{v.supplier}</td>
                  <td className="px-4 py-3 text-ink-muted">{v.culture}</td>
                  <td className="px-4 py-3 text-ink-muted">{v.origin}</td>
                  <td className="px-4 py-3 text-ink-muted">{v.segment}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : tab === 'Fournisseurs' ? (
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-line text-left text-xs uppercase tracking-wide text-ink-faint">
                <th className="px-4 py-3 font-medium">Nom</th>
                <th className="px-4 py-3 font-medium">Pays</th>
                <th className="px-4 py-3 font-medium">Variétés</th>
              </tr>
            </thead>
            <tbody>
              {suppliers.map((s) => (
                <tr key={s.name} className="border-b border-line last:border-0 hover:bg-surface-2">
                  <td className="px-4 py-3 font-medium text-ink">{s.name}</td>
                  <td className="px-4 py-3 text-ink-muted">{s.country}</td>
                  <td className="px-4 py-3 text-ink-muted">{s.varieties}</td>
                </tr>
              ))}
            </tbody>
          </table>
        ) : (
          <div className="p-10 text-center text-sm text-ink-faint">
            « {tab} » — même modèle (table + champs personnalisés + import) que les autres référentiels.
          </div>
        )}
      </Card>
    </div>
  )
}
