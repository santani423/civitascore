/**
 * Format tanggal/angka untuk Portal Mahasiswa (id-ID). Tanggal "YYYY-MM-DD"
 * dari backend diperlakukan sebagai tanggal kalender (tanpa geser zona),
 * waktu ISO sebagai instan yang ditampilkan dalam zona waktu browser.
 */

function parseDateOnly(value: string): Date {
  const [year, month, day] = value.slice(0, 10).split('-').map(Number)
  return new Date(year, (month ?? 1) - 1, day ?? 1)
}

export function formatDate(value: string | null | undefined, style: 'long' | 'medium' | 'short' = 'medium'): string {
  if (!value) return '-'

  const date = value.length <= 10 ? parseDateOnly(value) : new Date(value)
  const options: Intl.DateTimeFormatOptions =
    style === 'long'
      ? { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
      : style === 'short'
        ? { day: 'numeric', month: 'short' }
        : { day: 'numeric', month: 'short', year: 'numeric' }

  return new Intl.DateTimeFormat('id-ID', options).format(date)
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return '-'

  return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value))
}

export function formatTime(value: string | null | undefined): string {
  if (!value) return '-'

  return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value))
}

/** IP/IPK selalu dua desimal dengan koma: 3,45. */
export function formatGpa(value: number | null | undefined): string {
  if (value === null || value === undefined) return '-'

  return value.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export function formatPercent(value: number | null | undefined): string {
  if (value === null || value === undefined) return '-'

  return `${value.toLocaleString('id-ID', { maximumFractionDigits: 1 })}%`
}

/** "2 hari lagi" / "3 jam lagi" / "lewat 5 jam" — untuk deadline & jadwal. */
export function formatCountdown(iso: string, now: number = Date.now()): string {
  const diffMinutes = Math.round((new Date(iso).getTime() - now) / 60_000)
  const abs = Math.abs(diffMinutes)
  const unit =
    abs < 60 ? `${abs} menit` : abs < 60 * 24 ? `${Math.round(abs / 60)} jam` : `${Math.round(abs / (60 * 24))} hari`

  return diffMinutes >= 0 ? `${unit} lagi` : `lewat ${unit}`
}

/** "2026-10-06" untuk tanggal lokal (bukan UTC) — dipakai parameter kalender. */
export function toLocalDateParam(date: Date): string {
  const pad = (value: number) => value.toString().padStart(2, '0')

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}
