import { useEffect, useId, useRef, useState, type KeyboardEvent, type ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { Check } from 'lucide-react'
import { cn } from '@/utils/cn'

interface DropdownProps {
  /** Render the trigger; spread `props` onto a <button>. */
  trigger: (props: {
    ref: (el: HTMLButtonElement | null) => void
    onClick: () => void
    onKeyDown: (e: KeyboardEvent) => void
    'aria-haspopup': 'menu' | 'dialog'
    'aria-expanded': boolean
    'aria-controls': string
  }) => ReactNode
  children: (close: () => void) => ReactNode
  align?: 'start' | 'end'
  width?: string
  /** 'menu' gets arrow-key roving focus; 'dialog' is a free-form popover (e.g. notifications). */
  kind?: 'menu' | 'dialog'
  label?: string
}

export function Dropdown({ trigger, children, align = 'end', width = 'w-56', kind = 'menu', label }: DropdownProps) {
  const [open, setOpen] = useState(false)
  const rootRef = useRef<HTMLDivElement>(null)
  const panelRef = useRef<HTMLDivElement>(null)
  const triggerRef = useRef<HTMLButtonElement | null>(null)
  const id = useId()

  const close = (restoreFocus = true) => {
    setOpen(false)
    if (restoreFocus) triggerRef.current?.focus()
  }

  useEffect(() => {
    if (!open) return
    const items = panelRef.current?.querySelectorAll<HTMLElement>('[role="menuitem"],[role="menuitemradio"]')
    if (kind === 'menu') items?.[0]?.focus()
    const onDown = (e: MouseEvent) => {
      if (!rootRef.current?.contains(e.target as Node)) setOpen(false)
    }
    const onKey = (e: globalThis.KeyboardEvent) => {
      if (e.key === 'Escape') close()
    }
    document.addEventListener('mousedown', onDown)
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('mousedown', onDown)
      document.removeEventListener('keydown', onKey)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, kind])

  const onMenuKey = (e: KeyboardEvent) => {
    if (kind !== 'menu') return
    const items = Array.from(panelRef.current?.querySelectorAll<HTMLElement>('[role="menuitem"],[role="menuitemradio"]') ?? [])
    const index = items.indexOf(document.activeElement as HTMLElement)
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault()
      const dir = e.key === 'ArrowDown' ? 1 : -1
      items[(index + dir + items.length) % items.length]?.focus()
    } else if (e.key === 'Home') {
      e.preventDefault()
      items[0]?.focus()
    } else if (e.key === 'End') {
      e.preventDefault()
      items[items.length - 1]?.focus()
    } else if (e.key === 'Tab') {
      setOpen(false)
    }
  }

  return (
    <div ref={rootRef} className="relative">
      {trigger({
        ref: (el) => {
          triggerRef.current = el
        },
        onClick: () => setOpen((o) => !o),
        onKeyDown: (e) => {
          if (e.key === 'ArrowDown' && kind === 'menu') {
            e.preventDefault()
            setOpen(true)
          }
        },
        'aria-haspopup': kind,
        'aria-expanded': open,
        'aria-controls': id,
      })}
      {open && (
        <div
          ref={panelRef}
          id={id}
          role={kind}
          aria-label={label}
          onKeyDown={onMenuKey}
          className={cn(
            'absolute top-full z-50 mt-2 origin-top animate-slide-in rounded-2xl border border-one-line bg-one-elev p-1.5 shadow-overlay',
            align === 'end' ? 'right-0' : 'left-0',
            width,
          )}
        >
          {children(() => close())}
        </div>
      )}
    </div>
  )
}

interface ItemProps {
  icon?: LucideIcon
  children: ReactNode
  onSelect: () => void
  checked?: boolean
  hint?: ReactNode
  danger?: boolean
}

export function DropdownItem({ icon: Icon, children, onSelect, checked, hint, danger }: ItemProps) {
  return (
    <button
      type="button"
      role={checked === undefined ? 'menuitem' : 'menuitemradio'}
      aria-checked={checked}
      tabIndex={-1}
      onClick={onSelect}
      className={cn(
        'flex w-full items-center gap-2.5 rounded-xl px-2.5 py-2 text-left text-[14px] outline-none transition-colors focus:bg-one-sunken hover:bg-one-sunken',
        danger ? 'text-one-bad' : 'text-one-fg',
      )}
    >
      {Icon && <Icon size={16} aria-hidden className={danger ? '' : 'text-one-subtle'} />}
      <span className="flex-1">{children}</span>
      {hint && <span className="text-[12px] text-one-subtle">{hint}</span>}
      {checked && <Check size={15} aria-hidden className="text-one-royal" />}
    </button>
  )
}

export function DropdownLabel({ children }: { children: ReactNode }) {
  return <div className="t-caption px-2.5 pb-1 pt-2 text-one-subtle">{children}</div>
}

export function DropdownSeparator() {
  return <div role="separator" className="my-1.5 h-px bg-one-line" />
}
