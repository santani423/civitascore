import { useRef, useState, type ButtonHTMLAttributes, type ReactNode } from 'react'
import { useOutsideClick } from '@/hooks/useOutsideClick'
import { cn } from '@/utils/cn'

export interface DropdownProps {
  trigger: ReactNode
  children: ReactNode
  align?: 'left' | 'right'
  className?: string
}

export function Dropdown({ trigger, children, align = 'right', className }: DropdownProps) {
  const [open, setOpen] = useState(false)
  const containerRef = useRef<HTMLDivElement>(null)

  useOutsideClick(containerRef, () => setOpen(false), open)

  return (
    <div ref={containerRef} className="relative">
      <button type="button" onClick={() => setOpen((current) => !current)} className="flex items-center">
        {trigger}
      </button>

      {open && (
        <div
          role="menu"
          className={cn(
            'absolute z-30 mt-2 min-w-48 animate-slide-in rounded-xl border border-border bg-surface p-1.5 shadow-popover',
            align === 'right' ? 'right-0' : 'left-0',
            className,
          )}
          onClick={() => setOpen(false)}
        >
          {children}
        </div>
      )}
    </div>
  )
}

export function DropdownItem({
  children,
  className,
  ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      type="button"
      role="menuitem"
      className={cn(
        'flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm text-ink-primary transition-colors hover:bg-surface-hover',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary',
        className,
      )}
      {...props}
    >
      {children}
    </button>
  )
}
