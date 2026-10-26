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
  className?: string
  children?: ReactNode
}

/** Eyebrow + h2 + lead. `id` is the heading id that the section's aria-labelledby points to. */
export function SectionHeader({ id, eyebrow, title, description, align = 'left', className, children }: SectionHeaderProps) {
  return (
    <Reveal className={cn('max-w-2xl', align === 'center' && 'mx-auto text-center', className)}>
      <p className="lp-eyebrow text-lp-brand-text">{eyebrow}</p>
      <h2 id={id} className="lp-h2 mt-3 text-lp-fg">
        {title}
      </h2>
      {description && <p className="lp-lead mt-4 text-lp-muted">{description}</p>}
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

/** Development status of a service/capability — always text + shape, never color alone. */
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
