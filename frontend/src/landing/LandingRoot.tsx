import { useEffect } from 'react'
import { BrowserRouter, Route, Routes, useLocation } from 'react-router-dom'
import { useThemeSync } from '@/hooks/useThemeSync'
import LandingApp from './LandingApp'
import { isLandingPath } from './paths'

/** Any in-app link out of the landing page (e.g. /login) reloads into the full application bundle. */
function LeaveLanding() {
  useEffect(() => {
    window.location.reload()
  }, [])
  return null
}

function LandingGate() {
  const { pathname } = useLocation()
  return isLandingPath(pathname) ? <LandingApp /> : <LeaveLanding />
}

/**
 * Standalone root used by main.tsx when the first page load is a landing
 * path: visitors get the landing chunk only, not the whole admin app.
 */
export default function LandingRoot() {
  useThemeSync()
  return (
    <BrowserRouter>
      <Routes>
        <Route path="*" element={<LandingGate />} />
      </Routes>
    </BrowserRouter>
  )
}
