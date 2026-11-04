import { useState } from 'react'
import { Link, Navigate, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { Bell, FileText, Home, LogOut, Menu, MessageSquare, Settings, UserRound, X } from 'lucide-react'
import { ThemeToggle } from '@/components/layout/ThemeToggle'
import { mobileLinkClass, navLinkClass } from '@/landing/components/layout/navStyles'
import { pmbApplicant } from '@/pmb/api/applicant'
import { pmbPublic } from '@/pmb/api/public'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { useLoad } from '@/pmb/components/useLoad'
import { usePmbTheme } from '@/pmb/components/usePmbTheme'
import { PortalContext } from '@/pmb/components/PortalContext'
import { SiteContext } from '@/pmb/components/SiteContext'
import { NavUnderline, SiteMark } from '@/pmb/components/site'
import { PMB } from '@/pmb/routes'

const NAV = [
  { label: 'Beranda', path: PMB.portal.home, icon: Home, end: true },
  { label: 'Profil', path: PMB.portal.profile, icon: UserRound },
  { label: 'Daftar Baru', path: PMB.portal.newApplication, icon: FileText },
  { label: 'Pesan', path: PMB.portal.messages, icon: MessageSquare },
  { label: 'Notifikasi', path: PMB.portal.notifications, icon: Bell },
  { label: 'Akun', path: PMB.portal.account, icon: Settings },
]

/** Portal pendaftar (POR-*): wajib login sebagai pendaftar; sesi terpisah dari staf. Header bergaya landing page. */
export function PortalLayout() {
  usePmbTheme()
  const token = useApplicantStore((s) => s.token)
  const clear = useApplicantStore((s) => s.clear)
  const location = useLocation()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const me = useLoad(() => (token ? pmbApplicant.me() : Promise.resolve(null)), [token])
  const site = useLoad(() => pmbPublic.site(), [])

  if (!token) {
    return <Navigate to={`${PMB.login}?next=${encodeURIComponent(location.pathname + location.search)}`} replace />
  }

  const logout = async () => {
    await pmbApplicant.logout().catch(() => undefined)
    clear()
    navigate(PMB.login, { replace: true })
  }

  const unread = me.data?.unread_notifications ?? 0
  const badge = (path: string) => path === PMB.portal.notifications && unread > 0 && (
    <span className="rounded-full bg-lp-brand px-1.5 text-[0.6875rem] font-semibold leading-5 text-lp-onbrand">
      {unread}
      <span className="sr-only"> belum dibaca</span>
    </span>
  )

  return (
    <SiteContext.Provider value={site.data}>
      <PortalContext.Provider value={{ me: me.data, reloadMe: me.reload }}>
        <div className="min-h-screen bg-lp-canvas text-lp-fg">
          <header className="sticky top-0 z-30 border-b border-lp-line bg-lp-surface/95 backdrop-blur-xl backdrop-saturate-150">
            <div className="lp-container flex min-h-16 max-w-[90rem] items-center gap-3 py-1 lg:min-h-[4.5rem]">
              <Link to={PMB.portal.home} className="-ml-1 mr-auto flex min-w-0 rounded-lp-sm p-1">
                <span className="sr-only">Beranda Portal PMB</span>
                <SiteMark site={site.data} label="Portal" />
              </Link>
              <nav aria-label="Menu portal" className="mr-2 hidden h-full xl:block">
                <ul className="flex h-full items-stretch">
                  {NAV.map((item) => (
                    <li key={item.path} className="flex">
                      <NavLink to={item.path} end={item.end} className={({ isActive }) => navLinkClass(isActive)}>
                        {({ isActive }) => (
                          <>
                            <item.icon size={16} aria-hidden className="mr-1 opacity-80" />
                            {item.label}
                            {badge(item.path)}
                            <NavUnderline active={isActive} />
                          </>
                        )}
                      </NavLink>
                    </li>
                  ))}
                </ul>
              </nav>
              <div className="flex shrink-0 items-center gap-1">
                {me.data?.account.name && <span className="mr-1 hidden max-w-40 truncate text-[0.875rem] text-lp-muted 2xl:inline">{me.data.account.name}</span>}
                <ThemeToggle />
                <button type="button" onClick={logout} className="flex h-9 items-center gap-1.5 rounded-lg px-2.5 text-[0.875rem] font-semibold text-lp-muted transition-colors hover:bg-lp-sunken hover:text-lp-fg">
                  <LogOut size={16} aria-hidden />
                  <span className="hidden sm:inline">Keluar</span>
                  <span className="sr-only sm:hidden">Keluar</span>
                </button>
                <button
                  type="button"
                  className="flex size-9 items-center justify-center rounded-lg text-lp-muted transition-colors hover:bg-lp-sunken hover:text-lp-fg xl:hidden"
                  aria-label={open ? 'Tutup menu' : 'Buka menu'}
                  aria-expanded={open}
                  aria-controls="pmb-portal-menu"
                  onClick={() => setOpen(!open)}
                >
                  {open ? <X size={22} aria-hidden /> : <Menu size={22} aria-hidden />}
                </button>
              </div>
            </div>
            {open && (
              <nav id="pmb-portal-menu" aria-label="Menu portal" className="border-t border-lp-line bg-lp-surface xl:hidden">
                <div className="lp-container py-3">
                  <ul className="grid gap-0.5 sm:grid-cols-2 sm:gap-x-4">
                    {NAV.map((item) => (
                      <li key={item.path}>
                        <NavLink to={item.path} end={item.end} onClick={() => setOpen(false)} className={({ isActive }) => mobileLinkClass(isActive)}>
                          <span className="flex items-center gap-3">
                            <item.icon size={18} aria-hidden className="opacity-80" />
                            {item.label}
                          </span>
                          {badge(item.path)}
                        </NavLink>
                      </li>
                    ))}
                  </ul>
                </div>
              </nav>
            )}
          </header>
          <main className="lp-container py-8">
            <Outlet />
          </main>
        </div>
      </PortalContext.Provider>
    </SiteContext.Provider>
  )
}
