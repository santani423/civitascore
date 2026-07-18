import type { ReactNode } from 'react'
import { Breadcrumb, type BreadcrumbItem } from '@/components/ui/Breadcrumb'

export interface PageHeaderProps {
  title: string
  description?: string
  breadcrumb?: BreadcrumbItem[]
  actions?: ReactNode
}

export function PageHeader({ title, description, breadcrumb, actions }: PageHeaderProps) {
  return (
    <div className="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
      <div>
        {breadcrumb && <Breadcrumb items={breadcrumb} />}
        <h1 className="mt-1 text-xl font-semibold tracking-tight text-ink-primary sm:text-2xl">{title}</h1>
        {description && <p className="mt-1 text-sm text-ink-secondary">{description}</p>}
      </div>
      {actions && <div className="flex shrink-0 items-center gap-2">{actions}</div>}
    </div>
  )
}
