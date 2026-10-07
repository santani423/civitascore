import type { HTMLAttributes, ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { cn } from '@/utils/cn'

interface CardProps extends HTMLAttributes<HTMLDivElement> {
  interactive?: boolean
  padded?: boolean
  as?: 'div' | 'section' | 'article' | 'li'
}

export function Card({ interactive, padded = false, as = 'div', className, ...rest }: CardProps) {
  // All variants share div-compatible props; the tag only changes semantics.
  const Tag = as as 'div'
  return (
    <Tag
      className={cn(
        'min-w-0 rounded-2xl border border-one-line bg-one-card shadow-soft',
        interactive && 'transition-[box-shadow,border-color,transform] duration-200 hover:-translate-y-0.5 hover:border-one-line2 hover:shadow-lift',
        padded && 'p-5 sm:p-6',
        className,
      )}
      {...rest}
    />
  )
}

interface CardHeaderProps {
  title: ReactNode
  description?: ReactNode
  action?: ReactNode
  /** Shortcut for a "View all →" link on the right. */
  href?: string
  hrefLabel?: string
  icon?: ReactNode
  className?: string
  id?: string
}

export function CardHeader({ title, description, action, href, hrefLabel, icon, className, id }: CardHeaderProps) {
  return (
    <div className={cn('flex items-start justify-between gap-4 px-5 pt-5 sm:px-6 sm:pt-6', className)}>
      <div className="flex min-w-0 items-start gap-3">
        {icon}
        <div className="min-w-0">
          <h2 id={id} className="t-h3 text-one-fg">
            {title}
          </h2>
          {description && <p className="t-small mt-0.5 text-one-subtle">{description}</p>}
        </div>
      </div>
      {action}
      {href && hrefLabel && (
        <Link
          to={href}
          className="group inline-flex shrink-0 items-center gap-1 rounded-lg text-[13px] font-medium text-one-royal hover:underline"
        >
          {hrefLabel}
          <ArrowRight size={14} className="transition-transform group-hover:translate-x-0.5" aria-hidden />
        </Link>
      )}
    </div>
  )
}

export function CardBody({ className, ...rest }: HTMLAttributes<HTMLDivElement>) {
  return <div className={cn('px-5 pb-5 pt-4 sm:px-6 sm:pb-6', className)} {...rest} />
}
