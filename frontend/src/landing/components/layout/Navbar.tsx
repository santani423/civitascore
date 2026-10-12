import { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { ArrowRight, LayoutDashboard, LogIn, Menu, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { ROUTES } from '@/constants/routes'
import { useAuthStore, selectIsAuthenticated } from '@/stores/authStore'
import { useLandingI18n } from '../../i18n'
import { useActiveSection, useScrolled } from '../../hooks/useScroll'
import { useLandingUi } from '../../store/ui'
import { LANDING_PATHS } from '../../paths'
import { NAV_ITEMS, SPY_SECTIONS } from '../../data/navigation'
import { Button, IconButton } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Drawer } from '../ui/Overlay'
import { LanguageMenu, LanguageSegmented } from './LanguageSwitcher'
import { ThemeSegmented, ThemeToggle } from './ThemeToggle'
import { SectionLink } from './SectionLink'
import { Wordmark } from './Wordmark'

/** "Masuk" for visitors, "Buka Dashboard" when a session already exists. */
function useAppEntry() {
  const { m } = useLandingI18n()
  const authenticated = useAuthStore(selectIsAuthenticated)
  return authenticated
    ? { to: ROUTES.dashboard, label: m.nav.openDashboard, icon: LayoutDashboard }
    : { to: ROUTES.login, label: m.nav.signIn, icon: LogIn }
}

export function Navbar() {
  const { m } = useLandingI18n()
  const { pathname } = useLocation()
  const onHome = pathname === LANDING_PATHS.home
  const scrolled = useScrolled()
  const active = useActiveSection(onHome ? SPY_SECTIONS : [])
  const openRequest = useLandingUi((s) => s.openRequest)
  const [menuOpen, setMenuOpen] = useState(false)
  const entry = useAppEntry()
  const solid = scrolled || !onHome

  return (
    <header
      className={cn(
        'fixed inset-x-0 top-0 z-50 border-b transition-[background-color,border-color,box-shadow] duration-300',
        solid ? 'border-lp-line bg-lp-canvas/80 shadow-lp-sm backdrop-blur-xl backdrop-saturate-150' : 'border-transparent bg-transparent',
      )}
    >
      <div className="lp-container flex h-16 items-center gap-3">
        <SectionLink section="home" className="-ml-1 rounded-lp-sm p-1" aria-current={undefined}>
          <span className="sr-only">{m.nav.homeLink}</span>
          <span aria-hidden>
            <Wordmark />
          </span>
        </SectionLink>

        <nav aria-label={m.nav.primary} className="ml-4 hidden lg:block xl:ml-8">
          <ul className="flex items-center gap-0.5">
            {NAV_ITEMS.map((item) => {
              const isActive = onHome && item.covers.some((c) => c === active)
              return (
                <li key={item.key}>
                  <SectionLink
                    section={item.target}
                    aria-current={isActive ? 'location' : undefined}
                    className={cn(
                      'relative inline-flex h-10 items-center rounded-full px-3 text-[14px] font-medium transition-colors xl:px-3.5',
                      isActive ? 'text-lp-fg' : 'text-lp-muted hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <span
                      aria-hidden
                      className={cn(
                        'absolute bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-lp-brand transition-opacity duration-300',
                        isActive ? 'opacity-100' : 'opacity-0',
                      )}
                    />
                  </SectionLink>
                </li>
              )
            })}
          </ul>
        </nav>

        <div className="ml-auto flex items-center gap-1">
          <LanguageMenu className="hidden sm:block" />
          <ThemeToggle />
          <Link to={entry.to} className={buttonClass('ghost', 'sm', 'hidden xl:inline-flex')}>
            {entry.label}
          </Link>
          <Button size="sm" className="ml-1 hidden sm:inline-flex" onClick={() => openRequest('demo')}>
            {m.nav.scheduleDemo}
          </Button>
          <IconButton
            className="lg:hidden"
            aria-label={m.nav.openMenu}
            aria-expanded={menuOpen}
            aria-controls="lp-mobile-menu"
            onClick={() => setMenuOpen(true)}
          >
            <Menu size={20} aria-hidden />
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
  entry: ReturnType<typeof useAppEntry>
}) {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  const EntryIcon = entry.icon

  return (
    <Drawer open={open} onClose={onClose} label={m.nav.mobile} id="lp-mobile-menu">
      <div className="flex h-16 shrink-0 items-center justify-between border-b border-lp-line px-5">
        <Wordmark />
        <IconButton onClick={onClose} aria-label={m.nav.closeMenu} className="-mr-2">
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
                      'flex h-12 items-center justify-between rounded-lp px-3 text-[16px] font-medium transition-colors',
                      isActive ? 'bg-lp-sunken text-lp-fg' : 'text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
                    )}
                  >
                    {m.nav[item.key]}
                    <ArrowRight size={16} aria-hidden className="text-lp-subtle" />
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
        <Button
          className="w-full"
          onClick={() => {
            onClose()
            openRequest('demo')
          }}
        >
          {m.nav.scheduleDemo}
        </Button>
        <Link to={entry.to} onClick={onClose} className={buttonClass('secondary', 'md', 'w-full')}>
          <EntryIcon size={16} aria-hidden />
          {entry.label}
        </Link>
      </div>
    </Drawer>
  )
}
