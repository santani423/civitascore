import type { ListParams, PaginatedResult } from '@/types/api'

/**
 * Mengambil seluruh baris sebuah daftar halaman demi halaman. Backend
 * membatasi per_page (maks 100), sehingga memuat satu halaman saja diam-diam
 * membuang sisa data; fungsi ini membaca sampai last_page.
 */
export async function fetchAllPages<T>(
  fetchPage: (params: ListParams) => Promise<PaginatedResult<T>>,
  params: ListParams = {},
): Promise<T[]> {
  const rows: T[] = []
  let page = 1
  let lastPage = 1

  do {
    const result = await fetchPage({ ...params, page, per_page: 100 })
    rows.push(...result.data)
    lastPage = result.meta.last_page
    page += 1
  } while (page <= lastPage)

  return rows
}
