import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { ChevronRight, Menu, MessageSquare, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { useAppEntry, type AppEntry } from '../../hooks/useAppEntry'
import { useActiveSection, useScrolled } from '../../hooks/useScroll'
import { useLandingUi } from '../../store/ui'
import { LANDING_PATHS, SECTION_IDS } from '../../paths'
import { NAV_ITEMS, SPY_SECTIONS } from '../../data/navigation'
import { Button, IconButton } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Drawer } from '../ui/Overlay'
import { LanguageMenu, LanguageSegmented } from './LanguageSwitcher'
import { ThemeSegmented, ThemeToggle } from './ThemeToggle'
import { SectionLink } from './SectionLink'
import { Wordmark } from './Wordmark'

export function Navbar() {
  const { m } = useLandingI18n()
  const { pathname } = useLocation()
  const onHome = pathname === LANDING_PATHS.home
  const scrolled = useScrolled()
  const active = useActiveSection(onHome ? SPY_SECTIONS : [])
  const [menuOpen, setMenuOpen] = useState(false)
  const entry = useAppEntry()
  const EntryIcon = entry.icon

  return (
    <header
      className={cn(
        'fixed inset-x-0 top-0 z-50 border-b bg-lp-surface/95 backdrop-blur-xl backdrop-saturate-150 transition-[border-color,box-shadow] duration-300',
        scrolled ? 'border-lp-line shadow-lp-md' : 'border-lp-line/70',
      )}
    >
      <div className="lp-container flex h-16 items-center gap-3 lg:h-[4.5rem]">
        <SectionLink section={SECTION_IDS.home} className="-ml-1 min-w-0 rounded-lp-sm p-1" aria-current={undefined}>
          <span className="sr-only">{m.nav.homeLink}</span>
          <span aria-hidden className="flex min-w-0">
            <Wordmark />
          </span>
        </SectionLink>

        <nav aria-label={m.nav.primary} className="ml-auto hidden h-full lg:block">
          <ul className="flex h-full items-stretch gap-1 xl:gap-2">
            {NAV_ITEMS.map((item) => {
              const isActive = onHome && item.covers.some((c) => c === active)
              return (
                <li key={item.key} className="flex">
                  <SectionLink
                    section={item.target}
                    aria-current={isActive ? 'location' : undefined}
                    className={cn(
                      'relative inline-flex items-center px-3 text-[14.5px] font-semibold transition-colors xl:px-4',
                      isActive ? 'text-lp-brand-text' : 'text-lp-muted hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <span
                      aria-hidden
                      className={cn(
                        'absolute inset-x-3 -bottom-px h-[3px] rounded-t-full bg-lp-brand transition-[opacity,transform] duration-300 xl:inset-x-4',
                        isActive ? 'scale-x-100 opacity-100' : 'scale-x-50 opacity-0',
                      )}
                    />
                  </SectionLink>
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="ml-auto flex shrink-0 items-center gap-1 lg:ml-4">
          <LanguageMenu className="hidden sm:block" />
          <ThemeToggle className="hidden sm:inline-flex" />
          <Link to={entry.to} className={buttonClass('primary', 'sm', 'ml-1 hidden h-10 px-5 sm:inline-flex')}>
            <EntryIcon size={16} aria-hidden />
            {entry.label}
          </Link>
          <IconButton
            className="lg:hidden"
            aria-label={m.nav.openMenu}
            aria-expanded={menuOpen}
            aria-controls="lp-mobile-menu"
            onClick={() => setMenuOpen(true)}
          >
            <Menu size={22} aria-hidden />
          </IconButton>
        </div>
      </div>

      <MobileMenu open={menuOpen} onClose={() => setMenuOpen(false)} active={onHome ? active : null} entry={entry} />
    </header>
  )
}

function MobileMenu({
  open,
  onClose,
  active,
  entry,
}: {
  open: boolean
  onClose: () => void
  active: string | null
  entry: AppEntry
}) {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
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
              const isActive = item.covers.some((c) => c === active)
              return (
                <li key={item.key}>
                  <SectionLink
                    section={item.target}
                    onNavigate={onClose}
                    aria-current={isActive ? 'location' : undefined}
                    className={cn(
                      'flex h-12 items-center justify-between rounded-lp-sm border-l-[3px] px-3 text-[16px] font-semibold transition-colors',
                      isActive
                        ? 'border-lp-brand bg-lp-brand-soft text-lp-brand-text'
                        : 'border-transparent text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <ChevronRight size={17} aria-hidden className="text-lp-subtle" />
                  </SectionLink>
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="mt-6 space-y-5 border-t border-lp-line px-2 pt-6">
          <div>
            <p className="mb-2 text-[12px] font-semibold uppercase tracking-wider text-lp-subtle">{m.nav.language}</p>
            <LanguageSegmented className="flex w-full" />
          </div>
          <div>
            <p className="mb-2 text-[12px] font-semibold uppercase tracking-wider text-lp-subtle">{m.nav.theme}</p>
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
