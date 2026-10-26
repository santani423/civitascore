import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import type { ActivityStatus } from '../../data/student-affairs'
import { STATUS_STYLE } from './styles'

const pill = 'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[0.75rem] font-semibold leading-none ring-1 ring-inset'

export function CategoryBadge({ label, lang, className }: { label: string; lang?: string; className?: string }) {
  return (
    <span lang={lang} className={cn(pill, 'bg-lp-surface text-lp-fg ring-lp-line', className)}>
      {label}
    </span>
  )
}

/** Upcoming / ongoing / completed — always icon + text. */
export function StatusBadge({ status, className }: { status: ActivityStatus; className?: string }) {
  const { m } = useLandingI18n()
  const { icon: Icon, className: tone } = STATUS_STYLE[status]
  return (
    <span className={cn(pill, 'bg-lp-surface', tone, className)}>
      <Icon size={13} aria-hidden className="shrink-0" />
      {m.affairs.status[status]}
    </span>
  )
}

/** Marks an entry as demonstration content. */
export function DemoBadge({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  return (
    <span className={cn(pill, 'bg-transparent text-lp-subtle ring-lp-strong uppercase tracking-[0.08em]', className)}>
      {m.affairs.demo.badge}
    </span>
  )
}
