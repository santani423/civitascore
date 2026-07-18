import { Fragment } from 'react'
import { Link } from 'react-router-dom'
import { ChevronRight } from 'lucide-react'

export interface BreadcrumbItem {
  label: string
  path?: string
}

export function Breadcrumb({ items }: { items: BreadcrumbItem[] }) {
  return (
    <nav aria-label="Breadcrumb" className="flex items-center gap-1.5 text-sm text-ink-secondary">
      {items.map((item, index) => (
        <Fragment key={`${item.label}-${index}`}>
          {index > 0 && <ChevronRight className="size-3.5 text-ink-tertiary" aria-hidden />}
          {item.path ? (
            <Link to={item.path} className="transition-colors hover:text-ink-primary">
              {item.label}
            </Link>
          ) : (
            <span className="text-ink-primary">{item.label}</span>
          )}
        </Fragment>
      ))}
    </nav>
  )
}
