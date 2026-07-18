import type { ReactNode } from 'react'
import { Inbox, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

export interface EmptyStateProps {
  icon?: LucideIcon
  title: string
  description?: string
  action?: ReactNode
  className?: string
}

export function EmptyState({ icon: Icon = Inbox, title, description, action, className }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center gap-2 px-6 py-12 text-center', className)}>
      <div className="mb-1 flex size-12 items-center justify-center rounded-full bg-surface-hover text-ink-tertiary">
        <Icon className="size-6" aria-hidden />
      </div>
      <p className="text-sm font-medium text-ink-primary">{title}</p>
      {description && <p className="max-w-sm text-sm text-ink-secondary">{description}</p>}
      {action && <div className="mt-3">{action}</div>}
    </div>
  )
}
