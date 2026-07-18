import { useState, type ReactNode } from 'react'
import { cn } from '@/utils/cn'

export interface TooltipProps {
  label: string
  children: ReactNode
  side?: 'top' | 'right' | 'bottom' | 'left'
  className?: string
}

const SIDE_CLASSES: Record<NonNullable<TooltipProps['side']>, string> = {
  top: 'bottom-full left-1/2 mb-2 -translate-x-1/2',
  right: 'left-full top-1/2 ml-2 -translate-y-1/2',
  bottom: 'top-full left-1/2 mt-2 -translate-x-1/2',
  left: 'right-full top-1/2 mr-2 -translate-y-1/2',
}

export function Tooltip({ label, children, side = 'right', className }: TooltipProps) {
  const [visible, setVisible] = useState(false)

  return (
    <span
      className="relative inline-flex"
      onMouseEnter={() => setVisible(true)}
      onMouseLeave={() => setVisible(false)}
      onFocus={() => setVisible(true)}
      onBlur={() => setVisible(false)}
    >
      {children}
      {visible && (
        <span
          role="tooltip"
          className={cn(
            'absolute z-40 animate-fade-in whitespace-nowrap rounded-md bg-ink-primary px-2 py-1 text-xs font-medium text-background',
            SIDE_CLASSES[side],
            className,
          )}
        >
          {label}
        </span>
      )}
    </span>
  )
}
