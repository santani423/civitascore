import { Navigate, Outlet } from 'react-router-dom'
import { useAuthStore, selectIsAuthenticated } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'

/** Sends an already-authenticated user straight to the dashboard instead of showing /login again. */
export function PublicOnlyRoute() {
  const isAuthenticated = useAuthStore(selectIsAuthenticated)

  if (isAuthenticated) {
    return <Navigate to={ROUTES.dashboard} replace />
  }

  return <Outlet />
}
