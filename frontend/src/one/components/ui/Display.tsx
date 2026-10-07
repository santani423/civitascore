import { useId, useState, type ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ChevronDown, ChevronRight, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { tone as tones, type UiTone } from '../../lib/tones'
import type { Tone } from '../../data/campus'
import { useI18n } from '../../i18n'

/* ---------- Avatar ---------- */

const avatarTones: UiTone[] = ['royal', 'teal', 'gold', 'sky', 'brand', 'ok']

export function initials(name: string): string {
  const words = name
    .replace(/^(Prof\.|Dr\.|dr\.|Ir\.|Rm\.)\s*/g, '')
    .replace(/,.*$/, '')
    .split(/\s+/)
    .filter((w) => /^[A-Za-z]/.test(w) && !/\.$/.test(w))
  return ((words[0]?.[0] ?? '') + (words[1]?.[0] ?? '')).toUpperCase() || name.slice(0, 2).toUpperCase()
}

interface AvatarProps {
  name: string
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
  className?: string
  ring?: boolean
}

export function Avatar({ name, size = 'md', className, ring }: AvatarProps) {
  const hash = Array.from(name).reduce((a, c) => a + c.charCodeAt(0), 0)
  const t = tones[avatarTones[hash % avatarTones.length]]
  return (
    <span
      role="img"
      aria-label={name}
      className={cn(
        'inline-flex shrink-0 select-none items-center justify-center rounded-full font-semibold',
        t.soft,
        size === 'xs' && 'h-6 w-6 text-[10px]',
        size === 'sm' && 'h-8 w-8 text-[12px]',
        size === 'md' && 'h-10 w-10 text-[13px]',
        size === 'lg' && 'h-14 w-14 text-[17px]',
        size === 'xl' && 'h-24 w-24 font-display text-[28px]',
        ring && 'ring-4 ring-one-card',
        className,
      )}
    >
      {initials(name)}
    </span>
  )
}

/* ---------- Progress ---------- */

interface ProgressProps {
  value: number
  max?: number
  tone?: UiTone
  label: string
  size?: 'sm' | 'md'
  className?: string
}

export function Progress({ value, max = 100, tone = 'royal', label, size = 'sm', className }: ProgressProps) {
  const pct = Math.max(0, Math.min(100, (value / max) * 100))
  return (
    <div
      role="progressbar"
      aria-label={label}
      aria-valuemin={0}
      aria-valuemax={max}
      aria-valuenow={value}
      className={cn('w-full overflow-hidden rounded-full bg-one-sunken', size === 'sm' ? 'h-1.5' : 'h-2.5', className)}
    >
      <div className={cn('h-full rounded-full transition-[width] duration-700 ease-out', tones[tone].solid)} style={{ width: `${pct}%` }} />
    </div>
  )
}

export function ProgressRing({
  value,
  size = 88,
  stroke = 8,
  tone = 'royal',
  label,
  children,
}: {
  value: number
  size?: number
  stroke?: number
  tone?: UiTone
  label: string
  children?: ReactNode
}) {
  const r = (size - stroke) / 2
  const c = 2 * Math.PI * r
  return (
    <div className="relative inline-flex shrink-0 items-center justify-center" style={{ width: size, height: size }}>
      <svg width={size} height={size} role="img" aria-label={label} className="-rotate-90">
        <circle cx={size / 2} cy={size / 2} r={r} fill="none" strokeWidth={stroke} className="stroke-one-sunken" />
        <circle
          cx={size / 2}
          cy={size / 2}
          r={r}
          fill="none"
          strokeWidth={stroke}
          strokeLinecap="round"
          strokeDasharray={c}
          strokeDashoffset={c * (1 - Math.min(100, value) / 100)}
          className={cn('transition-[stroke-dashoffset] duration-700 ease-out', tones[tone].text)}
          stroke="currentColor"
        />
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center">{children}</div>
    </div>
  )
}

/* ---------- Cover (generated art in place of photography) ---------- */

interface CoverProps {
  tone: Tone
  icon?: LucideIcon
  className?: string
  children?: ReactNode
  label?: string
}

export function Cover({ tone, icon: Icon, className, children, label }: CoverProps) {
  return (
    <div
      role={label ? 'img' : undefined}
      aria-label={label}
      aria-hidden={label ? undefined : true}
      className={cn('one-cover relative overflow-hidden', className)}
      style={{ backgroundImage: `linear-gradient(135deg, var(--cover-${tone}))` }}
    >
      <div className="one-cover-pattern absolute inset-0 opacity-60" />
      <div className="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/10" />
      <div className="absolute -bottom-16 -left-8 h-44 w-44 rounded-full bg-black/10" />
      {Icon && (
        <Icon aria-hidden strokeWidth={1.25} className="absolute bottom-3 right-3 h-16 w-16 text-white/25 sm:h-20 sm:w-20" />
      )}
      {children}
    </div>
  )
}

/* ---------- Stat card ---------- */

interface StatProps {
  label: string
  value: ReactNode
  icon?: LucideIcon
  tone?: UiTone
  hint?: ReactNode
  children?: ReactNode
  className?: string
}

export function Stat({ label, value, icon: Icon, tone = 'royal', hint, children, className }: StatProps) {
  return (
    <div className={cn('rounded-2xl border border-one-line bg-one-card p-4 shadow-soft sm:p-5', className)}>
      <div className="flex items-center justify-between gap-3">
        <p className="text-[13px] font-medium text-one-subtle">{label}</p>
        {Icon && (
          <span className={cn('flex h-9 w-9 items-center justify-center rounded-xl', tones[tone].soft)}>
            <Icon size={18} aria-hidden />
          </span>
        )}
      </div>
      <p className="mt-2 font-display text-[26px] font-bold leading-tight tracking-tight text-one-fg tabular sm:text-[28px]">{value}</p>
      {hint && <p className="t-small mt-1 text-one-subtle">{hint}</p>}
      {children}
    </div>
  )
}

/* ---------- Page header + breadcrumb ---------- */

export interface Crumb {
  label: string
  to?: string
}

export function Breadcrumb({ items }: { items: Crumb[] }) {
  const { t } = useI18n()
  return (
    <nav aria-label={t('common.breadcrumb')} className="mb-3">
      <ol className="flex flex-wrap items-center gap-1 text-[13px] text-one-subtle">
        {items.map((c, i) => (
          <li key={i} className="flex items-center gap-1">
            {i > 0 && <ChevronRight size={14} aria-hidden className="text-one-line2" />}
            {c.to ? (
              <Link to={c.to} className="rounded hover:text-one-fg hover:underline">
                {c.label}
              </Link>
            ) : (
              <span aria-current="page" className="font-medium text-one-muted">
                {c.label}
              </span>
            )}
          </li>
        ))}
      </ol>
    </nav>
  )
}

interface PageHeaderProps {
  title: ReactNode
  description?: ReactNode
  crumbs?: Crumb[]
  actions?: ReactNode
  eyebrow?: ReactNode
}

export function PageHeader({ title, description, crumbs, actions, eyebrow }: PageHeaderProps) {
  return (
    <header className="mb-6 sm:mb-8">
      {crumbs && <Breadcrumb items={crumbs} />}
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div className="min-w-0">
          {eyebrow && <div className="t-caption mb-2 text-one-royal">{eyebrow}</div>}
          <h1 className="t-h1 text-one-fg">{title}</h1>
          {description && <p className="t-body mt-1.5 max-w-2xl text-one-muted">{description}</p>}
        </div>
        {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
      </div>
    </header>
  )
}

/* ---------- Filter chips ---------- */

interface ChipsProps<V extends string> {
  options: Array<{ value: V; label: string; count?: number }>
  value: V
  onChange: (v: V) => void
  label: string
  className?: string
}

export function FilterChips<V extends string>({ options, value, onChange, label, className }: ChipsProps<V>) {
  return (
    <div role="group" aria-label={label} className={cn('no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0', className)}>
      {options.map((o) => {
        const active = o.value === value
        return (
          <button
            key={o.value}
            type="button"
            aria-pressed={active}
            onClick={() => onChange(o.value)}
            className={cn(
              'inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-[13px] font-medium transition-colors',
              active
                ? 'border-one-brand bg-one-brand text-one-onbrand'
                : 'border-one-line bg-one-card text-one-muted hover:border-one-line2 hover:text-one-fg',
            )}
          >
            {o.label}
            {o.count !== undefined && <span className={cn('tabular text-[12px]', active ? 'opacity-80' : 'text-one-subtle')}>{o.count}</span>}
          </button>
        )
      })}
    </div>
  )
}

/* ---------- Accordion ---------- */

export function Accordion({ items }: { items: Array<{ id: string; title: string; content: ReactNode }> }) {
  const [open, setOpen] = useState<string | null>(items[0]?.id ?? null)
  const base = useId()
  return (
    <div className="divide-y divide-one-line rounded-2xl border border-one-line bg-one-card">
      {items.map((item) => {
        const expanded = open === item.id
        return (
          <div key={item.id}>
            <h3>
              <button
                type="button"
                id={`${base}-${item.id}-h`}
                aria-expanded={expanded}
                aria-controls={`${base}-${item.id}-p`}
                onClick={() => setOpen(expanded ? null : item.id)}
                className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left text-[15px] font-medium text-one-fg hover:bg-one-sunken/50"
              >
                {item.title}
                <ChevronDown size={18} aria-hidden className={cn('shrink-0 text-one-subtle transition-transform', expanded && 'rotate-180')} />
              </button>
            </h3>
            {expanded && (
              <div id={`${base}-${item.id}-p`} role="region" aria-labelledby={`${base}-${item.id}-h`} className="t-body animate-fade-in px-5 pb-5 text-one-muted">
                {item.content}
              </div>
            )}
          </div>
        )
      })}
    </div>
  )
}

/* ---------- Tooltip ---------- */

export function Tooltip({ content, children, side = 'top' }: { content: string; children: ReactNode; side?: 'top' | 'right' | 'bottom' }) {
  const id = useId()
  return (
    <span className="group/tt relative inline-flex" aria-describedby={id}>
      {children}
      <span
        id={id}
        role="tooltip"
        className={cn(
          'pointer-events-none absolute z-50 whitespace-nowrap rounded-lg bg-one-fg px-2 py-1 text-[12px] font-medium text-one-canvas opacity-0 shadow-lift transition-opacity delay-150 group-hover/tt:opacity-100 group-focus-within/tt:opacity-100',
          side === 'top' && 'bottom-full left-1/2 mb-2 -translate-x-1/2',
          side === 'bottom' && 'left-1/2 top-full mt-2 -translate-x-1/2',
          side === 'right' && 'left-full top-1/2 ml-3 -translate-y-1/2',
        )}
      >
        {content}
      </span>
    </span>
  )
}

/* ---------- Misc ---------- */

export function Kbd({ children }: { children: ReactNode }) {
  return (
    <kbd className="inline-flex h-5 min-w-5 items-center justify-center rounded-md border border-one-line bg-one-card px-1 font-sans text-[11px] font-medium text-one-subtle">
      {children}
    </kbd>
  )
}

export function IconTile({ icon: Icon, tone = 'royal', size = 'md' }: { icon: LucideIcon; tone?: UiTone; size?: 'sm' | 'md' | 'lg' }) {
  return (
    <span
      className={cn(
        'flex shrink-0 items-center justify-center rounded-xl',
        tones[tone].soft,
        size === 'sm' && 'h-8 w-8',
        size === 'md' && 'h-10 w-10',
        size === 'lg' && 'h-12 w-12 rounded-2xl',
      )}
    >
      <Icon size={size === 'sm' ? 16 : size === 'md' ? 18 : 22} aria-hidden />
    </span>
  )
}

export function MetaItem({ icon: Icon, children }: { icon: LucideIcon; children: ReactNode }) {
  return (
    <span className="inline-flex min-w-0 items-center gap-1.5 text-[13px] text-one-subtle">
      <Icon size={14} aria-hidden className="shrink-0" />
      <span className="truncate">{children}</span>
    </span>
  )
}
