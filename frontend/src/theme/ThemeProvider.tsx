import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { applyTheme, defaultTheme, type ThemeSettings } from './theme'

type ThemeContextValue = {
  theme: ThemeSettings
  setTheme: (t: ThemeSettings) => void
  reset: () => void
}

const ThemeContext = createContext<ThemeContextValue | null>(null)

const STORAGE_KEY = 'agri-trials-theme'

export function ThemeProvider({ children }: { children: ReactNode }) {
  const [theme, setThemeState] = useState<ThemeSettings>(() => {
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      return raw ? { ...defaultTheme, ...JSON.parse(raw) } : defaultTheme
    } catch {
      return defaultTheme
    }
  })

  useEffect(() => {
    applyTheme(theme)
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(theme))
    } catch {
      /* ignore */
    }
  }, [theme])

  const setTheme = (t: ThemeSettings) => setThemeState(t)
  const reset = () => setThemeState(defaultTheme)

  return <ThemeContext.Provider value={{ theme, setTheme, reset }}>{children}</ThemeContext.Provider>
}

export function useTheme() {
  const ctx = useContext(ThemeContext)
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider')
  return ctx
}
