import { assignments, courseById, courses, exams } from '../data/academic'
import { events } from '../data/campus'
import { invoices } from '../data/services'
import { startOfDay } from './dates'

export type AgendaKind = 'lecture' | 'exam' | 'assignment' | 'event' | 'payment'

export interface AgendaItem {
  id: string
  kind: AgendaKind
  title: string
  subtitle?: string
  start: Date
  end?: Date
  location?: string
  to: string
  allDay?: boolean
}

function at(day: Date, hhmm: string): Date {
  const [h, m] = hhmm.split(':').map(Number)
  const d = new Date(day)
  d.setHours(h, m, 0, 0)
  return d
}

/** Everything on the student's calendar between `from` and `to` (inclusive days). */
export function buildAgenda(from: Date, to: Date): AgendaItem[] {
  const items: AgendaItem[] = []
  const start = startOfDay(from)
  const end = startOfDay(to)
  end.setHours(23, 59, 59, 999)

  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    const weekday = d.getDay() === 0 ? 7 : d.getDay()
    for (const c of courses) {
      for (const s of c.slots) {
        if (s.day !== weekday) continue
        items.push({
          id: `lec-${c.id}-${d.toDateString()}-${s.start}`,
          kind: 'lecture',
          title: c.name,
          subtitle: c.code,
          start: at(d, s.start),
          end: at(d, s.end),
          location: s.room,
          to: `/one/courses/${c.id}`,
        })
      }
    }
  }

  const inRange = (d: Date) => d >= start && d <= end

  for (const e of exams) {
    const s = new Date(e.start)
    if (!inRange(s)) continue
    const c = courseById(e.courseId)
    items.push({
      id: e.id,
      kind: 'exam',
      title: c?.name ?? e.courseId,
      subtitle: e.type,
      start: s,
      end: new Date(s.getTime() + e.duration * 60_000),
      location: e.room,
      to: '/one/exams',
    })
  }
  for (const a of assignments) {
    const s = new Date(a.due)
    if (!inRange(s) || a.status === 'graded') continue
    items.push({ id: a.id, kind: 'assignment', title: a.title, subtitle: courseById(a.courseId)?.code, start: s, to: '/one/assignments' })
  }
  for (const e of events) {
    const s = new Date(e.date)
    if (!inRange(s)) continue
    items.push({ id: e.id, kind: 'event', title: e.title, start: s, end: new Date(e.end), location: e.location, to: `/one/campus/events?e=${e.id}` })
  }
  for (const inv of invoices) {
    const s = new Date(inv.due)
    if (inv.status === 'paid' || !inRange(s)) continue
    items.push({ id: inv.id, kind: 'payment', title: inv.description, start: s, allDay: true, to: '/one/finance' })
  }

  return items.sort((a, b) => a.start.getTime() - b.start.getTime())
}
