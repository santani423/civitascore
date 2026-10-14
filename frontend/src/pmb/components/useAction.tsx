import { useCallback, useState } from 'react'
import { Alert } from '@/components/ui/Alert'
import type { NormalizedApiError } from '@/services/api'

export interface ActionState {
  busy: boolean
  message: { variant: 'success' | 'danger'; text: string } | null
  run: <T>(fn: () => Promise<T>, successText?: string | ((result: T) => string)) => Promise<T | undefined>
  clear: () => void
}

/** Menjalankan aksi async dengan status sibuk & pesan sukses/galat yang seragam. */
export function useAction(): ActionState {
  const [busy, setBusy] = useState(false)
  const [message, setMessage] = useState<ActionState['message']>(null)

  const run = useCallback(async <T,>(fn: () => Promise<T>, successText?: string | ((result: T) => string)) => {
    setBusy(true)
    setMessage(null)

    try {
      const result = await fn()

      if (successText) setMessage({ variant: 'success', text: typeof successText === 'function' ? successText(result) : successText })

      return result
    } catch (err) {
      setMessage({ variant: 'danger', text: describeError(err as NormalizedApiError) })

      return undefined
    } finally {
      setBusy(false)
    }
  }, [])

  return { busy, message, run, clear: () => setMessage(null) }
}

/** Pesan galat ramah: pesan utama + galat validasi pertama / daftar `unmet`. */
export function describeError(err: NormalizedApiError | undefined): string {
  if (!err) return 'Terjadi kesalahan.'
  const errors = (err.errors ?? {}) as Record<string, unknown>
  const firstField = Object.values(errors).find((v) => Array.isArray(v) && typeof v[0] === 'string') as string[] | undefined
  const unmet = Array.isArray(errors.unmet) ? ` (${(errors.unmet as string[]).join(', ')})` : ''

  if (firstField && err.status === 422 && !errors.code) return firstField[0]

  return `${err.message}${unmet}`
}

export function Feedback({ state }: { state: ActionState }) {
  if (!state.message) return null

  return (
    <Alert variant={state.message.variant} onDismiss={state.clear}>
      {state.message.text}
    </Alert>
  )
}
