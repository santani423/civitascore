import type { FieldValues, Path, UseFormSetError } from 'react-hook-form'
import type { NormalizedApiError } from '@/services/api'

/**
 * Memetakan NormalizedApiError.errors (bentuk {field: string[]} dari Laravel)
 * ke error per-field react-hook-form. Field yang tidak dikenali form
 * (mis. error umum tanpa field) dikembalikan sebagai pesan top-level untuk
 * ditampilkan lewat Alert, meniru pola formError di LoginPage.
 *
 * Hanya nilai berbentuk daftar pesan yang dipetakan — 409 ber-kode
 * (`errors.code`, `errors.conflicts`, dst.) bukan error per-field, jadi
 * pesannya dikembalikan sebagai pesan top-level.
 */
export function applyServerErrors<T extends FieldValues>(
  error: NormalizedApiError,
  setError: UseFormSetError<T>,
): string | null {
  const entries = Object.entries((error.errors ?? {}) as Record<string, unknown>).filter(
    (entry): entry is [string, string[]] => Array.isArray(entry[1]) && typeof entry[1][0] === 'string',
  )

  if (entries.length === 0) {
    return error.message
  }

  for (const [field, messages] of entries) {
    setError(field as Path<T>, { type: 'server', message: messages[0] })
  }

  return null
}
