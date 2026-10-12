import type { CSSProperties, ReactNode } from 'react'
import { cn } from '@/utils/cn'
import { useInView } from '../../hooks/useInView'
import { useLandingI18n } from '../../i18n'
import type { CapabilityStatus } from '../../data/catalog'

/** Fades + slides its children in the first time they enter the viewport. */
export function Reveal({ children, delay = 0, className }: { children: ReactNode; delay?: number; className?: string }) {
  const [ref, visible] = useInView<HTMLDivElement>()
  // The delay is a per-instance CSS variable; everything else lives in landing.css.
  const style = delay ? ({ '--lp-delay': `${delay}ms` } as CSSProperties) : undefined
  return (
    <div ref={ref} className={cn('lp-reveal', className)} data-visible={visible} style={style}>
      {children}
    </div>
  )
}

interface SectionHeaderProps {
  id: string
  eyebrow: string
  title: ReactNode
  description?: ReactNode
  align?: 'left' | 'center'
  tone?: 'default' | 'band'
  className?: string
  children?: ReactNode
}

/** Eyebrow + h2 + lead. `id` is the heading id that the section's aria-labelledby points to. */
export function SectionHeader({ id, eyebrow, title, description, align = 'left', tone = 'default', className, children }: SectionHeaderProps) {
  const band = tone === 'band'
  return (
    <Reveal className={cn('max-w-3xl', align === 'center' && 'mx-auto text-center', className)}>
      <p className={cn('lp-eyebrow', band ? 'text-lp-gold' : 'text-lp-brand-text')}>{eyebrow}</p>
      <h2 id={id} className={cn('lp-h2 mt-4', band ? 'text-lp-band-fg' : 'text-lp-fg')}>
        {title}
      </h2>
      {description && <p className={cn('lp-lead mt-5', band ? 'text-lp-band-muted' : 'text-lp-muted')}>{description}</p>}
      {children}
    </Reveal>
  )
}

const statusTone: Record<CapabilityStatus, string> = {
  available: 'bg-lp-ok/10 text-lp-ok ring-lp-ok/20',
  phased: 'bg-lp-gold/10 text-lp-gold-text ring-lp-gold/25',
  planned: 'bg-lp-sunken text-lp-muted ring-lp-strong',
}
const statusDot: Record<CapabilityStatus, string> = {
  available: 'bg-lp-ok',
  phased: 'bg-lp-gold',
  planned: 'border border-lp-subtle bg-transparent',
}

/** Development status of a module/capability — always text + shape, never color alone. */
export function StatusPill({ status, className, compact }: { status: CapabilityStatus; className?: string; compact?: boolean }) {
  const { m } = useLandingI18n()
  const hint = { available: m.status.availableHint, phased: m.status.phasedHint, planned: m.status.plannedHint }[status]
  return (
    <span
      title={hint}
      className={cn(
        'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full font-medium ring-1 ring-inset',
        compact ? 'px-2 py-0.5 text-[11px]' : 'px-2.5 py-1 text-[12px]',
        statusTone[status],
        className,
      )}
    >
      <span aria-hidden className={cn('h-1.5 w-1.5 rounded-full', statusDot[status])} />
      {m.status[status]}
    </span>
  )
}

export function StatusLegend({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  const items: CapabilityStatus[] = ['available', 'phased', 'planned']
  return (
    <div className={cn('flex flex-wrap items-center gap-x-5 gap-y-3', className)}>
      <span className="lp-small font-medium text-lp-muted">{m.status.legendTitle}</span>
      {items.map((s) => (
        <span key={s} className="flex items-center gap-2">
          <StatusPill status={s} compact />
          <span className="text-[12.5px] text-lp-subtle">{{ available: m.status.availableHint, phased: m.status.phasedHint, planned: m.status.plannedHint }[s]}</span>
        </span>
      ))}
    </div>
  )
}

/** Small "Illustrative data" chip shown on every dashboard mockup. */
export function IllustrativeChip({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  return (
    <span
      title={m.common.illustrativeNote}
      className={cn(
        'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-dashed border-lp-strong px-2 py-0.5 text-[10.5px] font-medium text-lp-subtle',
        className,
      )}
    >
      <span aria-hidden className="h-1 w-1 rounded-full bg-lp-gold" />
      {m.common.illustrative}
    </span>
  )
}
