import { Link } from 'react-router-dom'
import { BookOpen, CalendarCheck, ClipboardList, FileCheck, Wallet, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'
import { kindTone, tone as tones } from '../../lib/tones'
import { sameDay } from '../../lib/dates'
import type { AgendaItem, AgendaKind } from '../../lib/agenda'

export const kindIcon: Record<AgendaKind, LucideIcon> = {
  lecture: BookOpen,
  exam: FileCheck,
  assignment: ClipboardList,
  event: CalendarCheck,
  payment: Wallet,
}

export function KindLegend({ kinds = ['lecture', 'exam', 'assignment', 'event', 'payment'] }: { kinds?: AgendaKind[] }) {
  const { t } = useI18n()
  return (
    <ul className="flex flex-wrap items-center gap-x-4 gap-y-2" aria-label={t('schedule.legend')}>
      {kinds.map((k) => {
        const Icon = kindIcon[k]
        return (
          <li key={k} className="inline-flex items-center gap-1.5 text-[12.5px] text-one-muted">
            <span className={cn('flex h-5 w-5 items-center justify-center rounded-md', tones[kindTone[k]].soft)}>
              <Icon size={12} aria-hidden />
            </span>
            {t(`kind.${k}`)}
          </li>
        )
      })}
    </ul>
  )
}

/** A single agenda entry: kind icon + label carries meaning, color reinforces it. */
export function AgendaRow({ item, compact }: { item: AgendaItem; compact?: boolean }) {
  const { t, fmt } = useI18n()
  const Icon = kindIcon[item.kind]
  const tn = tones[kindTone[item.kind]]
  return (
    <Link
      to={item.to}
      className={cn(
        'group flex items-start gap-3 rounded-xl border border-one-line bg-one-card transition-colors hover:border-one-line2 hover:bg-one-sunken/40',
        compact ? 'p-2.5' : 'p-3.5',
      )}
    >
      <span className={cn('mt-0.5 flex shrink-0 items-center justify-center rounded-lg', tn.soft, compact ? 'h-7 w-7' : 'h-9 w-9')}>
        <Icon size={compact ? 14 : 16} aria-hidden />
      </span>
      <span className="min-w-0 flex-1">
        <span className={cn('block truncate font-medium text-one-fg group-hover:text-one-royal', compact ? 'text-[13px]' : 'text-[14px]')}>{item.title}</span>
        <span className="mt-0.5 block truncate text-[12px] text-one-subtle">
          <span className={cn('font-medium', tn.text)}>{t(`kind.${item.kind}`)}</span>
          {!item.allDay && ` · ${fmt.time(item.start)}${item.end ? `–${fmt.time(item.end)}` : ''}`}
          {item.location && ` · ${item.location}`}
        </span>
      </span>
    </Link>
  )
}

interface MonthProps {
  month: Date
  days: Date[]
  items: AgendaItem[]
  selected: Date
  onSelect: (d: Date) => void
}

export function MonthView({ month, days, items, selected, onSelect }: MonthProps) {
  const { t, fmt } = useI18n()
  const today = new Date()
  const weekdayLabels = days.slice(0, 7).map((d) => fmt.weekday(d))

  return (
    <div role="grid" aria-label={fmt.month(month)} className="overflow-hidden rounded-2xl border border-one-line">
      <div role="row" className="grid grid-cols-7 border-b border-one-line bg-one-sunken/60">
        {weekdayLabels.map((w) => (
          <div role="columnheader" key={w} className="py-2.5 text-center text-[12px] font-semibold uppercase tracking-wide text-one-subtle">
            {w}
          </div>
        ))}
      </div>
      {Array.from({ length: 6 }, (_, row) => (
        <div role="row" key={row} className="grid grid-cols-7">
          {days.slice(row * 7, row * 7 + 7).map((d) => {
            const dayItems = items.filter((i) => sameDay(i.start, d))
            const inMonth = d.getMonth() === month.getMonth()
            const isToday = sameDay(d, today)
            const isSelected = sameDay(d, selected)
            return (
              <div role="gridcell" key={d.toISOString()} aria-selected={isSelected} className="border-b border-r border-one-line [&:nth-child(7n)]:border-r-0">
                <button
                  type="button"
                  onClick={() => onSelect(d)}
                  aria-label={`${fmt.date(d, { weekday: 'long', day: 'numeric', month: 'long' })}${dayItems.length ? ` — ${dayItems.length}` : ''}`}
                  className={cn(
                    'flex h-16 w-full flex-col items-stretch gap-1 p-1.5 text-left transition-colors sm:h-28 sm:p-2',
                    isSelected ? 'bg-one-royal/[0.07]' : 'hover:bg-one-sunken/50',
                    !inMonth && 'bg-one-sunken/30',
                  )}
                >
                  <span
                    className={cn(
                      'flex h-6 w-6 items-center justify-center self-center rounded-full text-[12.5px] font-medium tabular sm:self-start',
                      isToday ? 'bg-one-brand text-one-onbrand' : inMonth ? 'text-one-fg' : 'text-one-subtle/60',
                    )}
                  >
                    {d.getDate()}
                  </span>
                  {/* Mobile: dots */}
                  <span className="flex justify-center gap-0.5 sm:hidden" aria-hidden>
                    {Array.from(new Set(dayItems.map((i) => i.kind)))
                      .slice(0, 4)
                      .map((k) => (
                        <span key={k} className={cn('h-1.5 w-1.5 rounded-full', tones[kindTone[k]].dot)} />
                      ))}
                  </span>
                  {/* Desktop: chips */}
                  <span className="hidden min-w-0 flex-col gap-0.5 sm:flex" aria-hidden>
                    {dayItems.slice(0, 2).map((i) => (
                      <span key={i.id} className={cn('truncate rounded-md px-1.5 py-0.5 text-[11px] font-medium', tones[kindTone[i.kind]].soft)}>
                        {i.title}
                      </span>
                    ))}
                    {dayItems.length > 2 && <span className="px-1.5 text-[11px] text-one-subtle">{t('schedule.more', { count: dayItems.length - 2 })}</span>}
                  </span>
                </button>
              </div>
            )
          })}
        </div>
      ))}
    </div>
  )
}

export function WeekView({ days, items }: { days: Date[]; items: AgendaItem[] }) {
  const { t, fmt } = useI18n()
  const today = new Date()
  return (
    <div className="grid gap-3 md:grid-cols-7 md:gap-2">
      {days.map((d) => {
        const dayItems = items.filter((i) => sameDay(i.start, d))
        const isToday = sameDay(d, today)
        return (
          <section key={d.toISOString()} aria-label={fmt.date(d, { weekday: 'long', day: 'numeric', month: 'long' })} className="min-w-0">
            <div className={cn('mb-2 flex items-center gap-2 rounded-xl px-2 py-1.5 md:flex-col md:gap-0.5', isToday && 'bg-one-royal/[0.08]')}>
              <span className="text-[12px] font-semibold uppercase tracking-wide text-one-subtle">{fmt.weekday(d)}</span>
              <span className={cn('font-display text-[18px] font-bold tabular', isToday ? 'text-one-royal' : 'text-one-fg')}>{d.getDate()}</span>
            </div>
            <div className="space-y-2">
              {dayItems.length === 0 ? (
                <p className="rounded-xl border border-dashed border-one-line px-3 py-3 text-center text-[12px] text-one-subtle">{t('schedule.noEvents')}</p>
              ) : (
                dayItems.map((i) => <AgendaRow key={i.id} item={i} compact />)
              )}
            </div>
          </section>
        )
      })}
    </div>
  )
}

export function DayView({ items }: { items: AgendaItem[] }) {
  const { fmt } = useI18n()
  return (
    <ol className="relative space-y-3 before:absolute before:bottom-2 before:left-[3.25rem] before:top-2 before:w-px before:bg-one-line">
      {items.map((i) => (
        <li key={i.id} className="relative flex items-start gap-4">
          <span className="w-10 shrink-0 pt-3 text-right text-[12.5px] font-medium text-one-subtle tabular">{i.allDay ? '—' : fmt.time(i.start)}</span>
          <span aria-hidden className={cn('relative z-10 mt-4 h-2.5 w-2.5 shrink-0 rounded-full ring-4 ring-one-card', tones[kindTone[i.kind]].dot)} />
          <div className="min-w-0 flex-1">
            <AgendaRow item={i} />
          </div>
        </li>
      ))}
    </ol>
  )
}
