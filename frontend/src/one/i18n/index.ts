import { useCallback, useMemo } from 'react'
import { usePrefs, type Lang } from '../store/prefs'
import { en, type Dictionary } from './translations/en'
import { id } from './translations/id'
import { zh } from './translations/zh'

export const dictionaries: Record<Lang, Dictionary> = { en, id, zh }

export const LANGS: Array<{ code: Lang; short: string; label: string; intl: string }> = [
  { code: 'id', short: 'ID', label: 'Bahasa Indonesia', intl: 'id-ID' },
  { code: 'en', short: 'EN', label: 'English', intl: 'en-US' },
  { code: 'zh', short: '中文', label: '简体中文', intl: 'zh-CN' },
]

/** Every dotted path to a string leaf, e.g. `'nav.dashboard'`. */
type Paths<T, P extends string = ''> = {
  [K in keyof T & string]: T[K] extends string ? `${P}${K}` : Paths<T[K], `${P}${K}.`>
}[keyof T & string]

export type TKey = Paths<Dictionary>
export type TVars = Record<string, string | number>
export type TFn = (key: TKey, vars?: TVars) => string

function lookup(dict: Dictionary, key: string): string {
  let node: unknown = dict
  for (const part of key.split('.')) {
    if (node && typeof node === 'object' && part in node) node = (node as Record<string, unknown>)[part]
    else return key
  }
  return typeof node === 'string' ? node : key
}

export function translate(lang: Lang, key: TKey, vars?: TVars): string {
  const raw = lookup(dictionaries[lang], key)
  if (!vars) return raw
  return raw.replace(/\{(\w+)\}/g, (_, name: string) => (name in vars ? String(vars[name]) : `{${name}}`))
}

export function useI18n() {
  const lang = usePrefs((s) => s.lang)
  const t = useCallback<TFn>((key, vars) => translate(lang, key, vars), [lang])
  const intl = LANGS.find((l) => l.code === lang)?.intl ?? 'en-US'

  const fmt = useMemo(
    () => ({
      date: (d: Date | string, opts: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }) =>
        new Intl.DateTimeFormat(intl, opts).format(typeof d === 'string' ? new Date(d) : d),
      time: (d: Date | string) =>
        new Intl.DateTimeFormat(intl, { hour: '2-digit', minute: '2-digit', hour12: false }).format(
          typeof d === 'string' ? new Date(d) : d,
        ),
      currency: (n: number) =>
        new Intl.NumberFormat(intl, { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n),
      compactCurrency: (n: number) =>
        new Intl.NumberFormat(intl, { style: 'currency', currency: 'IDR', notation: 'compact', maximumFractionDigits: 1 }).format(n),
      number: (n: number, digits = 0) =>
        new Intl.NumberFormat(intl, { minimumFractionDigits: digits, maximumFractionDigits: digits }).format(n),
      compact: (n: number) => new Intl.NumberFormat(intl, { notation: 'compact', maximumFractionDigits: 1 }).format(n),
      weekday: (d: Date, style: 'short' | 'long' = 'short') => new Intl.DateTimeFormat(intl, { weekday: style }).format(d),
      month: (d: Date, style: 'short' | 'long' = 'long') =>
        new Intl.DateTimeFormat(intl, { month: style, year: 'numeric' }).format(d),
    }),
    [intl],
  )

  return { t, lang, intl, fmt }
}
