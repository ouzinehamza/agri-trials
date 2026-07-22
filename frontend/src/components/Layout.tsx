import { NavLink, Outlet, useLocation } from 'react-router-dom'
import {
  LayoutDashboard,
  FlaskConical,
  Boxes,
  Database,
  Receipt,
  SlidersHorizontal,
  Palette,
  Users,
  Search,
  Globe,
} from 'lucide-react'
import { useTheme } from '../theme/ThemeProvider'
import { useI18n } from '../i18n/i18n'
import { cn } from '../lib/cn'

const nav = [
  { to: '/', key: 'dashboard', icon: LayoutDashboard, end: true },
  { to: '/trials', key: 'trials', icon: FlaskConical },
  { to: '/referentiels', key: 'referentiels', icon: Database },
  { to: '/stock', key: 'stock', icon: Boxes },
  { to: '/expenses', key: 'expenses', icon: Receipt },
  { to: '/configuration', key: 'configuration', icon: SlidersHorizontal },
  { to: '/branding', key: 'branding', icon: Palette },
  { to: '/users', key: 'users', icon: Users },
]

export default function Layout() {
  const { theme } = useTheme()
  const { t, lang, setLang } = useI18n()
  const loc = useLocation()

  return (
    <div className="flex min-h-screen">
      <aside className="hidden w-64 shrink-0 flex-col border-r border-line bg-surface md:flex">
        <div className="flex items-center gap-2.5 px-5 py-5">
          <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-brand text-sm font-medium text-brand-fg">
            {theme.logoText}
          </div>
          <div>
            <div className="text-[15px] font-medium leading-tight text-ink">{theme.appName}</div>
            <div className="text-[11px] text-ink-faint">Division Semences</div>
          </div>
        </div>
        <nav className="flex-1 space-y-0.5 px-3 py-2">
          {nav.map((item) => {
            const Icon = item.icon
            return (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  cn(
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition',
                    isActive ? 'bg-brand/10 font-medium text-brand' : 'text-ink-muted hover:bg-surface-2 hover:text-ink',
                  )
                }
              >
                <Icon size={18} strokeWidth={1.75} />
                {t(item.key)}
              </NavLink>
            )
          })}
        </nav>
        <div className="border-t border-line p-4">
          <div className="flex items-center gap-3">
            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-info-soft text-xs font-medium text-info">AB</div>
            <div className="leading-tight">
              <div className="text-[13px] font-medium text-ink">Assma Benhammou</div>
              <div className="text-[11px] text-ink-faint">Administrateur</div>
            </div>
          </div>
        </div>
      </aside>

      <div className="flex min-w-0 flex-1 flex-col">
        <header className="flex items-center gap-3 border-b border-line bg-surface px-5 py-3">
          <div className="relative flex-1 max-w-md">
            <Search size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-faint" />
            <input
              placeholder={t('search')}
              className="w-full rounded-md border border-line bg-page py-2 pl-9 pr-3 text-sm outline-none placeholder:text-ink-faint focus:border-brand"
            />
          </div>
          <button
            onClick={() => setLang(lang === 'fr' ? 'en' : 'fr')}
            className="flex items-center gap-1.5 rounded-md border border-line px-2.5 py-1.5 text-xs font-medium text-ink-muted hover:bg-surface-2"
          >
            <Globe size={14} />
            {lang.toUpperCase()}
          </button>
        </header>
        <main key={loc.pathname} className="flex-1 overflow-y-auto p-6 lg:p-8">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
