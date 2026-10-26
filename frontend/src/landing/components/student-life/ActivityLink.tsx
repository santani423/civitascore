import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import type { ActivityTarget } from '../../lib/activities'

/** A link to the configured activity page: a route of this app keeps its auth guard, another site is a plain link. */
export function ActivityLink({ target, className, children }: { target: ActivityTarget; className?: string; children: ReactNode }) {
  return target.external ? (
    <a href={target.href} className={className}>
      {children}
    </a>
  ) : (
    <Link to={target.href} className={className}>
      {children}
    </Link>
  )
}
