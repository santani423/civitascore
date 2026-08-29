export function formatNumber(value: number): string {
  return new Intl.NumberFormat('id-ID').format(value)
}

export function formatCurrencyIDR(value: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

export function formatRelativeTime(isoDate: string): string {
  const diffMs = Date.now() - new Date(isoDate).getTime()
  const diffMinutes = Math.round(diffMs / 60_000)

  if (diffMinutes < 1) return 'Baru saja'
  if (diffMinutes < 60) return `${diffMinutes} menit lalu`

  const diffHours = Math.round(diffMinutes / 60)
  if (diffHours < 24) return `${diffHours} jam lalu`

  const diffDays = Math.round(diffHours / 24)
  if (diffDays < 7) return `${diffDays} hari lalu`

  return new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(
    new Date(isoDate),
  )
}

export function formatDateRange(startIso: string, endIso: string): string {
  const formatter = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short' })
  return `${formatter.format(new Date(startIso))} - ${formatter.format(new Date(endIso))}`
}

/** ISO datetime (UTC, dari backend) -> value yang diterima <input type="datetime-local"> (local time, tanpa detik/offset). */
export function toDatetimeLocalValue(iso: string | null | undefined): string {
  if (!iso) return ''

  const date = new Date(iso)
  const pad = (value: number) => value.toString().padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

/** Kebalikan toDatetimeLocalValue() — value <input type="datetime-local"> (local time) -> ISO string untuk dikirim ke API. */
export function fromDatetimeLocalValue(value: string | null | undefined): string | null {
  if (!value) return null

  return new Date(value).toISOString()
}

/** "super_admin" / "grade_change" -> "Super Admin" / "Grade Change" — dipakai untuk menampilkan slug/enum backend sebagai label. */
export function humanizeSlug(slug: string): string {
  return slug
    .split(/[_.-]/)
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

export function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`

  const units = ['KB', 'MB', 'GB', 'TB']
  let value = bytes / 1024
  let unitIndex = 0

  while (value >= 1024 && unitIndex < units.length - 1) {
    value /= 1024
    unitIndex += 1
  }

  return `${value.toFixed(value < 10 ? 1 : 0)} ${units[unitIndex]}`
}
