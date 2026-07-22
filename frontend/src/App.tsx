import { BrowserRouter, Routes, Route } from 'react-router-dom'
import { ThemeProvider } from './theme/ThemeProvider'
import { I18nProvider } from './i18n/i18n'
import Layout from './components/Layout'
import Dashboard from './pages/Dashboard'
import Trials from './pages/Trials'
import TrialDetail from './pages/TrialDetail'
import Decision from './pages/Decision'
import Referentiels from './pages/Referentiels'
import Stock from './pages/Stock'
import Branding from './pages/Branding'
import Placeholder from './pages/Placeholder'

export default function App() {
  return (
    <ThemeProvider>
      <I18nProvider>
        <BrowserRouter>
          <Routes>
            <Route element={<Layout />}>
              <Route index element={<Dashboard />} />
              <Route path="trials" element={<Trials />} />
              <Route path="trials/:id" element={<TrialDetail />} />
              <Route path="trials/:id/decision" element={<Decision />} />
              <Route path="referentiels" element={<Referentiels />} />
              <Route path="stock" element={<Stock />} />
              <Route
                path="expenses"
                element={<Placeholder title="Charges & factures" note="Charges par essai / espace / partenaire, et factures des tiers (ex. pépinière externe) avec suivi du statut de paiement." />}
              />
              <Route
                path="configuration"
                element={<Placeholder title="Configuration" note="Éditeur de champs personnalisés par modèle, catalogue de mesures, et éditeur de modèles de workflow (cycle de vie) — le cœur métadonnées de l'application." />}
              />
              <Route path="branding" element={<Branding />} />
              <Route
                path="users"
                element={<Placeholder title="Utilisateurs" note="Gestion des utilisateurs (internes / externes) et des rôles (RBAC) par espace de travail." />}
              />
            </Route>
          </Routes>
        </BrowserRouter>
      </I18nProvider>
    </ThemeProvider>
  )
}
