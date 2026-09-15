import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { useAuthStore, selectIsAuthenticated } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'

/**
 * Redirects to /login (preserving the intended destination) when there is
 * no session, and to /ubah-password when the account still has a default
 * password (e.g. a student's NIM+tanggal lahir) that must be changed first.
 */
export function ProtectedRoute() {
  const isAuthenticated = useAuthStore(selectIsAuthenticated)
  const mustChangePassword = useAuthStore((state) => state.session?.user.mustChangePassword ?? false)
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to={ROUTES.login} replace state={{ from: location }} />
  }

  if (mustChangePassword && location.pathname !== ROUTES.changePassword) {
    return <Navigate to={ROUTES.changePassword} replace />
  }

  return <Outlet />
}
