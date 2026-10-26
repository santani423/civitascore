import { create } from 'zustand'

interface Toast {
  id: number
  message: string
  tone: 'success' | 'info' | 'error'
}

interface LandingUiState {
  contactOpen: boolean
  /** True after the first open — the form chunk (zod, react-hook-form) loads lazily from then on. */
  contactLoaded: boolean
  openContact: () => void
  closeContact: () => void

  toasts: Toast[]
  toast: (message: string, tone?: Toast['tone']) => void
  dismissToast: (id: number) => void

  /** Polite screen-reader announcement (e.g. "Language changed to English."). */
  announcement: string
  announce: (message: string) => void
}

let toastId = 0

export const useLandingUi = create<LandingUiState>()((set) => ({
  contactOpen: false,
  contactLoaded: false,
  openContact: () => set({ contactOpen: true, contactLoaded: true }),
  closeContact: () => set({ contactOpen: false }),

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
