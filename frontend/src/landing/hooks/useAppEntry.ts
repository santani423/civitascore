import { LayoutDashboard, LogIn } from 'lucide-react'
import { ROUTES } from '@/constants/routes'
import { useAuthStore, selectIsAuthenticated } from '@/stores/authStore'
import { useLandingI18n } from '../i18n'

/**
 * Where the landing's sign-in buttons go: the existing /login page for
 * visitors, /dashboard when a session already exists (/login would redirect
 * there anyway via PublicOnlyRoute). Read-only use of the shared auth store.
 */
export function useAppEntry() {
  const { m } = useLandingI18n()
  const authenticated = useAuthStore(selectIsAuthenticated)
  return authenticated
    ? { to: ROUTES.dashboard, label: m.nav.openDashboard, icon: LayoutDashboard, authenticated }
    : { to: ROUTES.login, label: m.nav.signIn, icon: LogIn, authenticated }
}

export type AppEntry = ReturnType<typeof useAppEntry>
