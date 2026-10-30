import { useEffect, useId, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { ChevronDown } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { LANDING_PATHS } from '../../paths'
import { childCurrent, type GroupNavItem } from '../../data/navigation'
import { SectionLink } from './SectionLink'
import { NAV_PAGE_ICONS, navLinkClass } from './navStyles'

/** The orange bar under the active navbar item. */
export function NavUnderline({ active }: { active: boolean }) {
  return (
    <span
      aria-hidden
      className={cn(
        'absolute inset-x-2.5 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300',
        active ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0',
      )}
    />
  )
}

/**
 * Desktop dropdown for a group of pages (disclosure pattern: a button that
 * shows a list of links). Closes on Escape (focus returns to the button), an
 * outside click, focus leaving it, or navigation.
 */
export function NavGroupMenu({ item, path, active, activeSection }: { item: GroupNavItem; path: string; active: boolean; activeSection: string | null }) {
  const { m } = useLandingI18n()
  const panelId = useId()
  const wrapRef = useRef<HTMLDivElement>(null)
  const buttonRef = useRef<HTMLButtonElement>(null)
  // Tied to the path it was opened on, so any navigation closes it.
  const [openOn, setOpenOn] = useState<string | null>(null)
  const open = openOn === path

  useEffect(() => {
    if (!open) return
    const onPointerDown = (e: PointerEvent) => {
      if (!wrapRef.current?.contains(e.target as Node)) setOpenOn(null)
    }
    const onKeyDown = (e: KeyboardEvent) => {
      if (e.key !== 'Escape') return
      setOpenOn(null)
      buttonRef.current?.focus()
    }
    document.addEventListener('pointerdown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('pointerdown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open])

  return (
    <div
      ref={wrapRef}
      className="relative flex"
      onBlur={(e) => {
        if (open && !wrapRef.current?.contains(e.relatedTarget as Node | null)) setOpenOn(null)
      }}
    >
      <button
        ref={buttonRef}
        type="button"
        aria-expanded={open}
        aria-controls={panelId}
        onClick={() => setOpenOn(open ? null : path)}
        className={navLinkClass(active || open)}
      >
        {m.nav[item.key]}
        <ChevronDown size={14} aria-hidden className={cn('transition-transform duration-200', open && 'rotate-180')} />
        <NavUnderline active={active} />
      </button>

      <div id={panelId} hidden={!open} className="absolute left-1/2 top-full z-10 w-[23rem] -translate-x-1/2 pt-1">
        <ul className="grid gap-0.5 rounded-lp-lg border border-lp-line bg-lp-surface p-2 shadow-lp-lg">
          {item.items.map((child) => {
            const current = childCurrent(child, path, activeSection)
            const Icon = NAV_PAGE_ICONS[child.key]
            const className = cn('group flex items-start gap-3 rounded-lp-sm p-3 transition-colors', current ? 'bg-lp-brand-soft' : 'hover:bg-lp-sunken')
            const body = (
              <>
                <span aria-hidden className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lp-sm bg-lp-brand-soft text-lp-brand-text">
                  <Icon size={18} />
                </span>
                <span className="min-w-0">
                  <span className={cn('block text-[0.9063rem] font-semibold', current ? 'text-lp-brand-text' : 'text-lp-fg')}>{m.nav[child.key]}</span>
                  <span className="mt-0.5 block text-[0.8125rem] leading-snug text-lp-muted">{m.nav.pageHints[child.key]}</span>
                </span>
              </>
            )
            return (
              <li key={child.key}>
                {'path' in child ? (
                  <Link to={child.path} aria-current={current} onClick={() => setOpenOn(null)} className={className}>
                    {body}
                  </Link>
                ) : (
                  <SectionLink
                    section={child.target}
                    path={LANDING_PATHS.home}
                    aria-current={current === 'location' ? current : undefined}
                    onNavigate={() => setOpenOn(null)}
                    className={className}
                  >
                    {body}
                  </SectionLink>
                )}
              </li>
            )
          })}
        </ul>
      </div>
    </div>
  )
}
