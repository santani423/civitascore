import { useEffect } from 'react'
import { landingConfig } from '../config'
import type { Locale } from '../i18n/locales'

function upsertMeta(attr: 'name' | 'property', key: string, content: string): () => void {
  let el = document.head.querySelector<HTMLMetaElement>(`meta[${attr}="${key}"]`)
  const created = !el
  if (!el) {
    el = document.createElement('meta')
    el.setAttribute(attr, key)
    document.head.appendChild(el)
  }
  const previous = el.getAttribute('content')
  el.setAttribute('content', content)
  return () => {
    if (created) el?.remove()
    else if (previous !== null) el?.setAttribute('content', previous)
  }
}

function upsertLink(rel: string, href: string): () => void {
  let el = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)
  const created = !el
  if (!el) {
    el = document.createElement('link')
    el.rel = rel
    document.head.appendChild(el)
  }
  const previous = el.getAttribute('href')
  el.href = href
  return () => {
    if (created) el?.remove()
    else if (previous !== null) el?.setAttribute('href', previous)
  }
}

/**
 * Per-language <title>, description and Open Graph tags, plus canonical/og:url
 * when a production origin is configured. Restores the previous values when
 * the landing page unmounts so the application keeps its own title.
 */
export function useDocumentMeta({ title, description, ogLocale, path }: { title: string; description: string; ogLocale: string; path: string }) {
  useEffect(() => {
    const previousTitle = document.title
    document.title = title
    const restore = [
      upsertMeta('name', 'description', description),
      upsertMeta('property', 'og:title', title),
      upsertMeta('property', 'og:description', description),
      upsertMeta('property', 'og:locale', ogLocale),
      upsertMeta('name', 'twitter:title', title),
      upsertMeta('name', 'twitter:description', description),
    ]
    if (landingConfig.siteUrl) {
      const url = `${landingConfig.siteUrl}${path}`
      restore.push(upsertLink('canonical', url), upsertMeta('property', 'og:url', url))
    }
    return () => {
      document.title = previousTitle
      restore.forEach((fn) => fn())
    }
  }, [title, description, ogLocale, path])
}

/** <html lang> and color-scheme follow the landing page while it is mounted. */
export function useDocumentLang(locale: Locale, isDark: boolean) {
  useEffect(() => {
    const html = document.documentElement
    const previous = { lang: html.lang, scheme: html.style.colorScheme }
    html.lang = locale
    html.style.colorScheme = isDark ? 'dark' : 'light'
    return () => {
      html.lang = previous.lang
      html.style.colorScheme = previous.scheme
    }
  }, [locale, isDark])
}

const ZH_FONT_ID = 'lp-font-noto-sans-sc'
const ZH_FONT_HREF = 'https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@400;500;600;700&display=swap'

/**
 * Loads Noto Sans SC only when Mandarin is first selected. Google Fonts
 * serves it in unicode-range slices, so only the glyphs on screen download.
 */
export function useCjkFont(locale: Locale) {
  useEffect(() => {
    if (locale !== 'zh-CN' || document.getElementById(ZH_FONT_ID)) return
    const link = document.createElement('link')
    link.id = ZH_FONT_ID
    link.rel = 'stylesheet'
    link.href = ZH_FONT_HREF
    document.head.appendChild(link)
  }, [locale])
}
