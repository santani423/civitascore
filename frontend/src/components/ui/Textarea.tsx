import { forwardRef, type TextareaHTMLAttributes } from 'react'
import { cn } from '@/utils/cn'

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string
  error?: string
  hint?: string
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  { className, label, error, hint, id, rows = 3, ...props },
  ref,
) {
  const textareaId = id ?? props.name

  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label htmlFor={textareaId} className="text-sm font-medium text-ink-primary">
          {label}
        </label>
      )}

      <textarea
        ref={ref}
        id={textareaId}
        rows={rows}
        className={cn(
          'w-full resize-y rounded-lg border bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors',
          'focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
          'disabled:cursor-not-allowed disabled:opacity-50',
          error ? 'border-danger focus:ring-danger focus:border-danger' : 'border-border-strong',
          className,
        )}
        aria-invalid={Boolean(error)}
        {...props}
      />

      {error ? (
        <p className="text-xs text-danger">{error}</p>
      ) : hint ? (
        <p className="text-xs text-ink-tertiary">{hint}</p>
      ) : null}
    </div>
  )
})
