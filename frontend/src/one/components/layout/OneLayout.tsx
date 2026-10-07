import { useEffect, useRef } from 'react'
import { Link, Outlet, useLocation } from 'react-router-dom'
import { useI18n } from '../../i18n'
import { usePrefs } from '../../store/prefs'
import { useMediaQuery, useResolvedTheme, useShell } from '../../hooks/useShell'
import { Toaster } from '../ui/Feedback'
import { Sidebar } from './Sidebar'
import { Topbar } from './Topbar'
import { MobileNav } from './MobileNav'
import { CommandPalette } from './CommandPalette'
import '../../styles/one.css'

const fontSizes = { small: '15px', default: '16px', large: '17.5px' }

export function OneLayout() {
  const { t, lang } = useI18n()
  const theme = useResolvedTheme()
  const fontScale = usePrefs((s) => s.fontScale)
  const reducedMotion = usePrefs((s) => s.reducedMotion)
  const highContrast = usePrefs((s) => s.highContrast)
  const collapsed = usePrefs((s) => s.sidebarCollapsed)
  const dataState = usePrefs((s) => s.dataState)
  const isDesktop = useMediaQuery('(min-width: 1024px)')
  const setDrawer = useShell((s) => s.setDrawer)
  const { pathname } = useLocation()
  const mainRef = useRef<HTMLElement>(null)
  const firstRender = useRef(true)

  // Document-level settings that can't live on the scoped root element.
  useEffect(() => {
    const html = document.documentElement
    const prev = { fontSize: html.style.fontSize, lang: html.lang, scheme: html.style.colorScheme }
    html.style.fontSize = fontSizes[fontScale]
    html.lang = lang === 'zh' ? 'zh-CN' : lang
    html.style.colorScheme = theme
    return () => {
      html.style.fontSize = prev.fontSize
      html.lang = prev.lang
      html.style.colorScheme = prev.scheme
    }
  }, [fontScale, lang, theme])

  // On navigation: close the mobile drawer, scroll up, and move focus to main for screen readers.
  useEffect(() => {
    setDrawer(false)
    if (firstRender.current) {
      firstRender.current = false
      return
    }
    window.scrollTo({ top: 0 })
    mainRef.current?.focus({ preventScroll: true })
  }, [pathname, setDrawer])

  return (
    <div
      className="one-root flex min-h-screen"
      data-theme={theme}
      data-contrast={highContrast ? 'high' : undefined}
      data-motion={reducedMotion ? 'reduce' : undefined}
      lang={lang}
    >
      <a
        href="#one-main"
        className="sr-only z-[90] rounded-xl bg-one-brand px-4 py-2.5 text-[14px] font-medium text-one-onbrand focus:not-sr-only focus:fixed focus:left-4 focus:top-4"
      >
        {t('nav.skipToContent')}
      </a>

      <Sidebar expanded={isDesktop && !collapsed} canToggle={isDesktop} />

      <div className="flex min-w-0 flex-1 flex-col">
        <Topbar />
        {dataState !== 'live' && (
          <div className="border-b border-one-warn/20 bg-one-warn/10 px-4 py-2 text-center text-[12.5px] font-medium text-one-warn">
            {t('settings.demo')}: {t(`settings.dataStates.${dataState}`)} ·{' '}
            <Link to="/one/settings" className="underline">
              {t('nav.settings')}
            </Link>
          </div>
        )}
        <main
          ref={mainRef}
          id="one-main"
          tabIndex={-1}
          className="mx-auto w-full max-w-[1440px] flex-1 px-4 pb-28 pt-6 focus:outline-none md:px-6 md:pb-12 lg:px-8 lg:pt-8"
        >
          <Outlet />
        </main>
        <footer className="hidden border-t border-one-line md:block">
          <div className="mx-auto flex max-w-[1440px] items-center justify-between gap-4 px-6 py-5 text-[12.5px] text-one-subtle lg:px-8">
            <span>
              {t('footer.rights', { year: new Date().getFullYear() })} · {t('app.tagline')}
            </span>
            <span className="flex gap-4">
              <Link to="/one/help" className="hover:text-one-fg">{t('footer.privacy')}</Link>
              <Link to="/one/help" className="hover:text-one-fg">{t('footer.terms')}</Link>
              <Link to="/one/settings" className="hover:text-one-fg">{t('footer.accessibility')}</Link>
            </span>
          </div>
        </footer>
      </div>

      <MobileNav />
      <CommandPalette />
      <Toaster />
    </div>
  )
}
