import { useEffect, useState, type FormEvent } from 'react'
import { Modal } from '@/components/ui/Modal'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Checkbox } from '@/components/ui/Checkbox'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { RichTextEditor } from '@/pmb/components/RichTextEditor'
import { describeError } from '@/pmb/components/useAction'
import { fromLocalInput, toLocalInput } from '@/pmb/lib/format'
import type { NormalizedApiError } from '@/services/api'

export type FieldType = 'text' | 'email' | 'number' | 'date' | 'datetime' | 'textarea' | 'richtext' | 'select' | 'checkbox' | 'file' | 'json' | 'multiselect' | 'password'

export interface FieldDef {
  name: string
  label: string
  type?: FieldType
  options?: Array<{ value: string; label: string }>
  required?: boolean
  hint?: string
  placeholder?: string
  /** Tampilkan hanya bila fungsi bernilai true atas nilai form saat ini. */
  visible?: (values: Record<string, unknown>) => boolean
  accept?: string
}

export interface FormDialogProps {
  open: boolean
  title: string
  fields: FieldDef[]
  initial?: Record<string, unknown>
  submitLabel?: string
  onClose: () => void
  /** Nilai: datetime → ISO, number → number, json → objek, file → File; FormData dibuat bila ada field file. */
  onSubmit: (values: Record<string, unknown>) => Promise<unknown>
  description?: string
  wide?: boolean
}

/**
 * Formulir modal generik berbasis definisi field — dipakai halaman admin
 * CRUD. Galat validasi Laravel dipetakan ke field; galat lain di atas.
 */
export function FormDialog({ open, title, fields, initial, submitLabel = 'Simpan', onClose, onSubmit, description, wide }: FormDialogProps) {
  const [values, setValues] = useState<Record<string, unknown>>({})
  const [errors, setErrors] = useState<Record<string, string>>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [busy, setBusy] = useState(false)

  useEffect(() => {
    if (!open) return
    const start: Record<string, unknown> = {}

    for (const field of fields) {
      const value = initial?.[field.name]
      start[field.name] = field.type === 'datetime' ? toLocalInput(value as string | null)
        : field.type === 'date' ? (value ? String(value).slice(0, 10) : '')
        : field.type === 'json' ? (value === undefined || value === null ? '' : JSON.stringify(value, null, 2))
          : field.type === 'checkbox' ? Boolean(value)
            : field.type === 'multiselect' ? (Array.isArray(value) ? value : [])
              : value ?? ''
    }

    setValues(start)
    setErrors({})
    setFormError(null)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open])

  const set = (name: string, value: unknown) => setValues((current) => ({ ...current, [name]: value }))

  async function handleSubmit(event: FormEvent) {
    event.preventDefault()
    setErrors({})
    setFormError(null)
    const output: Record<string, unknown> = {}

    for (const field of fields) {
      if (field.visible && !field.visible(values)) continue
      const raw = values[field.name]

      if (field.type === 'datetime') output[field.name] = fromLocalInput(String(raw ?? ''))
      else if (field.type === 'number') output[field.name] = raw === '' || raw === null || raw === undefined ? null : Number(raw)
      else if (field.type === 'json') {
        if (raw === '' || raw === undefined) {
          output[field.name] = null
        } else {
          try {
            output[field.name] = JSON.parse(String(raw))
          } catch {
            setErrors({ [field.name]: 'Format JSON tidak valid.' })

            return
          }
        }
      } else if (field.type === 'select' || field.type === 'text' || field.type === 'textarea' || field.type === 'richtext' || field.type === 'date' || field.type === 'email') output[field.name] = raw === '' ? null : raw
      else output[field.name] = raw
    }

    setBusy(true)

    try {
      await onSubmit(output)
      onClose()
    } catch (err) {
      const apiError = err as NormalizedApiError
      const fieldErrors: Record<string, string> = {}

      for (const [key, messages] of Object.entries(apiError.errors ?? {})) {
        if (Array.isArray(messages) && typeof messages[0] === 'string') fieldErrors[key] = messages[0]
      }

      setErrors(fieldErrors)
      const unmapped = Object.keys(fieldErrors).filter((key) => !fields.some((f) => key === f.name || key.startsWith(`${f.name}.`)))
      setFormError(Object.keys(fieldErrors).length === 0 || unmapped.length > 0 ? describeError(apiError) : null)
    } finally {
      setBusy(false)
    }
  }

  const fieldError = (name: string) => errors[name] ?? Object.entries(errors).find(([key]) => key.startsWith(`${name}.`))?.[1]

  return (
    <Modal open={open} onClose={onClose} title={title} className={wide ? 'max-w-3xl' : undefined}>
      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        {description && <p className="text-sm text-ink-secondary">{description}</p>}
        {formError && <Alert variant="danger">{formError}</Alert>}
        {fields.filter((f) => !f.visible || f.visible(values)).map((field) => {
          const common = { label: field.label + (field.required ? ' *' : ''), hint: field.hint, error: fieldError(field.name) }
          const value = values[field.name]

          switch (field.type) {
            case 'textarea':
            case 'json':
              return <Textarea key={field.name} {...common} rows={field.type === 'json' ? 6 : 4} value={String(value ?? '')} placeholder={field.placeholder} onChange={(e) => set(field.name, e.target.value)} className={field.type === 'json' ? 'font-mono text-xs' : undefined} />
            case 'richtext':
              return <RichTextEditor key={field.name} {...common} value={String(value ?? '')} onChange={(html) => set(field.name, html)} />
            case 'select':
              return <Select key={field.name} {...common} options={field.options ?? []} placeholder="Pilih..." value={String(value ?? '')} onChange={(e) => set(field.name, e.target.value)} />
            case 'checkbox':
              return <Checkbox key={field.name} label={field.label} checked={Boolean(value)} onChange={(e) => set(field.name, e.target.checked)} />
            case 'multiselect':
              return (
                <fieldset key={field.name} className="flex flex-col gap-1">
                  <legend className="mb-1 text-sm font-medium text-ink-primary">{common.label}</legend>
                  <div className="grid grid-cols-2 gap-1">
                    {(field.options ?? []).map((option) => {
                      const list = (value as string[]) ?? []

                      return <Checkbox key={option.value} label={option.label} checked={list.includes(option.value)} onChange={(e) => set(field.name, e.target.checked ? [...list, option.value] : list.filter((v) => v !== option.value))} />
                    })}
                  </div>
                  {common.error && <p className="text-xs text-danger">{common.error}</p>}
                </fieldset>
              )
            case 'file':
              return <Input key={field.name} {...common} type="file" accept={field.accept} onChange={(e) => set(field.name, e.target.files?.[0] ?? null)} />
            default:
              return (
                <Input
                  key={field.name}
                  {...common}
                  type={field.type === 'datetime' ? 'datetime-local' : field.type ?? 'text'}
                  step={field.type === 'number' ? 'any' : undefined}
                  placeholder={field.placeholder}
                  value={String(value ?? '')}
                  onChange={(e) => set(field.name, e.target.value)}
                />
              )
          }
        })}
        <div className="flex justify-end gap-2 pt-2">
          <Button type="button" variant="outline" onClick={onClose}>Batal</Button>
          <Button type="submit" isLoading={busy}>{submitLabel}</Button>
        </div>
      </form>
    </Modal>
  )
}

/** Mengubah objek nilai (dengan File) menjadi FormData untuk unggahan multipart. */
export function toFormData(values: Record<string, unknown>): FormData {
  const form = new FormData()

  for (const [key, value] of Object.entries(values)) {
    if (value === null || value === undefined) continue
    if (value instanceof File) form.append(key, value)
    else if (typeof value === 'boolean') form.append(key, value ? '1' : '0')
    else if (typeof value === 'object') form.append(key, JSON.stringify(value))
    else form.append(key, String(value))
  }

  return form
}
