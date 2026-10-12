import { StrictMode, Suspense, lazy } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import { isLandingPath } from './landing/paths'

// A first visit to the public landing page boots only the landing bundle;
// every other path boots the full application (which can still render the
// landing page itself when navigated to in-app).
const Root = isLandingPath(window.location.pathname) ? lazy(() => import('./landing/LandingRoot')) : lazy(() => import('./App.tsx'))

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <Suspense fallback={null}>
      <Root />
    </Suspense>
  </StrictMode>,
)
