import { useCallback, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { CalendarEvent } from '@/types/studentPortal'
import { toLocalDateParam } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']

const TYPE_STYLE: Record<CalendarEvent['type'], { dot: string; chip: string; label: string }> = {
  class: { dot: 'bg-primary', chip: 'bg-primary-50 text-primary-800 dark:bg-primary-950 dark:text-primary-200', label: 'Kuliah' },
  exam: { dot: 'bg-red-500', chip: 'bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200', label: 'Ujian' },
  assignment: { dot: 'bg-amber-500', chip: 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-200', label: 'Batas tugas' },
  academic: { dot: 'bg-sky-500', chip: 'bg-sky-50 text-sky-800 dark:bg-sky-950 dark:text-sky-200', label: 'Kalender akademik' },
}

/** Tanggal (YYYY-MM-DD lokal) yang dicakup sebuah agenda — agenda sehari penuh bisa beberapa hari. */
function eventDays(event: CalendarEvent): string[] {
  if (!event.all_day) return [toLocalDateParam(new Date(event.start))]

  const days: string[] = []
  const [sy, sm, sd] = event.start.slice(0, 10).split('-').map(Number)
  const [ey, em, ed] = event.end.slice(0, 10).split('-').map(Number)
  const cursor = new Date(sy, sm - 1, sd)
  const end = new Date(ey, em - 1, ed)

  while (cursor <= end && days.length < 62) {
    days.push(toLocalDateParam(cursor))
    cursor.setDate(cursor.getDate() + 1)
  }

  return days
}

function eventTime(event: CalendarEvent): string {
  if (event.all_day) return 'Sepanjang hari'

  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(event.start))
}

export function PortalCalendarPage() {
  const [month, setMonth] = useState(() => {
    const now = new Date()
    return new Date(now.getFullYear(), now.getMonth(), 1)
  })

  // Grid Senin–Minggu yang mencakup seluruh bulan (maks. 6 minggu = 42 hari).
  const gridDays = useMemo(() => {
    const first = new Date(month)
    const offset = (first.getDay() + 6) % 7
    const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset)

    return Array.from({ length: 42 }, (_, index) => new Date(start.getFullYear(), start.getMonth(), start.getDate() + index))
  }, [month])

  const from = toLocalDateParam(gridDays[0])
  const to = toLocalDateParam(gridDays[gridDays.length - 1])
  const fetchEvents = useCallback(() => studentPortalService.calendar(from, to), [from, to])
  const { data: events, isLoading, error, refetch } = useFetch(fetchEvents)

  const byDay = useMemo(() => {
    const map = new Map<string, CalendarEvent[]>()
    for (const event of events ?? []) {
      for (const day of eventDays(event)) map.set(day, [...(map.get(day) ?? []), event])
    }
    return map
  }, [events])

  const today = toLocalDateParam(new Date())
  const monthLabel = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(month)
  const monthDays = gridDays.filter((day) => day.getMonth() === month.getMonth())

  const shiftMonth = (delta: number) => setMonth((current) => new Date(current.getFullYear(), current.getMonth() + delta, 1))

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kalender Akademik"
        description="Jadwal kuliah, ujian, batas tugas, periode KRS, dan agenda akademik dalam satu tampilan."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Kalender' }]}
      />

      <div className="flex items-center justify-between gap-2">
        <div className="flex items-center gap-1">
          <Button size="sm" variant="outline" aria-label="Bulan sebelumnya" onClick={() => shiftMonth(-1)}>
            <ChevronLeft className="size-4" />
          </Button>
          <Button size="sm" variant="outline" aria-label="Bulan berikutnya" onClick={() => shiftMonth(1)}>
            <ChevronRight className="size-4" />
          </Button>
          <Button size="sm" variant="ghost" onClick={() => setMonth(new Date(new Date().getFullYear(), new Date().getMonth(), 1))}>
            Hari ini
          </Button>
        </div>
        <h2 className="text-base font-semibold capitalize text-ink-primary">{monthLabel}</h2>
      </div>

      <div className="flex flex-wrap gap-3 text-xs text-ink-secondary">
        {Object.values(TYPE_STYLE).map((style) => (
          <span key={style.label} className="inline-flex items-center gap-1.5">
            <span className={cn('size-2 rounded-full', style.dot)} /> {style.label}
          </span>
        ))}
      </div>

      {isLoading && !events ? (
        <PortalLoading cards={0} rows={8} />
      ) : error ? (
        <PortalError message={error} onRetry={refetch} />
      ) : (
        <>
          {/* Desktop: grid bulanan */}
          <Card noPadding className="hidden md:block">
            <div className="grid grid-cols-7 border-b border-border text-center text-xs font-medium text-ink-tertiary">
              {WEEKDAYS.map((day) => (
                <div key={day} className="py-2">
                  {day}
                </div>
              ))}
            </div>
            <div className="grid grid-cols-7">
              {gridDays.map((day) => {
                const key = toLocalDateParam(day)
                const dayEvents = byDay.get(key) ?? []
                const inMonth = day.getMonth() === month.getMonth()

                return (
                  <div key={key} className={cn('min-h-28 border-b border-r border-border p-1.5 last:border-r-0', !inMonth && 'bg-surface-hover/50')}>
                    <p
                      className={cn(
                        'mb-1 inline-flex size-6 items-center justify-center rounded-full text-xs',
                        key === today ? 'bg-primary font-semibold text-white' : inMonth ? 'text-ink-primary' : 'text-ink-tertiary',
                      )}
                    >
                      {day.getDate()}
                    </p>
                    <div className="flex flex-col gap-0.5">
                      {dayEvents.slice(0, 3).map((event) => (
                        <span key={event.id} title={event.title} className={cn('truncate rounded px-1 py-0.5 text-[11px]', TYPE_STYLE[event.type].chip)}>
                          {!event.all_day && `${eventTime(event)} `}
                          {event.title}
                        </span>
                      ))}
                      {dayEvents.length > 3 && <span className="px-1 text-[11px] text-ink-tertiary">+{dayEvents.length - 3} lainnya</span>}
                    </div>
                  </div>
                )
              })}
            </div>
          </Card>

          {/* Mobile: daftar agenda per hari */}
          <div className="flex flex-col gap-3 md:hidden">
            {monthDays.filter((day) => byDay.has(toLocalDateParam(day))).length === 0 ? (
              <PortalEmpty icon={CalendarDays} title="Tidak ada agenda bulan ini." />
            ) : (
              monthDays
                .filter((day) => byDay.has(toLocalDateParam(day)))
                .map((day) => {
                  const key = toLocalDateParam(day)

                  return (
                    <Card key={key} title={new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long' }).format(day)}>
                      <ul className="flex flex-col gap-2">
                        {(byDay.get(key) ?? []).map((event) => {
                          const content = (
                            <span className="flex items-start gap-2">
                              <span className={cn('mt-1.5 size-2 shrink-0 rounded-full', TYPE_STYLE[event.type].dot)} />
                              <span className="min-w-0">
                                <span className="block text-sm font-medium text-ink-primary">{event.title}</span>
                                <span className="block text-xs text-ink-secondary">
                                  {eventTime(event)}
                                  {event.location ? ` · ${event.location}` : ''}
                                  {event.description ? ` · ${event.description}` : ''}
                                </span>
                              </span>
                            </span>
                          )

                          return (
                            <li key={event.id}>
                              {event.link ? <Link to={event.link}>{content}</Link> : content}
                            </li>
                          )
                        })}
                      </ul>
                    </Card>
                  )
                })
            )}
          </div>
        </>
      )}
    </div>
  )
}
