import { useEffect, useRef } from 'react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { PILLARS, pillarNumber } from '../../data/pillars'
import { prefersReducedMotion, useActiveSection } from '../../hooks/useScroll'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { SectionLink } from '../layout/SectionLink'

// The hero and the activities section are watched too, so no pillar is marked while either is in view.
const SPY_IDS = [STUDENT_LIFE_IDS.hero, ...PILLARS.map((p) => p.id), STUDENT_LIFE_IDS.activities]

/**
 * Pillar index that sticks under the navbar while the pillar sections scroll
 * by, marking the pillar in view. On narrow screens it scrolls sideways and
 * keeps the marked pillar visible. Render it in the same parent as the
 * pillar sections so it stops sticking after the last one.
 */
export function PillarNav() {
  const { m } = useLandingI18n()
  const active = useActiveSection(SPY_IDS)
  const listRef = useRef<HTMLOListElement>(null)

  useEffect(() => {
    const list = listRef.current
    const item = list?.querySelector<HTMLElement>('[aria-current]')
    if (!list || !item || list.scrollWidth <= list.clientWidth) return
    list.scrollTo({ left: item.offsetLeft - (list.clientWidth - item.offsetWidth) / 2, behavior: prefersReducedMotion() ? 'auto' : 'smooth' })
  }, [active])

  return (
    <nav
      aria-label={m.studentLife.rail}
      className="sticky top-[var(--lp-nav-h)] z-30 border-b border-lp-line bg-lp-surface/95 shadow-lp-sm backdrop-blur-xl backdrop-saturate-150"
    >
      <div className="lp-container">
        <ol
          ref={listRef}
          className="lp-no-scrollbar relative -mx-[var(--lp-gutter)] flex overflow-x-auto px-[calc(var(--lp-gutter)-0.75rem)] lg:mx-0 lg:grid lg:grid-cols-5 lg:overflow-visible lg:px-0"
        >
          {PILLARS.map((pillar) => {
            const isActive = pillar.id === active
            // One line on narrow screens (the list scrolls); on wide screens every name takes two lines, so items line up.
            const split = pillar.title.lastIndexOf(' ')
            return (
              <li key={pillar.id} className="flex shrink-0">
                <SectionLink
                  section={pillar.id}
                  path={LANDING_PATHS.studentLife}
                  aria-current={isActive ? 'location' : undefined}
                  className={cn(
                    'relative flex min-h-14 w-full items-center gap-2.5 px-3 py-2 text-[0.875rem] font-semibold leading-tight transition-colors lg:px-4',
                    isActive ? 'text-lp-brand-text' : 'text-lp-muted hover:text-lp-fg',
                  )}
                >
                  <span className={cn('lp-tabular text-[0.75rem] font-bold', isActive ? 'text-lp-brand-text' : 'text-lp-subtle')}>{pillarNumber(pillar)}</span>
                  <span lang="id" className="whitespace-nowrap">
                    <span className="lg:block">{pillar.title.slice(0, split)}</span> <span className="lg:block">{pillar.title.slice(split + 1)}</span>
                  </span>
                  <span
                    aria-hidden
                    className={cn(
                      'absolute inset-x-3 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300 lg:inset-x-4',
                      isActive ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0',
                    )}
                  />
                </SectionLink>
              </li>
            )
          })}
        </ol>
      </div>
    </nav>
  )
}
