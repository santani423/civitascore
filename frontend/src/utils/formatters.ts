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
