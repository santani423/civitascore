import { useCallback, useMemo } from 'react'
import { useSearchParams } from 'react-router-dom'

/**
 * Filter state kept in the query string (`?kategori=olahraga&q=futsal`), so a
 * filtered list can be shared or bookmarked and Back returns to it. Values
 * that fail `allowed` are treated as unset. Updates replace the history entry.
 *
 * Updates start from `window.location.search`, not the router's `prev`: the
 * router applies navigations in a transition, so two quick changes (a chip,
 * then a select) would otherwise both start from the same stale params and
 * the first one would be lost.
 */
export function useFilterParams<K extends string>(keys: readonly K[], allowed: Partial<Record<K, readonly string[]>> = {}) {
  const [params, setParams] = useSearchParams()

  const values = useMemo(() => {
    const out = {} as Record<K, string>
    for (const key of keys) {
      const raw = params.get(key)?.trim() ?? ''
      const list = allowed[key]
      out[key] = raw && (!list || list.includes(raw)) ? raw : ''
    }
    return out
    // `keys` and `allowed` are static per page.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [params])

  const set = useCallback(
    (key: K, value: string) =>
      setParams(
        () => {
          const next = new URLSearchParams(window.location.search)
          if (value) next.set(key, value)
          else next.delete(key)
          return next
        },
        { replace: true },
      ),
    [setParams],
  )

  const reset = useCallback(
    () =>
      setParams(
        () => {
          const next = new URLSearchParams(window.location.search)
          for (const key of keys) next.delete(key)
          return next
        },
        { replace: true },
      ),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [setParams],
  )

  const active = keys.some((key) => values[key] !== '')
  return { values, set, reset, active }
}

/** The list to go back to from a detail page: the same filtered list when the visitor came from one (router state `from`). */
export function backToList(listPath: string, state: unknown): string {
  const from = (state as { from?: unknown } | null)?.from
  return typeof from === 'string' && from.startsWith('?') ? `${listPath}${from}` : listPath
}
