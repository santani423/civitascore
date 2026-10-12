import { useCallback, useMemo } from 'react'
import { useLandingPrefs } from '../store/prefs'
import { useLandingUi } from '../store/ui'
import { LOCALES, type Locale } from './locales'
import { id, type Messages } from './messages/id'
import { en } from './messages/en'
import { zhCN } from './messages/zh-CN'

export const dictionaries: Record<Locale, Messages> = { id, en, 'zh-CN': zhCN }

export type FillVars = Record<string, string | number>

/** Replaces `{name}` placeholders; unknown names are left visible so they're easy to spot. */
export function fill(template: string, vars: FillVars): string {
  return template.replace(/\{(\w+)\}/g, (match, name: string) => (name in vars ? String(vars[name]) : match))
}

export function useLandingI18n() {
  const locale = useLandingPrefs((s) => s.locale)
  const m = dictionaries[locale]
  const intl = LOCALES.find((l) => l.code === locale)?.intl ?? 'id-ID'

  const fmt = useMemo(
    () => ({
      number: (n: number, digits = 0) =>
        new Intl.NumberFormat(intl, { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(n),
      compact: (n: number) => new Intl.NumberFormat(intl, { notation: 'compact', maximumFractionDigits: 1 }).format(n),
      percent: (n: number, digits = 1) =>
        new Intl.NumberFormat(intl, { style: 'percent', minimumFractionDigits: digits, maximumFractionDigits: digits }).format(n / 100),
      currency: (n: number) =>
        new Intl.NumberFormat(intl, { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n),
      date: (d: Date, opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }) =>
        new Intl.DateTimeFormat(intl, opts).format(d),
      time: (d: Date) => new Intl.DateTimeFormat(intl, { hour: '2-digit', minute: '2-digit', hour12: false }).format(d),
      weekday: (d: Date, style: 'narrow' | 'short' | 'long' = 'short') => new Intl.DateTimeFormat(intl, { weekday: style }).format(d),
      month: (d: Date) => new Intl.DateTimeFormat(intl, { month: 'long', year: 'numeric' }).format(d),
    }),
    [intl],
  )

  return { m, locale, intl, fmt }
}

/** Switches language and announces it (in the new language) to screen readers. */
export function useChangeLocale() {
  const setLocale = useLandingPrefs((s) => s.setLocale)
  const announce = useLandingUi((s) => s.announce)
  return useCallback(
    (next: Locale) => {
      if (next === useLandingPrefs.getState().locale) return
      setLocale(next)
      announce(dictionaries[next].toast.languageChanged)
    },
    [setLocale, announce],
  )
}

export { LOCALES, type Locale }
