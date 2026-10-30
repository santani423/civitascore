import { useId } from 'react'
import { cn } from '@/utils/cn'

const controlClass =
  'w-full rounded-lp-sm border border-lp-strong bg-lp-surface px-3.5 text-[0.9375rem] text-lp-fg transition-colors placeholder:text-lp-subtle hover:border-lp-brand/40 focus:border-lp-brand aria-[invalid=true]:border-lp-bad'

interface TextFieldProps {
  label: string
  value: string
  onChange: (value: string) => void
  error?: string
  type?: 'text' | 'email'
  placeholder?: string
  autoComplete?: string
  /** Renders a textarea. */
  multiline?: boolean
  required?: boolean
  requiredLabel?: string
}

/** Labelled input with its error wired up for screen readers. */
export function TextField({ label, value, onChange, error, type = 'text', placeholder, autoComplete, multiline, required, requiredLabel }: TextFieldProps) {
  const id = useId()
  const errorId = `${id}-error`
  const shared = { id, value, placeholder, required, 'aria-invalid': error ? true : undefined, 'aria-describedby': error ? errorId : undefined }
  return (
    <div>
      <label htmlFor={id} className="block text-[0.875rem] font-semibold text-lp-fg">
        {label}
        {required && requiredLabel && <span className="ml-1 font-normal text-lp-subtle">({requiredLabel})</span>}
      </label>
      {multiline ? (
        <textarea {...shared} rows={4} onChange={(e) => onChange(e.target.value)} className={cn(controlClass, 'mt-1.5 min-h-28 resize-y py-2.5 leading-relaxed')} />
      ) : (
        <input {...shared} type={type} autoComplete={autoComplete} onChange={(e) => onChange(e.target.value)} className={cn(controlClass, 'mt-1.5 h-11')} />
      )}
      {error && (
        <p id={errorId} className="mt-1.5 text-[0.8125rem] font-medium text-lp-bad">
          {error}
        </p>
      )}
    </div>
  )
}
