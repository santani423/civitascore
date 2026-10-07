import { CalendarCheck, ClipboardList, FileCheck, GraduationCap, Megaphone, ShieldCheck, Wallet, type LucideIcon } from 'lucide-react'
import type { NotifCategory } from '../../data/services'
import type { UiTone } from '../../lib/tones'
import { LANGS } from '../../i18n'
import type { Lang } from '../../store/prefs'
import { IconTile } from '../ui/Display'

export const notifMeta: Record<NotifCategory, { icon: LucideIcon; tone: UiTone }> = {
  academic: { icon: GraduationCap, tone: 'royal' },
  finance: { icon: Wallet, tone: 'warn' },
  assignment: { icon: ClipboardList, tone: 'gold' },
  exam: { icon: FileCheck, tone: 'bad' },
  event: { icon: CalendarCheck, tone: 'teal' },
  announcement: { icon: Megaphone, tone: 'sky' },
  system: { icon: ShieldCheck, tone: 'neutral' },
}

export function NotifIcon({ category, size = 'sm' }: { category: NotifCategory; size?: 'sm' | 'md' }) {
  const m = notifMeta[category]
  return <IconTile icon={m.icon} tone={m.tone} size={size} />
}

export function relativeTime(iso: string, lang: Lang): string {
  const intl = LANGS.find((l) => l.code === lang)?.intl ?? 'en-US'
  const rtf = new Intl.RelativeTimeFormat(intl, { numeric: 'auto' })
  const diffMin = Math.round((new Date(iso).getTime() - Date.now()) / 60_000)
  const abs = Math.abs(diffMin)
  if (abs < 60) return rtf.format(diffMin, 'minute')
  if (abs < 60 * 24) return rtf.format(Math.round(diffMin / 60), 'hour')
  return rtf.format(Math.round(diffMin / (60 * 24)), 'day')
}
