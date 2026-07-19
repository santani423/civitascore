import { useCallback, useEffect, useState } from 'react'
import type { NormalizedApiError } from '@/services/api'
import type { ApiPaginationMeta, ListParams, PaginatedResult } from '@/types/api'

export interface UsePaginatedListOptions<T> {
  /** Referensi stabil (mis. `roleService.index`) — jangan wrap dengan arrow function baru tiap render, itu akan memicu fetch berulang. */
  fetcher: (params: ListParams) => Promise<PaginatedResult<T>>
  perPage?: number
}

export function usePaginatedList<T>({ fetcher, perPage = 20 }: UsePaginatedListOptions<T>) {
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState<Record<string, string>>({})
  const [sort, setSort] = useState<string | undefined>(undefined)
  const [data, setData] = useState<T[]>([])
  const [meta, setMeta] = useState<ApiPaginationMeta | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [reloadToken, setReloadToken] = useState(0)

  // Reset ke halaman 1 setiap kali pencarian/filter/sort berubah.
  useEffect(() => {
    setPage(1)
  }, [search, filter, sort])

  useEffect(() => {
    let cancelled = false
    setIsLoading(true)
    setError(null)

    fetcher({
      page,
      per_page: perPage,
      search: search || undefined,
      filter: Object.keys(filter).length > 0 ? filter : undefined,
      sort,
    })
      .then((result) => {
        if (cancelled) return
        setData(result.data)
        setMeta(result.meta)
      })
      .catch((err: NormalizedApiError) => {
        if (cancelled) return
        setError(err.message)
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [fetcher, page, perPage, search, filter, sort, reloadToken])

  const refetch = useCallback(() => setReloadToken((token) => token + 1), [])

  return { data, meta, isLoading, error, page, setPage, search, setSearch, filter, setFilter, sort, setSort, refetch }
}
