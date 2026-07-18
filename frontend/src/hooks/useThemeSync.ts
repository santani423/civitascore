import { useEffect } from 'react'
import { useThemeStore } from '@/stores/themeStore'

/** Keeps the `dark` class on <html> in sync with the persisted theme store. */
export function useThemeSync(): void {
  const theme = useThemeStore((state) => state.theme)

  useEffect(() => {
    document.documentElement.classList.toggle('dark', theme === 'dark')
  }, [theme])
}
