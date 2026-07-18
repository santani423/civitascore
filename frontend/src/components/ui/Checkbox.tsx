import { forwardRef, type InputHTMLAttributes } from 'react'
import { cn } from '@/utils/cn'

export interface CheckboxProps extends InputHTMLAttributes<HTMLInputElement> {
  label?: string
}

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
  { className, label, id, ...props },
  ref,
) {
  const inputId = id ?? props.name

  return (
    <label htmlFor={inputId} className="inline-flex select-none items-center gap-2 text-sm text-ink-secondary">
      <input
        ref={ref}
        id={inputId}
        type="checkbox"
        className={cn(
          'size-4 rounded border-border-strong text-primary accent-[var(--color-primary)]',
          'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary',
          'disabled:cursor-not-allowed disabled:opacity-50',
          className,
        )}
        {...props}
      />
      {label}
    </label>
  )
})
