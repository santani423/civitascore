import { useCallback, useEffect, useState } from 'react'
import type { NormalizedApiError } from '@/services/api'

/**
 * Fetch-on-mount sederhana untuk endpoint non-paginated (sessions, devices,
 * notification-channels, dst). `fetcher` harus referensi stabil.
 */
export function useFetch<T>(fetcher: () => Promise<T>) {
  const [data, setData] = useState<T | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [reloadToken, setReloadToken] = useState(0)

  useEffect(() => {
    let cancelled = false
    setIsLoading(true)
    setError(null)

    fetcher()
      .then((result) => {
        if (!cancelled) setData(result)
      })
      .catch((err: NormalizedApiError) => {
        if (!cancelled) setError(err.message)
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [fetcher, reloadToken])

  const refetch = useCallback(() => setReloadToken((token) => token + 1), [])

  return { data, isLoading, error, refetch, setData }
}
