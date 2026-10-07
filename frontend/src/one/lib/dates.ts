/**
 * Mock dates are generated relative to "now" so the demo always looks
 * current: deadlines are a few days out, news is from last week, etc.
 */
const DAY = 86_400_000

export function startOfDay(d: Date): Date {
  const x = new Date(d)
  x.setHours(0, 0, 0, 0)
  return x
}

export function daysFromNow(days: number, hour = 9, minute = 0): string {
  const d = startOfDay(new Date())
  d.setDate(d.getDate() + days)
  d.setHours(hour, minute, 0, 0)
  return d.toISOString()
}

export function daysUntil(iso: string): number {
  return Math.round((startOfDay(new Date(iso)).getTime() - startOfDay(new Date()).getTime()) / DAY)
}

export function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
}

/** Monday-first week containing `d`. */
export function weekDays(d: Date): Date[] {
  const start = startOfDay(d)
  const offset = (start.getDay() + 6) % 7
  start.setDate(start.getDate() - offset)
  return Array.from({ length: 7 }, (_, i) => new Date(start.getFullYear(), start.getMonth(), start.getDate() + i))
}

/** 6×7 Monday-first grid covering the month of `d`. */
export function monthGrid(d: Date): Date[] {
  const first = new Date(d.getFullYear(), d.getMonth(), 1)
  const offset = (first.getDay() + 6) % 7
  return Array.from({ length: 42 }, (_, i) => new Date(first.getFullYear(), first.getMonth(), 1 - offset + i))
}

export function timeOfDay(): 'morning' | 'afternoon' | 'evening' {
  const h = new Date().getHours()
  if (h < 11) return 'morning'
  if (h < 17) return 'afternoon'
  return 'evening'
}
