import type { MouseEvent, ReactNode } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { LANDING_PATHS } from '../../paths'
import { scrollToSection } from '../../hooks/useScroll'

interface SectionLinkProps {
  section: string
  children: ReactNode
  className?: string
  onNavigate?: () => void
  'aria-current'?: 'location' | undefined
}

/**
 * Link to an in-page section. On the home page it smooth-scrolls; from the
 * legal pages it routes to `/#section` and the home page scrolls on arrival.
 */
export function SectionLink({ section, children, className, onNavigate, ...aria }: SectionLinkProps) {
  const { pathname } = useLocation()
  const onHome = pathname === LANDING_PATHS.home

  const onClick = (e: MouseEvent<HTMLAnchorElement>) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return
    if (!onHome) {
      onNavigate?.()
      return
    }
    if (!document.getElementById(section)) return
    e.preventDefault()
    if (onNavigate) {
      // Let a closing drawer restore focus first, then move focus to the section.
      onNavigate()
      window.setTimeout(() => scrollToSection(section), 60)
    } else {
      scrollToSection(section)
    }
  }

  return (
    <Link to={`${LANDING_PATHS.home}#${section}`} onClick={onClick} className={className} {...aria}>
      {children}
    </Link>
  )
}
