import type { MouseEvent, ReactNode } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { LANDING_PATHS, normalizePath, type LandingPath } from '../../paths'
import { scrollToSection } from '../../hooks/useScroll'

interface SectionLinkProps {
  section: string
  /** The page the section is on (default: home). */
  path?: LandingPath
  children: ReactNode
  className?: string
  onNavigate?: () => void
  'aria-current'?: 'location' | undefined
}

/**
 * Link to a section of a landing page. On that page it smooth-scrolls; from
 * another page it routes to `path#section` and the page scrolls on arrival.
 */
export function SectionLink({ section, path = LANDING_PATHS.home, children, className, onNavigate, ...aria }: SectionLinkProps) {
  const { pathname } = useLocation()
  const onPage = normalizePath(pathname) === path

  const onClick = (e: MouseEvent<HTMLAnchorElement>) => {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return
    if (!onPage) {
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
    <Link to={`${path}#${section}`} onClick={onClick} className={className} {...aria}>
      {children}
    </Link>
  )
}
