import type { ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { ArrowDownRight, ArrowUpRight, Lock } from 'lucide-react'
import { cn } from '@/utils/cn'
import { IllustrativeChip } from '../ui/Primitives'
import type { Tone } from '../../data/demo'

/** Desktop app window chrome around a dashboard mockup. */
export function MockWindow({ title, children, className, bodyClassName }: { title: string; children: ReactNode; className?: string; bodyClassName?: string }) {
  return (
    <div className={cn('overflow-hidden rounded-lp-lg border border-lp-line bg-lp-canvas shadow-lp-xl', className)}>
      <div className="flex h-10 items-center gap-3 border-b border-lp-line bg-lp-surface px-4">
        <span aria-hidden className="flex gap-1.5">
          <span className="h-2.5 w-2.5 rounded-full bg-lp-strong" />
          <span className="h-2.5 w-2.5 rounded-full bg-lp-strong" />
          <span className="h-2.5 w-2.5 rounded-full bg-lp-strong" />
        </span>
        <span className="mx-auto flex min-w-0 items-center gap-1.5 rounded-full bg-lp-sunken px-3 py-1 text-[11.5px] text-lp-subtle">
          <Lock size={11} aria-hidden className="shrink-0" />
          <span className="truncate">{title}</span>
        </span>
        <IllustrativeChip className="hidden sm:inline-flex" />
      </div>
      <div className={bodyClassName}>{children}</div>
    </div>
  )
}

/** Phone chrome for mobile-view mockups. */
export function PhoneFrame({ children, className }: { children: ReactNode; className?: string }) {
  return (
    <div className={cn('relative w-[232px] rounded-[2.25rem] border border-lp-strong bg-lp-surface p-2 shadow-lp-xl', className)}>
      <div className="relative overflow-hidden rounded-[1.75rem] border border-lp-line bg-lp-canvas">
        <div aria-hidden className="absolute left-1/2 top-2 z-10 h-[18px] w-20 -translate-x-1/2 rounded-full bg-lp-fg/90" />
        <div className="pt-8">{children}</div>
      </div>
    </div>
  )
}

/** A card inside a mockup. */
export function MockPanel({ title, action, children, className }: { title?: ReactNode; action?: ReactNode; children: ReactNode; className?: string }) {
  return (
    <div className={cn('rounded-lp border border-lp-line bg-lp-surface p-4', className)}>
      {(title || action) && (
        <div className="mb-3 flex items-center justify-between gap-3">
          {title && <p className="truncate text-[13px] font-semibold text-lp-fg">{title}</p>}
          {action}
        </div>
      )}
      {children}
    </div>
  )
}

interface KpiTileProps {
  label: string
  value: string
  /** Pre-formatted change (e.g. "2,4%") and its direction. */
  trend?: { up: boolean; text: string }
  trendLabel?: string
  icon?: LucideIcon
  className?: string
  /** Visual size of the value. */
  size?: 'sm' | 'md'
}

export function KpiTile({ label, value, trend, trendLabel, icon: Icon, className, size = 'md' }: KpiTileProps) {
  return (
    <div className={cn('min-w-0 rounded-lp border border-lp-line bg-lp-surface p-3.5', className)}>
      <div className="flex items-center justify-between gap-2">
        <p className="truncate text-[12px] font-medium text-lp-muted">{label}</p>
        {Icon && (
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-lp-brand/10 text-lp-brand-text">
            <Icon size={13} aria-hidden />
          </span>
        )}
      </div>
      <p className={cn('lp-tabular mt-1.5 font-display font-bold tracking-tight text-lp-fg', size === 'md' ? 'text-[22px]' : 'text-[18px]')}>{value}</p>
      {trend && (
        <p className="mt-1 flex items-center gap-1 truncate text-[11px] text-lp-subtle">
          <span className={cn('inline-flex items-center font-semibold', trend.up ? 'text-lp-ok' : 'text-lp-bad')}>
            {trend.up ? <ArrowUpRight size={12} aria-hidden /> : <ArrowDownRight size={12} aria-hidden />}
            {trend.text}
          </span>
          {trendLabel}
        </p>
      )}
    </div>
  )
}

const avatarTones = ['bg-lp-brand/10 text-lp-brand-text', 'bg-lp-gold/15 text-lp-gold-text', 'bg-lp-info/10 text-lp-info', 'bg-lp-ok/10 text-lp-ok']

export function Avatar({ name, size = 28, className }: { name: string; size?: number; className?: string }) {
  const initials = name
    .replace(/^(Dr\.|Prof\.)\s*/i, '')
    .split(/[\s,]+/)
    .filter((p) => p && /^[A-Za-z]/.test(p) && !p.includes('.'))
    .slice(0, 2)
    .map((p) => p[0]?.toUpperCase())
    .join('')
  const tone = avatarTones[[...name].reduce((sum, c) => sum + c.charCodeAt(0), 0) % avatarTones.length]
  return (
    <span
      aria-hidden
      className={cn('inline-flex shrink-0 items-center justify-center rounded-full font-semibold', tone, className)}
      style={{ width: size, height: size, fontSize: Math.round(size * 0.38) }}
    >
      {initials}
    </span>
  )
}

const toneClass: Record<Tone | 'ok' | 'warn' | 'muted', string> = {
  brand: 'bg-lp-brand/10 text-lp-brand-text',
  gold: 'bg-lp-gold/15 text-lp-gold-text',
  info: 'bg-lp-info/10 text-lp-info',
  ok: 'bg-lp-ok/10 text-lp-ok',
  warn: 'bg-lp-warn/10 text-lp-warn',
  muted: 'bg-lp-sunken text-lp-muted',
}

export function MockChip({ tone = 'muted', children, className }: { tone?: keyof typeof toneClass; children: ReactNode; className?: string }) {
  return <span className={cn('inline-flex shrink-0 items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10.5px] font-semibold', toneClass[tone], className)}>{children}</span>
}

/** Thin progress track. `value` is 0–100. */
export function Progress({ value, className, tone = 'brand' }: { value: number; className?: string; tone?: 'brand' | 'gold' | 'ok' }) {
  return (
    <div className={cn('h-1.5 overflow-hidden rounded-full bg-lp-sunken', className)}>
      <div
        className={cn('h-full rounded-full', tone === 'brand' && 'bg-lp-viz1', tone === 'gold' && 'bg-lp-viz2', tone === 'ok' && 'bg-lp-ok')}
        style={{ width: `${Math.max(0, Math.min(100, value))}%` }}
      />
    </div>
  )
}
