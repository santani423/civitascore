import { fill } from '../i18n'
import type { Messages } from '../i18n/messages/id'

export interface AcademicTerm {
  odd: boolean
  startYear: number
}

/** Indonesian academic calendar: odd term Aug–Jan, even term Feb–Jul. */
export function currentTerm(date = new Date()): AcademicTerm {
  const month = date.getMonth()
  const year = date.getFullYear()
  if (month >= 7) return { odd: true, startYear: year }
  if (month === 0) return { odd: true, startYear: year - 1 }
  return { odd: false, startYear: year - 1 }
}

export function termLabel(m: Messages, term = currentTerm()): string {
  return fill(term.odd ? m.common.termOdd : m.common.termEven, { years: `${term.startYear}/${term.startYear + 1}` })
}

/** Compact, language-neutral labels for the last `count` terms, oldest first (e.g. "25/26 I"). */
export function recentTermLabels(count: number, date = new Date()): string[] {
  const labels: string[] = []
  let term = currentTerm(date)
  for (let i = 0; i < count; i++) {
    const yy = String(term.startYear % 100).padStart(2, '0')
    const next = String((term.startYear + 1) % 100).padStart(2, '0')
    labels.unshift(`${yy}/${next} ${term.odd ? 'I' : 'II'}`)
    term = term.odd ? { odd: false, startYear: term.startYear - 1 } : { odd: true, startYear: term.startYear }
  }
  return labels
}

/**
 * The next `count` calendar events on or after `today`, treating the
 * day-of-month pattern in CALENDAR_EVENTS as recurring — so the hero agenda
 * and the interactive calendar always tell the same story.
 */
export function upcomingEvents<K extends string>(events: Array<{ key: K; from: number; to: number }>, count: number, today = new Date()) {
  const start = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const out: Array<{ key: K; from: Date; to: Date }> = []
  for (let offset = 0; offset < 3 && out.length < count; offset++) {
    const year = today.getFullYear()
    const month = today.getMonth() + offset
    const lastDay = new Date(year, month + 1, 0).getDate()
    for (const ev of events) {
      const to = new Date(year, month, Math.min(ev.to, lastDay))
      if (to < start) continue
      out.push({ key: ev.key, from: new Date(year, month, Math.min(ev.from, lastDay)), to })
      if (out.length === count) break
    }
  }
  return out
}

/** A date `days` from today (negative for the past). */
export function daysFromToday(days: number, base = new Date()): Date {
  const d = new Date(base)
  d.setDate(d.getDate() + days)
  return d
}
