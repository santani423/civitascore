import type { Locale } from '../i18n/locales'
import type { ResolvedActivity } from '../data/student-affairs'

function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
}

/**
 * "Sab, 31 Okt 2026" (or a range, "26 Okt – 8 Nov 2026") in the visitor's
 * language. `long` spells out weekday and month for the detail page.
 */
export function formatActivityDate(activity: ResolvedActivity, intl: string, style: 'short' | 'long' = 'short'): string {
  const long = style === 'long'
  if (sameDay(activity.startsAt, activity.endsAt)) {
    return new Intl.DateTimeFormat(intl, { weekday: long ? 'long' : 'short', day: 'numeric', month: long ? 'long' : 'short', year: 'numeric' }).format(activity.startsAt)
  }
  return new Intl.DateTimeFormat(intl, { day: 'numeric', month: long ? 'long' : 'short', year: 'numeric' }).formatRange(activity.startsAt, activity.endsAt)
}

/** Times are stored as "HH:MM" (WIB); Indonesian writes them "09.00". */
export function formatClock(time: string, locale: Locale): string {
  return locale === 'id' ? time.replace(':', '.') : time
}

/** Month filter label, e.g. "November 2026". */
export function formatMonthKey(key: string, intl: string): string {
  const [year, month] = key.split('-').map(Number)
  return new Intl.DateTimeFormat(intl, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1))
}
