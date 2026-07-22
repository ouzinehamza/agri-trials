import type { ReactNode } from 'react'
import { cn } from '../lib/cn'

type Tone = 'success' | 'warning' | 'info' | 'neutral' | 'danger'

const toneMap: Record<Tone, string> = {
  success: 'bg-success-soft text-success',
  warning: 'bg-warning-soft text-warning',
  info: 'bg-info-soft text-info',
  danger: 'bg-danger-soft text-danger',
  neutral: 'bg-surface-2 text-ink-muted',
}

export function Badge({ children, tone = 'neutral', className }: { children: ReactNode; tone?: Tone; className?: string }) {
  return (
    <span className={cn('inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium', toneMap[tone], className)}>
      {children}
    </span>
  )
}

export function Card({ children, className }: { children: ReactNode; className?: string }) {
  return <div className={cn('rounded-lg border border-line bg-surface', className)}>{children}</div>
}

export function StatCard({ label, value, sub, icon }: { label: string; value: ReactNode; sub?: ReactNode; icon?: ReactNode }) {
  return (
    <div className="rounded-lg bg-surface-2 p-4">
      <div className="flex items-center justify-between">
        <span className="text-[13px] text-ink-muted">{label}</span>
        {icon && <span className="text-ink-faint">{icon}</span>}
      </div>
      <div className="mt-1 text-2xl font-medium text-ink">{value}</div>
      {sub && <div className="mt-0.5 text-xs text-ink-faint">{sub}</div>}
    </div>
  )
}

export function Button({
  children,
  variant = 'secondary',
  className,
  onClick,
  type = 'button',
}: {
  children: ReactNode
  variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
  className?: string
  onClick?: () => void
  type?: 'button' | 'submit'
}) {
  const styles: Record<string, string> = {
    primary: 'bg-brand text-brand-fg hover:bg-brand-hover border-transparent',
    secondary: 'bg-surface text-ink border-line hover:bg-surface-2',
    ghost: 'bg-transparent text-ink-muted border-transparent hover:bg-surface-2',
    danger: 'bg-surface text-danger border-danger/30 hover:bg-danger-soft',
  }
  return (
    <button
      type={type}
      onClick={onClick}
      className={cn(
        'inline-flex items-center gap-2 rounded-md border px-3.5 py-2 text-sm font-medium transition active:scale-[0.98]',
        styles[variant],
        className,
      )}
    >
      {children}
    </button>
  )
}

export function PageHeader({ title, subtitle, actions }: { title: string; subtitle?: string; actions?: ReactNode }) {
  return (
    <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 className="text-[22px] font-medium text-ink">{title}</h1>
        {subtitle && <p className="mt-1 text-sm text-ink-muted">{subtitle}</p>}
      </div>
      {actions}
    </div>
  )
}
