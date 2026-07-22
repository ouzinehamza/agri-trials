# Agri-Trials — Frontend prototype

Clickable React prototype (French, responsive, mock data — no backend yet). Validates the flow and
the design system before the Laravel API is wired in. See `../docs/` for the full spec.

## Run
```bash
npm install
npm run dev      # http://localhost:5173
npm run build    # type-check + production build
```

## What's here
- **Shell** — sidebar nav + topbar, FR/EN toggle (`src/components/Layout.tsx`).
- **Live theming** — admin-controllable brand/colors/fonts/radius via CSS variables
  (`src/theme/`, page `src/pages/Branding.tsx`). Persisted to `localStorage`.
- **Screens** — Dashboard, Essais (list + filters), Essai detail (configurable lifecycle stepper),
  **Décision** (interactive weighted scorecard), Référentiels, Stock. Placeholders for
  Configuration / Charges & factures / Utilisateurs.
- **Mock data** — `src/data/mock.ts` (trials, measurements, référentiels, stock).

## Notes
- Tailwind theme maps to CSS custom properties so the admin theme re-styles every screen live.
- **This standalone SPA is a prototype/component library.** The real app is **Laravel + Inertia +
  React**: these components/design system/theme port over; routing (react-router → Inertia) and data
  (mock → controller props) are swapped when the Inertia app is scaffolded.
