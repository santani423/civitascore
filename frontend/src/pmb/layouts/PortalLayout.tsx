import { useState } from 'react'
import { Link, Navigate, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { Bell, FileText, Home, LogOut, Menu, MessageSquare, Settings, UserRound, X } from 'lucide-react'
import { pmbApplicant } from '@/pmb/api/applicant'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { useLoad } from '@/pmb/components/useLoad'
import { PortalContext } from '@/pmb/components/PortalContext'
import { PMB } from '@/pmb/routes'
import { cn } from '@/utils/cn'

const NAV = [
  { label: 'Beranda', path: PMB.portal.home, icon: Home, end: true },
  { label: 'Profil', path: PMB.portal.profile, icon: UserRound },
  { label: 'Daftar Baru', path: PMB.portal.newApplication, icon: FileText },
  { label: 'Pesan', path: PMB.portal.messages, icon: MessageSquare },
  { label: 'Notifikasi', path: PMB.portal.notifications, icon: Bell },
  { label: 'Akun', path: PMB.portal.account, icon: Settings },
]

/** Portal pendaftar (POR-*): wajib login sebagai pendaftar; sesi terpisah dari staf. */
export function PortalLayout() {
  const token = useApplicantStore((s) => s.token)
  const clear = useApplicantStore((s) => s.clear)
  const location = useLocation()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const me = useLoad(() => (token ? pmbApplicant.me() : Promise.resolve(null)), [token])

  if (!token) {
    return <Navigate to={`${PMB.login}?next=${encodeURIComponent(location.pathname + location.search)}`} replace />
  }

  const logout = async () => {
    await pmbApplicant.logout().catch(() => undefined)
    clear()
    navigate(PMB.login, { replace: true })
  }

  const unread = me.data?.unread_notifications ?? 0

  return (
    <PortalContext.Provider value={{ me: me.data, reloadMe: me.reload }}>
      <div className="min-h-screen bg-background">
        <header className="sticky top-0 z-30 border-b border-border bg-surface">
          <div className="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4">
            <button type="button" className="rounded p-1 md:hidden" onClick={() => setOpen(!open)} aria-label="Menu">{open ? <X className="size-5" /> : <Menu className="size-5" />}</button>
            <Link to={PMB.portal.home} className="font-semibold text-ink-primary">Portal PMB</Link>
            <nav className="ml-6 hidden gap-1 md:flex">
              {NAV.map((item) => (
                <NavLink key={item.path} to={item.path} end={item.end} className={({ isActive }) => cn('flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm', isActive ? 'bg-primary-100 text-primary-700' : 'text-ink-secondary hover:text-ink-primary')}>
                  <item.icon className="size-4" />{item.label}
                  {item.path === PMB.portal.notifications && unread > 0 && <span className="rounded-full bg-danger px-1.5 text-xs text-white">{unread}</span>}
                </NavLink>
              ))}
            </nav>
            <div className="ml-auto flex items-center gap-3 text-sm">
              <span className="hidden text-ink-secondary sm:inline">{me.data?.account.name}</span>
              <button type="button" onClick={logout} className="flex items-center gap-1 rounded-lg px-2 py-1 text-ink-secondary hover:bg-surface-hover"><LogOut className="size-4" />Keluar</button>
            </div>
          </div>
          {open && (
            <nav className="grid gap-1 border-t border-border p-2 md:hidden">
              {NAV.map((item) => <Link key={item.path} to={item.path} onClick={() => setOpen(false)} className="flex items-center gap-2 rounded px-3 py-2 text-sm"><item.icon className="size-4" />{item.label}</Link>)}
            </nav>
          )}
        </header>
        <main className="mx-auto max-w-6xl px-4 py-6">
          <Outlet />
        </main>
      </div>
    </PortalContext.Provider>
  )
}
