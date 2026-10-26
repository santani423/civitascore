import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import type { NavItem } from '../../data/navigation'
import { SectionLink } from './SectionLink'

interface NavItemLinkProps {
  item: NavItem
  children: ReactNode
  className?: string
  onNavigate?: () => void
  'aria-current'?: 'page' | 'location' | undefined
}

/** A navbar/footer item: a home-page section (smooth scroll) or a page of its own (route). */
export function NavItemLink({ item, children, className, onNavigate, 'aria-current': current }: NavItemLinkProps) {
  if ('path' in item) {
    return (
      <Link to={item.path} onClick={onNavigate} className={className} aria-current={current}>
        {children}
      </Link>
    )
  }
  return (
    <SectionLink section={item.target} onNavigate={onNavigate} className={className} aria-current={current === 'location' ? current : undefined}>
      {children}
    </SectionLink>
  )
}
