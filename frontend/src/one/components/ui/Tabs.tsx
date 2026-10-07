import { useRef, type KeyboardEvent, type ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

export interface TabItem<V extends string> {
  value: V
  label: ReactNode
  icon?: LucideIcon
  count?: number
}

interface TabsProps<V extends string> {
  /** Shared id prefix linking tabs to their panels. */
  id: string
  items: TabItem<V>[]
  value: V
  onChange: (v: V) => void
  label: string
  variant?: 'line' | 'pill'
  className?: string
}

/** WAI-ARIA tabs: roving tabindex, ←/→/Home/End to move, automatic activation. */
export function Tabs<V extends string>({ id, items, value, onChange, label, variant = 'line', className }: TabsProps<V>) {
  const refs = useRef<Array<HTMLButtonElement | null>>([])

  const onKeyDown = (e: KeyboardEvent, index: number) => {
    const last = items.length - 1
    const next =
      e.key === 'ArrowRight' ? (index === last ? 0 : index + 1)
      : e.key === 'ArrowLeft' ? (index === 0 ? last : index - 1)
      : e.key === 'Home' ? 0
      : e.key === 'End' ? last
      : -1
    if (next < 0) return
    e.preventDefault()
    onChange(items[next].value)
    refs.current[next]?.focus()
  }

  return (
    <div
      role="tablist"
      aria-label={label}
      className={cn(
        'no-scrollbar flex max-w-full overflow-x-auto',
        variant === 'line' ? 'gap-1 border-b border-one-line' : 'gap-1 rounded-xl bg-one-sunken p-1',
        className,
      )}
    >
      {items.map((item, i) => {
        const selected = item.value === value
        const Icon = item.icon
        return (
          <button
            key={item.value}
            ref={(el) => {
              refs.current[i] = el
            }}
            id={`${id}-tab-${item.value}`}
            role="tab"
            type="button"
            aria-selected={selected}
            aria-controls={`${id}-panel-${item.value}`}
            tabIndex={selected ? 0 : -1}
            onClick={() => onChange(item.value)}
            onKeyDown={(e) => onKeyDown(e, i)}
            className={cn(
              'relative inline-flex shrink-0 items-center gap-2 whitespace-nowrap text-[14px] font-medium transition-colors',
              variant === 'line' && [
                'h-11 px-3 after:absolute after:inset-x-2 after:-bottom-px after:h-0.5 after:rounded-full after:transition-colors',
                selected ? 'text-one-fg after:bg-one-royal' : 'text-one-subtle hover:text-one-fg after:bg-transparent',
              ],
              variant === 'pill' && [
                'h-9 rounded-lg px-3.5',
                selected ? 'bg-one-card text-one-fg shadow-soft' : 'text-one-muted hover:text-one-fg',
              ],
            )}
          >
            {Icon && <Icon size={16} aria-hidden />}
            {item.label}
            {item.count !== undefined && (
              <span
                className={cn(
                  'rounded-full px-1.5 py-px text-[11px] font-semibold tabular',
                  selected ? 'bg-one-royal/10 text-one-royal' : 'bg-one-sunken text-one-subtle',
                )}
              >
                {item.count}
              </span>
            )}
          </button>
        )
      })}
    </div>
  )
}

export function TabPanel({ id, value, children, className }: { id: string; value: string; children: ReactNode; className?: string }) {
  return (
    <div
      role="tabpanel"
      id={`${id}-panel-${value}`}
      aria-labelledby={`${id}-tab-${value}`}
      tabIndex={0}
      className={cn('animate-rise-in focus-visible:outline-offset-4', className)}
    >
      {children}
    </div>
  )
}

interface SegmentedProps<V extends string> {
  items: Array<{ value: V; label: ReactNode; icon?: LucideIcon }>
  value: V
  onChange: (v: V) => void
  label: string
  size?: 'sm' | 'md'
  className?: string
}

/** Single-choice toggle group (radio semantics) — Daily/Weekly/Monthly, theme, etc. */
export function Segmented<V extends string>({ items, value, onChange, label, size = 'md', className }: SegmentedProps<V>) {
  const refs = useRef<Array<HTMLButtonElement | null>>([])
  return (
    <div role="radiogroup" aria-label={label} className={cn('inline-flex rounded-xl bg-one-sunken p-1', className)}>
      {items.map((item, i) => {
        const selected = item.value === value
        const Icon = item.icon
        return (
          <button
            key={item.value}
            ref={(el) => {
              refs.current[i] = el
            }}
            type="button"
            role="radio"
            aria-checked={selected}
            tabIndex={selected ? 0 : -1}
            onClick={() => onChange(item.value)}
            onKeyDown={(e) => {
              const dir = e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : e.key === 'ArrowLeft' || e.key === 'ArrowUp' ? -1 : 0
              if (!dir) return
              e.preventDefault()
              const next = (i + dir + items.length) % items.length
              onChange(items[next].value)
              refs.current[next]?.focus()
            }}
            className={cn(
              'inline-flex flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg font-medium transition-colors',
              size === 'sm' ? 'h-7 px-2.5 text-[12.5px]' : 'h-8 px-3 text-[13px]',
              selected ? 'bg-one-card text-one-fg shadow-soft' : 'text-one-muted hover:text-one-fg',
            )}
          >
            {Icon && <Icon size={14} aria-hidden />}
            {item.label}
          </button>
        )
      })}
    </div>
  )
}
