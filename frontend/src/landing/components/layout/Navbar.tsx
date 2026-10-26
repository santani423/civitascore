import { useEffect, useRef, useState, type RefObject } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { Accessibility, ChevronRight, Menu, MessageSquare, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { useAppEntry, type AppEntry } from '../../hooks/useAppEntry'
import { useActiveSection, useScrolled } from '../../hooks/useScroll'
import { useLandingUi } from '../../store/ui'
import { useA11y } from '../../store/a11y'
import { LANDING_PATHS, SECTION_IDS, normalizePath } from '../../paths'
import { NAV_ITEMS, SPY_SECTIONS, navCurrent } from '../../data/navigation'
import { Button, IconButton } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Drawer } from '../ui/Overlay'
import { LanguageMenu, LanguageSegmented } from './LanguageSwitcher'
import { ThemeSegmented, ThemeToggle } from './ThemeToggle'
import { SectionLink } from './SectionLink'
import { NavItemLink } from './NavItemLink'
import { Wordmark } from './Wordmark'
import { AccessibilityButton } from '../a11y/AccessibilityPanel'

/**
 * Publishes the header's real height as `--lp-nav-h` on `.lp-root` (it grows
 * with the desktop nav and with enlarged text), for content that sticks below it.
 */
function useNavHeightVar(ref: RefObject<HTMLElement | null>) {
  useEffect(() => {
    const header = ref.current
    const root = header?.closest<HTMLElement>('.lp-root')
    if (!header || !root || typeof ResizeObserver === 'undefined') return
    const ro = new ResizeObserver(() => root.style.setProperty('--lp-nav-h', `${header.offsetHeight}px`))
    ro.observe(header)
    return () => {
      ro.disconnect()
      root.style.removeProperty('--lp-nav-h')
    }
  }, [ref])
}

export function Navbar() {
  const { m } = useLandingI18n()
  const path = normalizePath(useLocation().pathname)
  const onHome = path === LANDING_PATHS.home
  const scrolled = useScrolled()
  const active = useActiveSection(onHome ? SPY_SECTIONS : [])
  const [menuOpen, setMenuOpen] = useState(false)
  const entry = useAppEntry()
  const EntryIcon = entry.icon
  const headerRef = useRef<HTMLElement>(null)
  useNavHeightVar(headerRef)

  return (
    <header
      ref={headerRef}
      className={cn(
        'fixed inset-x-0 top-0 z-50 border-b bg-lp-surface/95 backdrop-blur-xl backdrop-saturate-150 transition-[border-color,box-shadow] duration-300',
        scrolled ? 'border-lp-line shadow-lp-md' : 'border-lp-line/70',
      )}
    >
      <div className="lp-container flex min-h-16 items-center gap-3 py-1 lg:min-h-[4.5rem]">
        <SectionLink section={SECTION_IDS.home} className="-ml-1 min-w-0 rounded-lp-sm p-1" aria-current={undefined}>
          <span className="sr-only">{m.nav.homeLink}</span>
          <span aria-hidden className="flex min-w-0">
            <Wordmark />
          </span>
        </SectionLink>

        <nav aria-label={m.nav.primary} className="lp-nav-desktop ml-auto h-full">
          <ul className="flex h-full items-stretch gap-1">
            {NAV_ITEMS.map((item) => {
              const current = navCurrent(item, path, active)
              const isActive = current !== undefined
              return (
                <li key={item.key} className="flex">
                  <NavItemLink
                    item={item}
                    aria-current={current}
                    className={cn(
                      'relative inline-flex min-h-16 items-center whitespace-nowrap px-3 text-[0.9063rem] font-semibold transition-colors lg:min-h-[4.5rem]',
                      isActive ? 'text-lp-brand-text' : 'text-lp-muted hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <span
                      aria-hidden
                      className={cn(
                        'absolute inset-x-3 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300',
                        isActive ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0',
                      )}
                    />
                  </NavItemLink>
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="ml-auto flex shrink-0 items-center gap-1 lg:ml-4">
          <LanguageMenu className="lp-hdr-extra hidden sm:block" />
          <ThemeToggle className="lp-hdr-extra hidden sm:inline-flex" />
          <AccessibilityButton />
          <Link to={entry.to} className={buttonClass('primary', 'sm', 'lp-hdr-extra ml-1 hidden px-5 sm:inline-flex')}>
            <EntryIcon size={16} aria-hidden />
            {entry.label}
          </Link>
          <IconButton
            className="lp-nav-burger"
            aria-label={m.nav.openMenu}
            aria-expanded={menuOpen}
            aria-controls="lp-mobile-menu"
            onClick={() => setMenuOpen(true)}
          >
            <Menu size={22} aria-hidden />
          </IconButton>
        </div>
      </div>

      <MobileMenu open={menuOpen} onClose={() => setMenuOpen(false)} path={path} active={active} entry={entry} />
    </header>
  )
}

function MobileMenu({
  open,
  onClose,
  path,
  active,
  entry,
}: {
  open: boolean
  onClose: () => void
  path: string
  active: string | null
  entry: AppEntry
}) {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
  const openA11y = useA11y((s) => s.openPanel)
  const EntryIcon = entry.icon

  return (
    <Drawer open={open} onClose={onClose} label={m.nav.mobile} id="lp-mobile-menu">
      <div className="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-lp-line px-4">
        <span aria-hidden className="flex min-w-0">
          <Wordmark />
        </span>
        <IconButton onClick={onClose} aria-label={m.nav.closeMenu} className="-mr-1">
          <X size={20} aria-hidden />
        </IconButton>
      </div>
      <div className="flex-1 overflow-y-auto px-3 py-4">
        <nav aria-label={m.nav.mobile}>
          <ul className="space-y-0.5">
            {NAV_ITEMS.map((item) => {
              const current = navCurrent(item, path, active)
              const isActive = current !== undefined
              return (
                <li key={item.key}>
                  <NavItemLink
                    item={item}
                    onNavigate={onClose}
                    aria-current={current}
                    className={cn(
                      'flex h-12 items-center justify-between rounded-lp-sm border-l-[3px] px-3 text-[1rem] font-semibold transition-colors',
                      isActive
                        ? 'border-lp-brand bg-lp-brand-soft text-lp-brand-text'
                        : 'border-transparent text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <ChevronRight size={17} aria-hidden className="text-lp-subtle" />
                  </NavItemLink>
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="mt-6 space-y-5 border-t border-lp-line px-2 pt-6">
          <div>
            <p className="mb-2 text-[0.75rem] font-semibold uppercase tracking-wider text-lp-subtle">{m.nav.language}</p>
            <LanguageSegmented className="flex w-full" />
          </div>
          <div>
            <p className="mb-2 text-[0.75rem] font-semibold uppercase tracking-wider text-lp-subtle">{m.nav.theme}</p>
            <ThemeSegmented className="flex w-full" />
          </div>
        </div>
      </div>
      <div className="shrink-0 space-y-2 border-t border-lp-line p-4">
        <Link to={entry.to} onClick={onClose} className={buttonClass('primary', 'md', 'w-full')}>
          <EntryIcon size={17} aria-hidden />
          {entry.label}
        </Link>
        <Button
          variant="secondary"
          className="w-full"
          onClick={() => {
            onClose()
            openA11y()
          }}
        >
          <Accessibility size={16} aria-hidden />
          {m.a11y.open}
        </Button>
        <Button
          variant="secondary"
          className="w-full"
          onClick={() => {
            onClose()
            openContact()
          }}
        >
          <MessageSquare size={16} aria-hidden />
          {m.nav.contact}
        </Button>
      </div>
    </Drawer>
  )
}
