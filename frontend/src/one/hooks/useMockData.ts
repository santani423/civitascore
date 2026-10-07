import { useCallback, useEffect, useState } from 'react'
import { usePrefs } from '../store/prefs'

/**
 * Stand-in for a data fetch. Resolves mock data after a short delay so
 * skeletons are exercised, and honors the Settings → Demo data switch so
 * every page can be previewed in its loading / empty / error state.
 */
export function useMockData<T>(factory: () => T, empty: T, delay = 450) {
  const dataState = usePrefs((s) => s.dataState)
  const [attempt, setAttempt] = useState(0)
  const [state, setState] = useState<{ status: 'loading' | 'error' | 'ready'; data: T }>({ status: 'loading', data: empty })

  useEffect(() => {
    setState((s) => ({ ...s, status: 'loading' }))
    if (dataState === 'loading') return
    const timer = window.setTimeout(() => {
      if (dataState === 'error') setState({ status: 'error', data: empty })
      else setState({ status: 'ready', data: dataState === 'empty' ? empty : factory() })
    }, delay)
    return () => window.clearTimeout(timer)
    // factory/empty are expected to be stable module-level values per call site.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [dataState, attempt, delay])

  const retry = useCallback(() => setAttempt((a) => a + 1), [])

  return { ...state, loading: state.status === 'loading', error: state.status === 'error', retry }
}
