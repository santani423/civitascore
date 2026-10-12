export type Locale = 'id' | 'en' | 'zh-CN'

export interface LocaleInfo {
  code: Locale
  /** Compact label for the navbar trigger. */
  short: string
  /** Native name, shown in the language menu. */
  label: string
  /** BCP 47 tag for Intl formatting. */
  intl: string
}

export const LOCALES: LocaleInfo[] = [
  { code: 'id', short: 'ID', label: 'Bahasa Indonesia', intl: 'id-ID' },
  { code: 'en', short: 'EN', label: 'English', intl: 'en-US' },
  { code: 'zh-CN', short: '中文', label: '简体中文', intl: 'zh-CN' },
]

export const DEFAULT_LOCALE: Locale = 'id'

export function isLocale(value: unknown): value is Locale {
  return LOCALES.some((l) => l.code === value)
}

/**
 * First-visit guess from the browser. Indonesian is the default for this
 * institution's audience; Chinese and English are picked only when the
 * browser clearly prefers them over Indonesian.
 */
export function detectLocale(): Locale {
  if (typeof navigator === 'undefined') return DEFAULT_LOCALE
  const prefs = (navigator.languages?.length ? navigator.languages : [navigator.language]).map((l) => l.toLowerCase())
  const first = prefs[0] ?? ''
  if (first.startsWith('zh')) return 'zh-CN'
  if (prefs.some((l) => l.startsWith('id') || l.startsWith('ms'))) return 'id'
  if (first.startsWith('en')) return 'en'
  return DEFAULT_LOCALE
}
