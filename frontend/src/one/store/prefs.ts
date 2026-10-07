import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export type Lang = 'id' | 'en' | 'zh'
export type ThemePref = 'light' | 'dark' | 'system'
export type FontScale = 'small' | 'default' | 'large'
/** Lets reviewers preview every page in its loading / empty / error state. */
export type DataState = 'live' | 'loading' | 'empty' | 'error'

interface PrefsState {
  lang: Lang
  theme: ThemePref
  sidebarCollapsed: boolean
  fontScale: FontScale
  reducedMotion: boolean
  highContrast: boolean
  dataState: DataState
  notif: { email: boolean; push: boolean; digest: boolean }
  set: <K extends keyof Omit<PrefsState, 'set'>>(key: K, value: PrefsState[K]) => void
}

function detectLang(): Lang {
  if (typeof navigator === 'undefined') return 'en'
  const l = navigator.language.toLowerCase()
  if (l.startsWith('zh')) return 'zh'
  if (l.startsWith('id') || l.startsWith('ms')) return 'id'
  return 'en'
}

export const usePrefs = create<PrefsState>()(
  persist(
    (set) => ({
      lang: detectLang(),
      theme: 'system',
      sidebarCollapsed: false,
      fontScale: 'default',
      reducedMotion: false,
      highContrast: false,
      dataState: 'live',
      notif: { email: true, push: true, digest: false },
      set: (key, value) => set({ [key]: value } as Partial<PrefsState>),
    }),
    {
      name: 'civitas-one-prefs',
      // The demo data state is a review tool, not a preference — never persist it.
      partialize: ({ set: _set, dataState: _dataState, ...rest }) => rest,
    },
  ),
)
