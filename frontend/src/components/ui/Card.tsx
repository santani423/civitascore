import type { HTMLAttributes, ReactNode } from 'react'
import { cn } from '@/utils/cn'

export interface CardProps extends Omit<HTMLAttributes<HTMLDivElement>, 'title'> {
  title?: ReactNode
  description?: ReactNode
  actions?: ReactNode
  noPadding?: boolean
}

export function Card({ className, title, description, actions, noPadding = false, children, ...props }: CardProps) {
  const hasHeader = title || description || actions

  return (
    <div
      className={cn('rounded-xl border border-border bg-surface shadow-card', className)}
      {...props}
    >
      {hasHeader && (
        <div className="flex items-start justify-between gap-3 border-b border-border px-5 py-4">
          <div>
            {title && <h3 className="text-sm font-semibold text-ink-primary">{title}</h3>}
            {description && <p className="mt-0.5 text-xs text-ink-secondary">{description}</p>}
          </div>
          {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
        </div>
      )}
      <div className={noPadding ? undefined : 'p-5'}>{children}</div>
    </div>
  )
}
