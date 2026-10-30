import { useRef } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { GraduationCap } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { ALUMNI_PAGES, pageCurrent } from '../../data/navigation'
import { LANDING_PATHS, normalizePath } from '../../paths'
import { useKeepActiveInView } from '../../hooks/useScroll'
import { NAV_PAGE_ICONS } from '../layout/navStyles'

/**
 * The section's own navigation, directly below the navbar and in the same place
 * on all six pages, so moving between them never shifts it. Scrolls sideways
 * on phones, keeping the current page in view.
 */
export function AlumniNav() {
  const { m } = useLandingI18n()
  const path = normalizePath(useLocation().pathname)
  const listRef = useRef<HTMLUListElement>(null)
  useKeepActiveInView(listRef, path)

  return (
    <nav aria-label={m.alumni.navLabel} className="lp-on-band sticky top-[var(--lp-nav-h)] z-30 border-b border-lp-band-line bg-lp-band">
      <div className="lp-container flex items-center gap-8">
        <Link to={LANDING_PATHS.alumni} className="hidden shrink-0 items-center gap-2 py-3 font-display text-[0.9375rem] font-extrabold text-lp-band-fg lg:inline-flex">
          <GraduationCap size={19} aria-hidden className="text-lp-accent" />
          {m.nav.alumni}
        </Link>
        <ul ref={listRef} className="lp-no-scrollbar lp-scroll-fade relative -mx-[var(--lp-gutter)] flex flex-1 overflow-x-auto px-[calc(var(--lp-gutter)-0.5rem)] lg:mx-0 lg:justify-end lg:px-0">
          {ALUMNI_PAGES.map((page) => {
            const current = pageCurrent(page, path)
            const Icon = NAV_PAGE_ICONS[page.key]
            return (
              <li key={page.key} className="flex shrink-0">
                <Link
                  to={page.path}
                  aria-current={current}
                  className={cn(
                    'relative inline-flex min-h-[3.25rem] items-center gap-2 whitespace-nowrap px-3 text-[0.875rem] font-semibold transition-colors',
                    current ? 'text-lp-band-fg' : 'text-lp-band-muted hover:text-lp-band-fg',
                  )}
                >
                  <Icon size={16} aria-hidden className={cn('shrink-0', current ? 'text-lp-accent' : 'opacity-70')} />
                  {m.nav[page.key]}
                  <span
                    aria-hidden
                    className={cn('absolute inset-x-3 -bottom-px h-[3px] rounded-t-full bg-lp-accent transition-[opacity,transform] duration-300', current ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0')}
                  />
                </Link>
              </li>
            )
          })}
        </ul>
      </div>
    </nav>
  )
}
