import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import type { AuthSession } from '@/types/auth'

interface AuthState {
  session: AuthSession | null
  setSession: (session: AuthSession) => void
  clearSession: () => void
}

/**
 * Persisted to localStorage so a refresh doesn't log the user out. This is
 * the only piece of auth state the app should read from — pages/components
 * should never touch localStorage directly.
 */
export const useAuthStore = create<AuthState>()(
  persist(
    (set) => ({
      session: null,
      setSession: (session) => set({ session }),
      clearSession: () => set({ session: null }),
    }),
    { name: 'civitasone-auth' },
  ),
)

export const selectIsAuthenticated = (state: AuthState): boolean => state.session !== null

export const selectPermissions = (state: AuthState): string[] => state.session?.user.permissions ?? []
