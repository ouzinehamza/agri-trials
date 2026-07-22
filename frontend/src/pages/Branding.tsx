import { RotateCcw, Check } from 'lucide-react'
import { Button, Card, PageHeader } from '../components/ui'
import { useTheme } from '../theme/ThemeProvider'
import { fontOptions, presetColors } from '../theme/theme'
import { cn } from '../lib/cn'

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="mb-1.5 block text-[13px] font-medium text-ink">{label}</label>
      {children}
    </div>
  )
}

export default function Branding() {
  const { theme, setTheme, reset } = useTheme()

  return (
    <div>
      <PageHeader
        title="Marque & thème"
        subtitle="L'administrateur personnalise l'identité de l'application — sans code. Les changements s'appliquent en direct."
        actions={
          <Button variant="ghost" onClick={reset}>
            <RotateCcw size={15} /> Réinitialiser
          </Button>
        }
      />

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-2">
        <Card className="space-y-5 p-6">
          <Field label="Nom de l'application">
            <input
              value={theme.appName}
              onChange={(e) => setTheme({ ...theme, appName: e.target.value })}
              className="w-full rounded-md border border-line bg-page px-3 py-2 text-sm outline-none focus:border-brand"
            />
          </Field>

          <Field label="Monogramme du logo">
            <input
              value={theme.logoText}
              maxLength={3}
              onChange={(e) => setTheme({ ...theme, logoText: e.target.value.toUpperCase() })}
              className="w-24 rounded-md border border-line bg-page px-3 py-2 text-sm outline-none focus:border-brand"
            />
          </Field>

          <Field label="Couleur principale">
            <div className="flex flex-wrap items-center gap-2">
              {presetColors.map((c) => (
                <button
                  key={c}
                  onClick={() => setTheme({ ...theme, primary: c })}
                  className={cn('flex h-8 w-8 items-center justify-center rounded-full border-2', theme.primary === c ? 'border-ink' : 'border-transparent')}
                  style={{ background: c }}
                  aria-label={c}
                >
                  {theme.primary === c && <Check size={14} color="#fff" />}
                </button>
              ))}
              <input
                type="color"
                value={theme.primary}
                onChange={(e) => setTheme({ ...theme, primary: e.target.value })}
                className="h-8 w-10 cursor-pointer rounded border border-line bg-transparent"
              />
            </div>
          </Field>

          <Field label="Couleur d'accent">
            <input
              type="color"
              value={theme.accent}
              onChange={(e) => setTheme({ ...theme, accent: e.target.value })}
              className="h-8 w-10 cursor-pointer rounded border border-line bg-transparent"
            />
          </Field>

          <Field label="Police">
            <select
              value={theme.fontSans}
              onChange={(e) => setTheme({ ...theme, fontSans: e.target.value })}
              className="w-full rounded-md border border-line bg-page px-3 py-2 text-sm outline-none focus:border-brand"
            >
              {fontOptions.map((f) => (
                <option key={f.value} value={f.value}>
                  {f.label}
                </option>
              ))}
            </select>
          </Field>

          <Field label={`Arrondi des angles — ${theme.radius}px`}>
            <input
              type="range"
              min={0}
              max={20}
              step={1}
              value={theme.radius}
              onChange={(e) => setTheme({ ...theme, radius: Number(e.target.value) })}
              className="w-full"
            />
          </Field>

          <Field label="Densité">
            <div className="flex gap-2">
              {(['comfortable', 'compact'] as const).map((d) => (
                <button
                  key={d}
                  onClick={() => setTheme({ ...theme, density: d })}
                  className={cn(
                    'rounded-md border px-3 py-1.5 text-sm',
                    theme.density === d ? 'border-brand bg-brand/10 text-brand' : 'border-line text-ink-muted',
                  )}
                >
                  {d === 'comfortable' ? 'Confortable' : 'Compacte'}
                </button>
              ))}
            </div>
          </Field>
        </Card>

        <Card className="p-6">
          <div className="mb-4 text-[13px] font-medium text-ink-muted">Aperçu en direct</div>
          <div className="rounded-lg border border-line p-5">
            <div className="flex items-center gap-2.5">
              <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand text-sm font-medium text-brand-fg">
                {theme.logoText}
              </div>
              <div className="text-[17px] font-medium text-ink">{theme.appName}</div>
            </div>

            <div className="mt-5 flex flex-wrap gap-2">
              <Button variant="primary">Bouton principal</Button>
              <Button variant="secondary">Secondaire</Button>
            </div>

            <div className="mt-5 space-y-2">
              <div className="flex items-center justify-between rounded-md bg-surface-2 p-3">
                <span className="text-sm text-ink">CLX 7702</span>
                <span className="rounded-md bg-success-soft px-2.5 py-1 text-xs font-medium text-success">Lancement</span>
              </div>
              <div className="h-2 overflow-hidden rounded-full bg-surface-2">
                <div className="h-full w-3/4 rounded-full bg-brand" />
              </div>
            </div>

            <p className="mt-5 text-sm text-ink-muted">
              Chaque écran lit les mêmes variables de thème, donc un changement de marque re-stylise
              instantanément toute l'application.
            </p>
          </div>
        </Card>
      </div>
    </div>
  )
}
