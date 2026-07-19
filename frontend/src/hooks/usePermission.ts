import { useAuthStore, selectPermissions } from '@/stores/authStore'

/**
 * OR-match seperti middleware `permission:a,b` di backend — true kalau user
 * punya salah satu dari permission yang diminta. Dipakai untuk
 * menyembunyikan/menonaktifkan aksi yang pasti akan ditolak backend (403).
 */
export function usePermission(required: string | string[]): boolean {
  const permissions = useAuthStore(selectPermissions)
  const requiredList = Array.isArray(required) ? required : [required]

  return requiredList.some((permission) => permissions.includes(permission))
}
