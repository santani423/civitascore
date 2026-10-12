import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { detectLocale, isLocale, type Locale } from '../i18n/locales'

interface LandingPrefsState {
  locale: Locale
  setLocale: (locale: Locale) => void
}

/** `?lang=en` on a shared link wins over the stored preference (and is then remembered). */
function localeFromUrl(): Locale | null {
  if (typeof window === 'undefined') return null
  const value = new URLSearchParams(window.location.search).get('lang')
  return isLocale(value) ? value : null
}

/**
 * Landing-page language preference, persisted to localStorage so it survives
 * reloads and moving between the landing and legal pages. Theme is not here:
 * it is shared with the application via stores/themeStore.ts.
 */
export const useLandingPrefs = create<LandingPrefsState>()(
  persist(
    (set) => ({
      locale: detectLocale(),
      setLocale: (locale) => set({ locale }),
    }),
    {
      name: 'civitas-one-atma-prefs',
      partialize: ({ locale }) => ({ locale }),
      merge: (persisted, current) => {
        const stored = (persisted as Partial<LandingPrefsState> | undefined)?.locale
        const locale = localeFromUrl() ?? (isLocale(stored) ? stored : current.locale)
        return { ...current, locale }
      },
    },
  ),
)
