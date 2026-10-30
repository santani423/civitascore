import { useId, type FormEvent, type ReactNode } from 'react'
import { ChevronDown, RotateCcw, Search, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { Button } from '../ui/Button'

const labelClass = 'block text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle'
const fieldClass =
  'h-11 w-full rounded-lp-sm border border-lp-strong bg-lp-surface text-[0.9375rem] text-lp-fg transition-colors hover:border-lp-brand/40 focus:border-lp-brand'

/** The filter card. A search landmark; Enter in the search box does nothing extra (results update as you type). */
export function FilterPanel({ label, children }: { label: string; children: ReactNode }) {
  return (
    <form role="search" aria-label={label} onSubmit={(e: FormEvent) => e.preventDefault()} className="rounded-lp-lg border border-lp-line bg-lp-surface p-4 shadow-lp-sm sm:p-6">
      {children}
    </form>
  )
}

export function SearchField({ label, placeholder, value, onChange }: { label: string; placeholder: string; value: string; onChange: (value: string) => void }) {
  const { m } = useLandingI18n()
  const id = useId()
  return (
    <div className="min-w-0">
      <label htmlFor={id} className={labelClass}>
        {label}
      </label>
      <div className="relative mt-2">
        <Search size={17} aria-hidden className="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-lp-subtle" />
        <input
          id={id}
          type="search"
          value={value}
          onChange={(e) => onChange(e.target.value)}
          placeholder={placeholder}
          autoComplete="off"
          enterKeyHint="search"
          className={cn(fieldClass, 'pl-10 pr-11 placeholder:text-lp-subtle [&::-webkit-search-cancel-button]:hidden')}
        />
        {value && (
          <button
            type="button"
            onClick={() => onChange('')}
            aria-label={m.affairs.filters.clearSearch}
            className="absolute right-1 top-1/2 inline-flex h-9 min-h-0 w-9 min-w-0 -translate-y-1/2 items-center justify-center rounded-full text-lp-subtle transition-colors hover:bg-lp-sunken hover:text-lp-fg"
          >
            <X size={16} aria-hidden />
          </button>
        )}
      </div>
    </div>
  )
}

export interface FilterOption {
  value: string
  label: string
  /** Content-language labels (e.g. Indonesian pillar or faculty names). */
  lang?: string
  count?: number
}

export function SelectField({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: FilterOption[] }) {
  const id = useId()
  return (
    <div className="min-w-0">
      <label htmlFor={id} className={labelClass}>
        {label}
      </label>
      <div className="relative mt-2">
        <select id={id} value={value} onChange={(e) => onChange(e.target.value)} className={cn(fieldClass, 'cursor-pointer appearance-none truncate pl-3.5 pr-10')}>
          {options.map((option) => (
            <option key={option.value} value={option.value} lang={option.lang}>
              {option.label}
            </option>
          ))}
        </select>
        <ChevronDown size={17} aria-hidden className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-lp-subtle" />
      </div>
    </div>
  )
}

/**
 * Single-choice chips (toggle buttons). The first option is "all" (value "");
 * pressing the selected chip again returns to it. Scrolls sideways on phones.
 */
export function ChipGroup({ label, value, onChange, options }: { label: string; value: string; onChange: (value: string) => void; options: FilterOption[] }) {
  const id = useId()
  return (
    <div className="min-w-0">
      <p id={id} className={labelClass}>
        {label}
      </p>
      <div role="group" aria-labelledby={id} className="lp-no-scrollbar -mx-4 mt-2 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0">
        {options.map((option) => {
          const on = option.value === value
          return (
            <button
              key={option.value}
              type="button"
              aria-pressed={on}
              onClick={() => onChange(on ? '' : option.value)}
              className={cn(
                'inline-flex h-11 shrink-0 items-center gap-2 whitespace-nowrap rounded-full border px-4 text-[0.875rem] font-semibold transition-colors',
                on ? 'border-lp-brand bg-lp-brand text-lp-onbrand' : 'border-lp-strong bg-lp-surface text-lp-muted hover:border-lp-brand/40 hover:text-lp-fg',
              )}
            >
              {option.label}
              {option.count !== undefined && (
                <span className={cn('lp-tabular min-w-[1.5rem] rounded-full px-1.5 py-0.5 text-center text-[0.75rem] leading-none', on ? 'bg-white/20' : 'bg-lp-sunken text-lp-subtle')}>
                  {option.count}
                </span>
              )}
            </button>
          )
        })}
      </div>
    </div>
  )
}

/** Result count (announced politely as it changes) and the reset action when any filter is set. */
export function ResultSummary({ text, canReset, onReset }: { text: string; canReset: boolean; onReset: () => void }) {
  const { m } = useLandingI18n()
  return (
    <div className="mt-8 flex min-h-11 flex-wrap items-center justify-between gap-3">
      <p role="status" className="text-[0.9375rem] font-medium text-lp-muted">
        {text}
      </p>
      {canReset && (
        <Button variant="ghost" size="sm" onClick={onReset}>
          <RotateCcw size={15} aria-hidden />
          {m.affairs.filters.reset}
        </Button>
      )}
    </div>
  )
}
