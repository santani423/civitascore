import { useRef, type KeyboardEvent, type ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

export interface TabItem<K extends string> {
  key: K
  label: string
  icon?: LucideIcon
}

interface TabListProps<K extends string> {
  items: TabItem<K>[]
  value: K
  onChange: (key: K) => void
  label: string
  /** Prefix for tab/panel ids: tab = `${idPrefix}-tab-${key}`, panel = `${idPrefix}-panel-${key}`. */
  idPrefix: string
  variant?: 'pill' | 'underline'
  className?: string
}

const tabId = (prefix: string, key: string) => `${prefix}-tab-${key}`
const panelId = (prefix: string, key: string) => `${prefix}-panel-${key}`

/** WAI-ARIA tabs with automatic activation: ←/→ (and ↑/↓), Home, End. */
export function TabList<K extends string>({ items, value, onChange, label, idPrefix, variant = 'pill', className }: TabListProps<K>) {
  const refs = useRef<Array<HTMLButtonElement | null>>([])

  const onKeyDown = (e: KeyboardEvent<HTMLButtonElement>, index: number) => {
    let next = -1
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') next = (index + 1) % items.length
    else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') next = (index - 1 + items.length) % items.length
    else if (e.key === 'Home') next = 0
    else if (e.key === 'End') next = items.length - 1
    if (next < 0) return
    e.preventDefault()
    refs.current[next]?.focus()
    onChange(items[next].key)
  }

  return (
    <div
      role="tablist"
      aria-label={label}
      className={cn(
        'lp-no-scrollbar flex max-w-full overflow-x-auto',
        variant === 'pill' && 'gap-1 rounded-full border border-lp-line bg-lp-sunken p-1',
        variant === 'underline' && 'gap-6 border-b border-lp-line',
        className,
      )}
    >
      {items.map((item, i) => {
        const selected = item.key === value
        const Icon = item.icon
        return (
          <button
            key={item.key}
            ref={(el) => {
              refs.current[i] = el
            }}
            type="button"
            role="tab"
            id={tabId(idPrefix, item.key)}
            aria-selected={selected}
            aria-controls={panelId(idPrefix, item.key)}
            tabIndex={selected ? 0 : -1}
            onClick={() => onChange(item.key)}
            onKeyDown={(e) => onKeyDown(e, i)}
            className={cn(
              'inline-flex shrink-0 items-center gap-2 whitespace-nowrap font-medium transition-[background-color,color,box-shadow] duration-200',
              variant === 'pill' && 'h-10 rounded-full px-4 text-[14px]',
              variant === 'pill' &&
                (selected ? 'bg-lp-surface text-lp-fg shadow-lp-sm ring-1 ring-lp-line' : 'text-lp-muted hover:text-lp-fg'),
              variant === 'underline' && '-mb-px h-11 border-b-2 text-[14.5px]',
              variant === 'underline' &&
                (selected ? 'border-lp-brand text-lp-fg' : 'border-transparent text-lp-muted hover:text-lp-fg'),
            )}
          >
            {Icon && <Icon size={16} aria-hidden className={selected ? 'text-lp-brand-text' : undefined} />}
            {item.label}
          </button>
        )
      })}
    </div>
  )
}

export function TabPanel({ idPrefix, tabKey, children, className }: { idPrefix: string; tabKey: string; children: ReactNode; className?: string }) {
  return (
    <div role="tabpanel" id={panelId(idPrefix, tabKey)} aria-labelledby={tabId(idPrefix, tabKey)} tabIndex={0} className={cn('focus:outline-none focus-visible:outline-2', className)}>
      {children}
    </div>
  )
}
