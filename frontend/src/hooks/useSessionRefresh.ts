import { useEffect } from 'react'
import { authService } from '@/services/authService'
import { useAuthStore } from '@/stores/authStore'

/**
 * Menyegarkan roles & permissions sesi yang tersimpan (localStorage) dari
 * GET /me sekali saat layout dimuat — supaya permission baru yang diberikan
 * ke sebuah role (mis. layanan Portal Mahasiswa) langsung berlaku tanpa
 * pengguna harus logout-login. 401 sudah ditangani interceptor apiClient.
 */
export function useSessionRefresh(): void {
  useEffect(() => {
    let cancelled = false

    authService
      .getCurrentUser()
      .then((user) => {
        if (cancelled) return

        const { session, setSession } = useAuthStore.getState()
        if (session) setSession({ ...session, user })
      })
      .catch(() => {
        // Gagal menyegarkan bukan alasan memblokir halaman — sesi lama tetap dipakai.
      })

    return () => {
      cancelled = true
    }
  }, [])
}
