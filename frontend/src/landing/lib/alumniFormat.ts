function sameDay(a: Date, b: Date): boolean {
  return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
}

/** "Sab, 31 Okt 2026" (or a range) in the visitor's language; `long` spells out weekday and month. */
export function formatEventDate(event: { startsAt: Date; endsAt: Date }, intl: string, style: 'short' | 'long' = 'short'): string {
  const long = style === 'long'
  if (sameDay(event.startsAt, event.endsAt)) {
    return new Intl.DateTimeFormat(intl, { weekday: long ? 'long' : 'short', day: 'numeric', month: long ? 'long' : 'short', year: 'numeric' }).format(event.startsAt)
  }
  return new Intl.DateTimeFormat(intl, { day: 'numeric', month: long ? 'long' : 'short', year: 'numeric' }).formatRange(event.startsAt, event.endsAt)
}

/** A rupiah amount held in billions, written the way each language shortens it ("Rp 35,2 M"). */
export function formatRupiahBillions(billions: number, intl: string): string {
  return new Intl.NumberFormat(intl, { style: 'currency', currency: 'IDR', notation: 'compact', maximumFractionDigits: 1 }).format(billions * 1_000_000_000)
}

/** Round value for a share of a total, e.g. 65.1. */
export function shareOf(part: number, whole: number): number {
  return (part / whole) * 100
}

export const isEmail = (value: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
