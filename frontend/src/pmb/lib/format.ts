/** Format tampilan PMB (zona waktu peramban; backend mengirim ISO-8601 UTC). */
export function money(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') return '—'

  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value))
}

export function dateTime(iso: string | null | undefined): string {
  if (!iso) return '—'

  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(iso))
}

export function date(iso: string | null | undefined): string {
  if (!iso) return '—'

  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(iso.length === 10 ? `${iso}T00:00:00` : iso))
}

/** Nilai untuk <input type="datetime-local"> dari ISO. */
export function toLocalInput(iso: string | null | undefined): string {
  if (!iso) return ''
  const d = new Date(iso)
  const pad = (n: number) => String(n).padStart(2, '0')

  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

/** ISO (dengan offset lokal) dari nilai datetime-local. */
export function fromLocalInput(value: string): string | null {
  if (!value) return null

  return new Date(value).toISOString()
}

export function countdown(targetIso: string, now: number): string {
  const diff = Math.max(0, new Date(targetIso).getTime() - now)
  const days = Math.floor(diff / 86_400_000)
  const hours = Math.floor((diff % 86_400_000) / 3_600_000)
  const minutes = Math.floor((diff % 3_600_000) / 60_000)
  const seconds = Math.floor((diff % 60_000) / 1000)

  return days > 0 ? `${days} hari ${hours} jam` : `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
}
