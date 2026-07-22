export type ThemeSettings = {
  appName: string
  logoText: string
  primary: string
  accent: string
  fontSans: string
  radius: number
  density: 'comfortable' | 'compact'
}

export const defaultTheme: ThemeSettings = {
  appName: 'Agri-Trials',
  logoText: 'AT',
  primary: '#1D9E75',
  accent: '#378ADD',
  fontSans: "'Inter', system-ui, sans-serif",
  radius: 10,
  density: 'comfortable',
}

export const fontOptions = [
  { label: 'Inter (défaut)', value: "'Inter', system-ui, sans-serif" },
  { label: 'System UI', value: 'system-ui, -apple-system, sans-serif' },
  { label: 'Georgia (serif)', value: 'Georgia, Cambria, serif' },
  { label: 'Roboto Mono', value: "'Roboto Mono', ui-monospace, monospace" },
]

export const presetColors = ['#1D9E75', '#0F6E56', '#378ADD', '#7F77DD', '#D85A30', '#BA7517', '#C22D2D', '#1C201E']

function hexToRgbTriplet(hex: string): string {
  const h = hex.replace('#', '')
  const n = h.length === 3 ? h.split('').map((c) => c + c).join('') : h
  const r = parseInt(n.slice(0, 2), 16)
  const g = parseInt(n.slice(2, 4), 16)
  const b = parseInt(n.slice(4, 6), 16)
  return `${r} ${g} ${b}`
}

function darken(hex: string, amount = 0.18): string {
  const t = hexToRgbTriplet(hex).split(' ').map(Number)
  const d = t.map((v) => Math.max(0, Math.round(v * (1 - amount))))
  return `${d[0]} ${d[1]} ${d[2]}`
}

function readableFg(hex: string): string {
  const [r, g, b] = hexToRgbTriplet(hex).split(' ').map(Number)
  const lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255
  return lum > 0.6 ? '28 32 30' : '255 255 255'
}

export function applyTheme(t: ThemeSettings) {
  const root = document.documentElement
  root.style.setProperty('--brand-primary', hexToRgbTriplet(t.primary))
  root.style.setProperty('--brand-hover', darken(t.primary))
  root.style.setProperty('--brand-fg', readableFg(t.primary))
  root.style.setProperty('--accent', hexToRgbTriplet(t.accent))
  root.style.setProperty('--font-sans', t.fontSans)
  root.style.setProperty('--radius', `${t.radius}px`)
  root.style.setProperty('--density', t.density === 'compact' ? '0.8' : '1')
}
