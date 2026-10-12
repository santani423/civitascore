import { useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { LandingLayout } from './components/layout/LandingLayout'
import { HomePage } from './pages/HomePage'
import { LegalPage } from './pages/LegalPage'
import { LANDING_PATHS } from './paths'

/**
 * Civitas One Atma public landing page (UI only — mock data, no API calls).
 * Rendered by App.tsx for the landing paths, and by LandingRoot when the
 * visitor lands here directly (so the admin bundle isn't downloaded).
 */
export default function LandingApp() {
  const { pathname, hash } = useLocation()
  const path = pathname.length > 1 ? pathname.replace(/\/+$/, '') : pathname

  // New page without an anchor: start at the top.
  useEffect(() => {
    if (!hash) window.scrollTo({ top: 0 })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path])

  return (
    <LandingLayout>
      {path === LANDING_PATHS.privacy ? <LegalPage doc="privacy" /> : path === LANDING_PATHS.terms ? <LegalPage doc="terms" /> : <HomePage />}
    </LandingLayout>
  )
}
