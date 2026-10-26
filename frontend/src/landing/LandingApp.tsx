import { Suspense, lazy, useEffect } from 'react'
import { useLocation } from 'react-router-dom'
import { LandingLayout } from './components/layout/LandingLayout'
import { HomePage } from './pages/HomePage'
import { LegalPage } from './pages/LegalPage'
import { StudentLifePage } from './pages/StudentLifePage'
import { DETAIL_PARENTS, LANDING_PATHS, normalizePath } from './paths'

// Kegiatan, Ormawa and UKM (with their sample data), and the faculty pages, are fetched only when opened.
const StudentAffairsPage = lazy(() => import('./pages/student-affairs/StudentAffairsPage'))
const FacultyPages = lazy(() => import('./pages/faculty/FacultyPages'))

function isUnder(path: string, parent: string): boolean {
  return path === parent || path.startsWith(`${parent}/`)
}

/**
 * One Atma Jaya public landing page. Static content, plus one read-only call
 * to the existing public PMB news endpoint (lib/pmbNews.ts). Rendered by
 * App.tsx for the landing paths, and by LandingRoot when the visitor lands
 * here directly (so the admin bundle isn't downloaded).
 */
export default function LandingApp() {
  const { pathname, hash } = useLocation()
  const path = normalizePath(pathname)

  // New page without an anchor: start at the top.
  useEffect(() => {
    if (!hash) window.scrollTo({ top: 0 })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path])

  return <LandingLayout>{renderPage(path)}</LandingLayout>
}

function renderPage(path: string) {
  if (isUnder(path, LANDING_PATHS.faculties)) {
    return (
      <Suspense fallback={<div className="min-h-screen" />}>
        <FacultyPages path={path} />
      </Suspense>
    )
  }
  if (DETAIL_PARENTS.some((parent) => isUnder(path, parent))) {
    return (
      <Suspense fallback={<div className="min-h-screen" />}>
        <StudentAffairsPage path={path} />
      </Suspense>
    )
  }
  switch (path) {
    case LANDING_PATHS.studentLife:
      return <StudentLifePage />
    case LANDING_PATHS.privacy:
      return <LegalPage doc="privacy" />
    case LANDING_PATHS.terms:
      return <LegalPage doc="terms" />
    default:
      return <HomePage />
  }
}
