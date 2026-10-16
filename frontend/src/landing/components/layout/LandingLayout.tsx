import { Suspense, lazy, useEffect, useRef, type ReactNode } from 'react'
import { useLandingI18n } from '../../i18n'
import { useThemeControl } from '../../hooks/useThemeControl'
import { useCjkFont, useDocumentLang } from '../../hooks/useDocumentSetup'
import { prefersReducedMotion } from '../../hooks/useScroll'
import { useLandingUi } from '../../store/ui'
import { Toaster } from '../ui/Toaster'
import { Navbar } from './Navbar'
import { Footer } from './Footer'
import '../../styles/landing.css'

// The form (zod + react-hook-form) is only fetched once someone opens it.
const RequestDialog = lazy(() => import('../RequestDialog'))

export function LandingLayout({ children }: { children: ReactNode }) {
  const { m, locale } = useLandingI18n()
  const { isDark } = useThemeControl()
  const requestLoaded = useLandingUi((s) => s.requestLoaded)
  const contentRef = useRef<HTMLDivElement>(null)
  const previousLocale = useRef(locale)
  useDocumentLang(locale, isDark)
  useCjkFont(locale)

  // A short cross-fade so a language switch reads as a deliberate change, not a flicker.
  useEffect(() => {
    if (previousLocale.current === locale) return
    previousLocale.current = locale
    if (prefersReducedMotion()) return
    contentRef.current?.animate([{ opacity: 0.25 }, { opacity: 1 }], { duration: 360, easing: 'cubic-bezier(0.2, 0.7, 0.2, 1)' })
  }, [locale])

  return (
    <div className="lp-root lp-page" lang={locale}>
      <a
        href="#main"
        className="sr-only z-[100] rounded-full bg-lp-brand px-5 py-2.5 text-[14px] font-semibold text-lp-onbrand focus:not-sr-only focus:fixed focus:left-4 focus:top-3"
      >
        {m.common.skipToContent}
      </a>
      <Navbar />
      <div ref={contentRef}>
        <main id="main" tabIndex={-1} className="focus:outline-none">
          {children}
        </main>
        <Footer />
      </div>
      {requestLoaded && (
        <Suspense fallback={null}>
          <RequestDialog />
        </Suspense>
      )}
      <Toaster />
    </div>
  )
}
