import { useEffect, useMemo, useState } from 'react'
import { create } from 'zustand'
import { notifications } from '../data/services'
import { useApp } from '../store/app'
import { usePrefs } from '../store/prefs'

export function useMediaQuery(query: string): boolean {
  const [matches, setMatches] = useState(() => typeof window !== 'undefined' && window.matchMedia(query).matches)
  useEffect(() => {
    const mql = window.matchMedia(query)
    const onChange = () => setMatches(mql.matches)
    onChange()
    mql.addEventListener('change', onChange)
    return () => mql.removeEventListener('change', onChange)
  }, [query])
  return matches
}

/** 'light' | 'dark' after resolving the 'system' preference. */
export function useResolvedTheme(): 'light' | 'dark' {
  const pref = usePrefs((s) => s.theme)
  const systemDark = useMediaQuery('(prefers-color-scheme: dark)')
  return pref === 'system' ? (systemDark ? 'dark' : 'light') : pref
}

export function useNotifications() {
  const readIds = useApp((s) => s.readNotifications)
  const unreadIds = useApp((s) => s.unreadOverrides)
  return useMemo(() => {
    const items = notifications.map((n) => ({
      ...n,
      read: !unreadIds.includes(n.id) && (n.read || readIds.includes(n.id)),
    }))
    return { items, unread: items.filter((n) => !n.read).length }
  }, [readIds, unreadIds])
}

/** UI-only shell state shared by the topbar, bottom nav and keyboard shortcuts. */
export const useShell = create<{ paletteOpen: boolean; drawerOpen: boolean; setPalette: (v: boolean) => void; setDrawer: (v: boolean) => void }>()(
  (set) => ({
    paletteOpen: false,
    drawerOpen: false,
    setPalette: (v) => set({ paletteOpen: v }),
    setDrawer: (v) => set({ drawerOpen: v }),
  }),
)
