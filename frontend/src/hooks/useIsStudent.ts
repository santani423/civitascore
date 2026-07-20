import { useAuthStore } from '@/stores/authStore'

/** Membedakan menu Portal Mahasiswa (self-service) dari menu operasional admin biasa. */
export function useIsStudent(): boolean {
  return useAuthStore((state) => state.session?.user.roles.includes('student') ?? false)
}
