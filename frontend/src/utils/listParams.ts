import type { ListParams } from '@/types/api'

/**
 * Meratakan ListParams jadi query string yang dipahami ListQuery backend
 * (filter[col]=value), tanpa bergantung pada perilaku serialisasi nested
 * object bawaan axios.
 */
export function toQueryParams(params: ListParams = {}): Record<string, string | number> {
  const query: Record<string, string | number> = {}

  if (params.page) query.page = params.page
  if (params.per_page) query.per_page = params.per_page
  if (params.search) query.search = params.search
  if (params.sort) query.sort = params.sort

  for (const [key, value] of Object.entries(params.filter ?? {})) {
    query[`filter[${key}]`] = value
  }

  return query
}
