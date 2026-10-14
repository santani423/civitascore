import { useCallback, useEffect, useRef, useState } from 'react'
import type { NormalizedApiError } from '@/services/api'

/**
 * Memuat data saat mount & saat `deps` berubah; `reload()` untuk muat ulang.
 * Fetcher boleh closure baru tiap render — yang menentukan muat ulang adalah `deps`.
 */
export function useLoad<T>(fetcher: () => Promise<T>, deps: unknown[] = []) {
  const [data, setData] = useState<T | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<NormalizedApiError | null>(null)
  const [token, setToken] = useState(0)
  const ref = useRef(fetcher)
  ref.current = fetcher

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    ref.current()
      .then((result) => {
        if (!cancelled) setData(result)
      })
      .catch((err: NormalizedApiError) => {
        if (!cancelled) setError(err)
      })
      .finally(() => {
        if (!cancelled) setLoading(false)
      })

    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, ...deps])

  const reload = useCallback(() => setToken((t) => t + 1), [])

  return { data, loading, error, reload, setData }
}
