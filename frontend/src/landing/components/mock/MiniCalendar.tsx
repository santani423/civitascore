import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import type { Tone } from '../../data/demo'

export interface CalendarMark {
  from: number
  to: number
  tone: Tone
  label: string
}

const markClass: Record<Tone, string> = {
  brand: 'bg-lp-brand text-lp-onbrand',
  gold: 'bg-lp-gold/20 text-lp-gold-text',
  info: 'bg-lp-info/15 text-lp-info',
}

interface MiniCalendarProps {
  month: Date
  marks: CalendarMark[]
  today?: number
  selected?: number | null
  onSelect?: (day: number) => void
  compact?: boolean
}

/** Month grid (Monday first). Interactive when `onSelect` is given. */
export function MiniCalendar({ month, marks, today, selected, onSelect, compact }: MiniCalendarProps) {
  const { fmt } = useLandingI18n()
  const year = month.getFullYear()
  const monthIndex = month.getMonth()
  const daysInMonth = new Date(year, monthIndex + 1, 0).getDate()
  const lead = (new Date(year, monthIndex, 1).getDay() + 6) % 7
  // 2024-01-01 was a Monday: a fixed week to derive localized weekday initials.
  const weekdays = Array.from({ length: 7 }, (_, i) => fmt.weekday(new Date(2024, 0, 1 + i), 'narrow'))
  const markFor = (day: number) => marks.find((mk) => day >= mk.from && day <= mk.to)

  const cells: Array<number | null> = [...Array.from({ length: lead }, () => null), ...Array.from({ length: daysInMonth }, (_, i) => i + 1)]

  return (
    <div>
      <div className="grid grid-cols-7 gap-1 text-center">
        {weekdays.map((w, i) => (
          <span key={i} aria-hidden className="text-[10px] font-semibold uppercase text-lp-subtle">
            {w}
          </span>
        ))}
      </div>
      <div className="mt-1.5 grid grid-cols-7 gap-1">
        {cells.map((day, i) => {
          if (day === null) return <span key={`e${i}`} />
          const mark = markFor(day)
          const isToday = day === today
          const isSelected = day === selected
          const className = cn(
            'relative flex items-center justify-center rounded-md text-[11px] lp-tabular transition-colors',
            compact ? 'h-6' : 'h-8 sm:h-9 sm:text-[12px]',
            mark ? markClass[mark.tone] : 'text-lp-muted',
            !mark && onSelect && 'hover:bg-lp-sunken hover:text-lp-fg',
            isToday && 'font-bold ring-1 ring-inset ring-lp-fg/40',
            isSelected && 'ring-2 ring-inset ring-lp-ring',
          )
          if (!onSelect) {
            return (
              <span key={day} className={className}>
                {day}
              </span>
            )
          }
          const label = fmt.date(new Date(year, monthIndex, day), { weekday: 'long', day: 'numeric', month: 'long' })
          return (
            <button
              key={day}
              type="button"
              className={className}
              aria-pressed={isSelected}
              aria-label={mark ? `${label} — ${mark.label}` : label}
              onClick={() => onSelect(day)}
            >
              {day}
            </button>
          )
        })}
      </div>
    </div>
  )
}
