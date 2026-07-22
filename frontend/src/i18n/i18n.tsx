import { createContext, useContext, useState, type ReactNode } from 'react'

export type Lang = 'fr' | 'en'

const dict: Record<string, { fr: string; en: string }> = {
  dashboard: { fr: 'Tableau de bord', en: 'Dashboard' },
  workspaces: { fr: 'Espaces de travail', en: 'Workspaces' },
  trials: { fr: 'Essais', en: 'Trials' },
  referentiels: { fr: 'Référentiels', en: 'Reference data' },
  stock: { fr: 'Stock', en: 'Stock' },
  expenses: { fr: 'Charges & factures', en: 'Expenses & invoices' },
  configuration: { fr: 'Configuration', en: 'Configuration' },
  branding: { fr: 'Marque & thème', en: 'Brand & theme' },
  users: { fr: 'Utilisateurs', en: 'Users' },
  newTrial: { fr: 'Nouvel essai', en: 'New trial' },
  search: { fr: 'Rechercher…', en: 'Search…' },
  all: { fr: 'Tous', en: 'All' },
  active: { fr: 'Actifs', en: 'Active' },
  decision: { fr: 'Décision', en: 'Decision' },
  overview: { fr: 'Aperçu', en: 'Overview' },
  weightedScore: { fr: 'Score global pondéré', en: 'Weighted score' },
  launch: { fr: 'Lancer en production', en: 'Launch to production' },
  retrial: { fr: 'Re-tester', en: 'Re-trial' },
  reject: { fr: 'Rejeter', en: 'Reject' },
  vsControl: { fr: 'vs témoin', en: 'vs control' },
  weight: { fr: 'Poids', en: 'Weight' },
}

type I18nContextValue = { lang: Lang; setLang: (l: Lang) => void; t: (k: string) => string }
const I18nContext = createContext<I18nContextValue | null>(null)

export function I18nProvider({ children }: { children: ReactNode }) {
  const [lang, setLang] = useState<Lang>('fr')
  const t = (k: string) => dict[k]?.[lang] ?? k
  return <I18nContext.Provider value={{ lang, setLang, t }}>{children}</I18nContext.Provider>
}

export function useI18n() {
  const ctx = useContext(I18nContext)
  if (!ctx) throw new Error('useI18n must be used within I18nProvider')
  return ctx
}
