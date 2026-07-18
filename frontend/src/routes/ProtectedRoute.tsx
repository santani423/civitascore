import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore, selectIsAuthenticated } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'

/** Redirects to /login (preserving the intended destination) when there is no session. */
export function ProtectedRoute() {
  const isAuthenticated = useAuthStore(selectIsAuthenticated)
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to={ROUTES.login} replace state={{ from: location }} />
  }

  return <Outlet />
}
