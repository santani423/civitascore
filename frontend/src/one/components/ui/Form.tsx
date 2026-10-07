import {
  cloneElement,
  forwardRef,
  isValidElement,
  useId,
  type InputHTMLAttributes,
  type ReactElement,
  type ReactNode,
  type SelectHTMLAttributes,
  type TextareaHTMLAttributes,
} from 'react'
import { ChevronDown, Search, X, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

const control =
  'w-full rounded-xl border border-one-line bg-one-card text-[14px] text-one-fg shadow-soft placeholder:text-one-subtle transition-[border-color,box-shadow] hover:border-one-line2 focus:border-one-royal focus:outline-none focus:ring-4 focus:ring-one-royal/15 disabled:cursor-not-allowed disabled:opacity-60 aria-[invalid=true]:border-one-bad aria-[invalid=true]:focus:ring-one-bad/15'

interface FieldProps {
  label: ReactNode
  hint?: ReactNode
  error?: string
  required?: boolean
  className?: string
  children: ReactElement<Record<string, unknown>>
}

/** Wires label, hint and error to the control via ids/aria — no manual plumbing per form. */
export function Field({ label, hint, error, required, className, children }: FieldProps) {
  const id = useId()
  const hintId = hint ? `${id}-hint` : undefined
  const errorId = error ? `${id}-error` : undefined
  const describedBy = [errorId, hintId].filter(Boolean).join(' ') || undefined
  const control = isValidElement(children)
    ? cloneElement(children, { id, 'aria-describedby': describedBy, 'aria-invalid': error ? true : undefined, required })
    : children

  return (
    <div className={cn('space-y-1.5', className)}>
      <label htmlFor={id} className="block text-[13px] font-medium text-one-fg">
        {label}
        {required && (
          <span className="ml-0.5 text-one-bad" aria-hidden>
            *
          </span>
        )}
      </label>
      {control}
      {error ? (
        <p id={errorId} role="alert" className="text-[12.5px] font-medium text-one-bad">
          {error}
        </p>
      ) : (
        hint && (
          <p id={hintId} className="text-[12.5px] text-one-subtle">
            {hint}
          </p>
        )
      )}
    </div>
  )
}

interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  icon?: LucideIcon
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input({ icon: Icon, className, ...rest }, ref) {
  if (!Icon) return <input ref={ref} className={cn(control, 'h-10 px-3.5', className)} {...rest} />
  return (
    <div className="relative">
      <Icon size={16} aria-hidden className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-one-subtle" />
      <input ref={ref} className={cn(control, 'h-10 pl-10 pr-3.5', className)} {...rest} />
    </div>
  )
})

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement>>(function Textarea(
  { className, ...rest },
  ref,
) {
  return <textarea ref={ref} className={cn(control, 'min-h-[96px] resize-y px-3.5 py-2.5 leading-relaxed', className)} {...rest} />
})

export const Select = forwardRef<HTMLSelectElement, SelectHTMLAttributes<HTMLSelectElement>>(function Select(
  { className, children, ...rest },
  ref,
) {
  return (
    <div className="relative">
      <select ref={ref} className={cn(control, 'h-10 cursor-pointer appearance-none pl-3.5 pr-10', className)} {...rest}>
        {children}
      </select>
      <ChevronDown size={16} aria-hidden className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-one-subtle" />
    </div>
  )
})

interface SearchInputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'size'> {
  value: string
  onValueChange: (v: string) => void
  label: string
  size?: 'md' | 'lg'
  clearLabel?: string
}

export function SearchInput({ value, onValueChange, label, size = 'md', clearLabel = 'Clear', className, ...rest }: SearchInputProps) {
  return (
    <div className={cn('relative', className)} role="search">
      <Search
        size={size === 'lg' ? 20 : 16}
        aria-hidden
        className={cn('pointer-events-none absolute top-1/2 -translate-y-1/2 text-one-subtle', size === 'lg' ? 'left-5' : 'left-3.5')}
      />
      <input
        type="search"
        aria-label={label}
        value={value}
        onChange={(e) => onValueChange(e.target.value)}
        className={cn(
          control,
          '[&::-webkit-search-cancel-button]:hidden',
          size === 'lg' ? 'h-14 rounded-2xl pl-14 pr-12 text-[15px]' : 'h-10 pl-10 pr-10',
        )}
        {...rest}
      />
      {value && (
        <button
          type="button"
          onClick={() => onValueChange('')}
          aria-label={clearLabel}
          className="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-one-subtle hover:bg-one-sunken hover:text-one-fg"
        >
          <X size={15} aria-hidden />
        </button>
      )}
    </div>
  )
}

interface SwitchProps {
  checked: boolean
  onChange: (v: boolean) => void
  label: ReactNode
  description?: ReactNode
  className?: string
}

export function Switch({ checked, onChange, label, description, className }: SwitchProps) {
  const id = useId()
  return (
    <div className={cn('flex items-start justify-between gap-6', className)}>
      <div className="min-w-0">
        <label htmlFor={id} className="block cursor-pointer text-[14px] font-medium text-one-fg">
          {label}
        </label>
        {description && <p className="t-small mt-0.5 text-one-subtle">{description}</p>}
      </div>
      <button
        id={id}
        type="button"
        role="switch"
        aria-checked={checked}
        onClick={() => onChange(!checked)}
        className={cn(
          'relative mt-0.5 inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors',
          checked ? 'bg-one-royal' : 'bg-one-line2',
        )}
      >
        <span
          aria-hidden
          className={cn(
            'inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform',
            checked ? 'translate-x-[22px]' : 'translate-x-0.5',
          )}
        />
      </button>
    </div>
  )
}
