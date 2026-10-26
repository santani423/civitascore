import { create } from 'zustand'
import { persist } from 'zustand/middleware'

export const TEXT_SIZES = ['sm', 'md', 'lg', 'xl'] as const
export const CONTRASTS = ['normal', 'high', 'dark'] as const
export const FONTS = ['default', 'readable'] as const
export const SPACINGS = ['normal', 'wide', 'wider'] as const

export interface A11yPrefs {
  textSize: (typeof TEXT_SIZES)[number]
  contrast: (typeof CONTRASTS)[number]
  font: (typeof FONTS)[number]
  spacing: (typeof SPACINGS)[number]
  colorSafe: boolean
  easyRead: boolean
  strongFocus: boolean
  largeTargets: boolean
  reduceMotion: boolean
}

export const DEFAULT_A11Y: A11yPrefs = {
  textSize: 'md',
  contrast: 'normal',
  font: 'default',
  spacing: 'normal',
  colorSafe: false,
  easyRead: false,
  strongFocus: false,
  largeTargets: false,
  reduceMotion: false,
}

interface A11yState extends A11yPrefs {
  panelOpen: boolean
  set: <K extends keyof A11yPrefs>(key: K, value: A11yPrefs[K]) => void
  reset: () => void
  openPanel: () => void
  closePanel: () => void
}

/** Keeps only valid values from storage, so a stale or edited entry can never break rendering. */
function sanitize(raw: unknown): A11yPrefs {
  const src = (raw && typeof raw === 'object' ? raw : {}) as Partial<Record<keyof A11yPrefs, unknown>>
  const pick = <T extends string>(list: readonly T[], value: unknown, fallback: T): T => (list.includes(value as T) ? (value as T) : fallback)
  const flag = (value: unknown, fallback: boolean) => (typeof value === 'boolean' ? value : fallback)
  return {
    textSize: pick(TEXT_SIZES, src.textSize, DEFAULT_A11Y.textSize),
    contrast: pick(CONTRASTS, src.contrast, DEFAULT_A11Y.contrast),
    font: pick(FONTS, src.font, DEFAULT_A11Y.font),
    spacing: pick(SPACINGS, src.spacing, DEFAULT_A11Y.spacing),
    colorSafe: flag(src.colorSafe, false),
    easyRead: flag(src.easyRead, false),
    strongFocus: flag(src.strongFocus, false),
    largeTargets: flag(src.largeTargets, false),
    reduceMotion: flag(src.reduceMotion, false),
  }
}

/**
 * Landing-page accessibility preferences, kept only in this browser's
 * localStorage (never sent anywhere, nothing sensitive). Separate from the
 * app's theme store; applied to <html> by hooks/useA11yEffect.ts.
 */
export const useA11y = create<A11yState>()(
  persist(
    (set) => ({
      ...DEFAULT_A11Y,
      panelOpen: false,
      set: (key, value) => set({ [key]: value } as Partial<A11yPrefs>),
      reset: () => set({ ...DEFAULT_A11Y }),
      openPanel: () => set({ panelOpen: true }),
      closePanel: () => set({ panelOpen: false }),
    }),
    {
      name: 'civitas-one-atma-a11y',
      partialize: (s): A11yPrefs => ({
        textSize: s.textSize,
        contrast: s.contrast,
        font: s.font,
        spacing: s.spacing,
        colorSafe: s.colorSafe,
        easyRead: s.easyRead,
        strongFocus: s.strongFocus,
        largeTargets: s.largeTargets,
        reduceMotion: s.reduceMotion,
      }),
      merge: (persisted, current) => ({ ...current, ...sanitize(persisted) }),
    },
  ),
)
