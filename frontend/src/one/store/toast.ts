import { create } from 'zustand'

export type ToastTone = 'success' | 'info' | 'warning' | 'error'

export interface ToastItem {
  id: number
  message: string
  tone: ToastTone
}

interface ToastState {
  toasts: ToastItem[]
  push: (message: string, tone?: ToastTone) => void
  dismiss: (id: number) => void
}

let seq = 0

export const useToastStore = create<ToastState>()((set, get) => ({
  toasts: [],
  push: (message, tone = 'success') => {
    const id = ++seq
    set({ toasts: [...get().toasts.slice(-3), { id, message, tone }] })
    window.setTimeout(() => get().dismiss(id), 4200)
  },
  dismiss: (id) => set({ toasts: get().toasts.filter((t) => t.id !== id) }),
}))

/** `const toast = useToast(); toast('Saved')` */
export function useToast() {
  return useToastStore((s) => s.push)
}
