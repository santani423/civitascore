import { useRef } from 'react'
import { cn } from '@/utils/cn'
import { useActiveSection, useKeepActiveInView } from '../../hooks/useScroll'
import { SectionLink } from './SectionLink'

interface StickySectionNavProps {
  label: string
  /** The page the sections are on. */
  path: string
  items: Array<{ id: string; label: string }>
  /** Sections before/after the listed ones, watched so no item is marked while they're in view. */
  watchAlso?: string[]
}

/**
 * Index of a long page's sections that sticks under the navbar and marks
 * the section in view. Scrolls sideways when it doesn't fit (phones), keeping
 * the marked item visible. Render it in the same parent as the sections so it
 * stops sticking after the last one.
 */
export function StickySectionNav({ label, path, items, watchAlso = [] }: StickySectionNavProps) {
  const active = useActiveSection([...watchAlso, ...items.map((item) => item.id)])
  const listRef = useRef<HTMLOListElement>(null)
  useKeepActiveInView(listRef, active)

  return (
    <nav aria-label={label} className="sticky top-[var(--lp-nav-h)] z-30 border-b border-lp-line bg-lp-surface/95 shadow-lp-sm backdrop-blur-xl backdrop-saturate-150">
      <div className="lp-container">
        <ol ref={listRef} className="lp-no-scrollbar relative -mx-[var(--lp-gutter)] flex overflow-x-auto px-[calc(var(--lp-gutter)-0.75rem)] xl:mx-0 xl:justify-between xl:px-0">
          {items.map((item) => {
            const isActive = item.id === active
            return (
              <li key={item.id} className="flex shrink-0">
                <SectionLink
                  section={item.id}
                  path={path}
                  aria-current={isActive ? 'location' : undefined}
                  className={cn(
                    'relative flex min-h-14 items-center whitespace-nowrap px-3 text-[0.875rem] font-semibold transition-colors',
                    isActive ? 'text-lp-brand-text' : 'text-lp-muted hover:text-lp-fg',
                  )}
                >
                  {item.label}
                  <span
                    aria-hidden
                    className={cn(
                      'absolute inset-x-3 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300',
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
