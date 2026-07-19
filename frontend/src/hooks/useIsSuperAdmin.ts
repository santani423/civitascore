import { useAuthStore } from '@/stores/authStore'

/** Membedakan menu Platform (Super Admin) dari menu operasional tenant biasa — lihat docs/RANCANGAN-SUPER-ADMIN.md §1-2. */
export function useIsSuperAdmin(): boolean {
  return useAuthStore((state) => state.session?.user.roles.includes('super_admin') ?? false)
}
