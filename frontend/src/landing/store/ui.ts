import { create } from 'zustand'
import type { CategoryKey } from '../data/catalog'

export type RequestMode = 'demo' | 'contact'

interface Toast {
  id: number
  message: string
  tone: 'success' | 'info' | 'error'
}

interface LandingUiState {
  request: { open: boolean; mode: RequestMode; interests: CategoryKey[] }
  /** True after the first open — the form chunk (zod, react-hook-form) loads lazily from then on. */
  requestLoaded: boolean
  openRequest: (mode: RequestMode, interests?: CategoryKey[]) => void
  closeRequest: () => void

  /** Selected group in the platform ecosystem diagram (footer links can set it). */
  platformCategory: CategoryKey
  setPlatformCategory: (key: CategoryKey) => void

  toasts: Toast[]
  toast: (message: string, tone?: Toast['tone']) => void
  dismissToast: (id: number) => void

  /** Polite screen-reader announcement (e.g. "Language changed to English."). */
  announcement: string
  announce: (message: string) => void
}

let toastId = 0

export const useLandingUi = create<LandingUiState>()((set) => ({
  request: { open: false, mode: 'demo', interests: [] },
  requestLoaded: false,
  openRequest: (mode, interests = []) => set({ request: { open: true, mode, interests }, requestLoaded: true }),
  closeRequest: () => set((s) => ({ request: { ...s.request, open: false } })),

  platformCategory: 'academic',
  setPlatformCategory: (platformCategory) => set({ platformCategory }),

  toasts: [],
  toast: (message, tone = 'success') => {
    const id = ++toastId
    set((s) => ({ toasts: [...s.toasts.slice(-2), { id, message, tone }] }))
    window.setTimeout(() => set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) })), 4200)
  },
  dismissToast: (id) => set((s) => ({ toasts: s.toasts.filter((t) => t.id !== id) })),

  announcement: '',
  announce: (message) => {
    // Clear first so repeating the same message is still announced.
    set({ announcement: '' })
    window.setTimeout(() => set({ announcement: message }), 60)
  },
}))
