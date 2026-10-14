/**
 * Kampus untuk website & portal PMB (tanpa login staf). Urutan: parameter
 * `?kampus=KODE` (disimpan untuk kunjungan berikutnya) → VITE_PMB_UNIVERSITY_CODE
 * → kosong (backend menentukan dari domain, lihat ResolvePublicTenant).
 */
const STORAGE_KEY = 'civitasone-pmb-campus'

export function resolveCampusCode(): string | null {
  try {
    const fromQuery = new URLSearchParams(window.location.search).get('kampus')

    if (fromQuery) {
      window.localStorage.setItem(STORAGE_KEY, fromQuery)

      return fromQuery
    }

    const stored = window.localStorage.getItem(STORAGE_KEY)

    if (stored) return stored
  } catch {
    // localStorage dapat diblokir (mode privat) — lanjut ke env.
  }

  return (import.meta.env.VITE_PMB_UNIVERSITY_CODE as string | undefined) ?? null
}
