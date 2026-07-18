import { forwardRef, type InputHTMLAttributes, type ReactNode } from 'react'
import { cn } from '@/utils/cn'

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
  error?: string
  hint?: string
  leftIcon?: ReactNode
  rightSlot?: ReactNode
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  { className, label, error, hint, leftIcon, rightSlot, id, ...props },
  ref,
) {
  const inputId = id ?? props.name

  return (
    <div className="flex flex-col gap-1.5">
      {label && (
        <label htmlFor={inputId} className="text-sm font-medium text-ink-primary">
          {label}
        </label>
      )}

      <div className="relative flex items-center">
        {leftIcon && <span className="pointer-events-none absolute left-3 text-ink-tertiary">{leftIcon}</span>}

        <input
          ref={ref}
          id={inputId}
          className={cn(
            'h-10 w-full rounded-lg border bg-surface px-3 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors',
            'focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary',
            'disabled:cursor-not-allowed disabled:opacity-50',
            leftIcon && 'pl-9',
            rightSlot && 'pr-10',
            error ? 'border-danger focus:ring-danger focus:border-danger' : 'border-border-strong',
            className,
          )}
          aria-invalid={Boolean(error)}
          {...props}
        />

        {rightSlot && <span className="absolute right-2 flex items-center">{rightSlot}</span>}
      </div>

      {error ? (
        <p className="text-xs text-danger">{error}</p>
      ) : hint ? (
        <p className="text-xs text-ink-tertiary">{hint}</p>
      ) : null}
    </div>
  )
})
