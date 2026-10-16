import { useCallback } from 'react'
import { flushSync } from 'react-dom'
import { useThemeStore, type Theme } from '@/stores/themeStore'
import { prefersReducedMotion } from './useScroll'

type DocumentWithTransitions = Document & { startViewTransition?: (update: () => void) => unknown }

/**
 * Theme switching for the landing page. Uses the app-wide theme store, so
 * the choice persists (localStorage) and carries over into the application;
 * before any choice the store starts from the OS preference.
 * The switch cross-fades via the View Transitions API where supported.
 */
export function useThemeControl() {
  const theme = useThemeStore((s) => s.theme)
  const setStoreTheme = useThemeStore((s) => s.setTheme)

  const setTheme = useCallback(
    (next: Theme) => {
      if (next === useThemeStore.getState().theme) return
      const root = document.documentElement
      const commit = () => {
        root.classList.toggle('dark', next === 'dark')
        flushSync(() => setStoreTheme(next))
      }
      if (prefersReducedMotion()) {
        commit()
        return
      }
      const doc = document as DocumentWithTransitions
      if (typeof doc.startViewTransition === 'function') {
        doc.startViewTransition(commit)
        return
      }
      root.classList.add('lp-theme-switching')
      commit()
      window.setTimeout(() => root.classList.remove('lp-theme-switching'), 320)
    },
    [setStoreTheme],
  )

  const toggle = useCallback(() => setTheme(theme === 'dark' ? 'light' : 'dark'), [setTheme, theme])

  return { theme, isDark: theme === 'dark', setTheme, toggle }
}
