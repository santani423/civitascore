import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export interface SelectedUniversity {
  id: string
  name: string
}

interface TenantState {
  selectedUniversity: SelectedUniversity | null
  supportSessionId: string | null
  enterTenant: (university: SelectedUniversity, supportSessionId: string) => void
  exitTenant: () => void
}

/**
 * Cuma diisi lewat Tenant Switcher (Super Admin) — lihat
 * docs/RANCANGAN-SUPER-ADMIN.md §1. Pengguna tenant biasa tidak pernah
 * menyentuh store ini; mereka tetap otomatis di-resolve backend lewat
 * membership default (lihat ResolveUniversityMiddleware).
 */
export const useTenantStore = create<TenantState>()(
  persist(
    (set) => ({
      selectedUniversity: null,
      supportSessionId: null,
      enterTenant: (university, supportSessionId) => set({ selectedUniversity: university, supportSessionId }),
      exitTenant: () => set({ selectedUniversity: null, supportSessionId: null }),
    }),
    { name: 'civitasone-tenant' },
  ),
)
