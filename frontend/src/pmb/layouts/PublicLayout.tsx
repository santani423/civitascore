import { useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { Menu, X, Phone, Mail, MapPin, Clock } from 'lucide-react'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { SiteContext } from '@/pmb/components/SiteContext'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { PMB, to } from '@/pmb/routes'
import { cn } from '@/utils/cn'

const MENU = [
  { label: 'Prodi', path: PMB.programs },
  { label: 'Jalur', path: PMB.paths },
  { label: 'Biaya', path: PMB.fees },
  { label: 'Jadwal', path: PMB.schedule },
  { label: 'Panduan', path: PMB.guide },
  { label: 'FAQ', path: PMB.faq },
  { label: 'Berita', path: PMB.news },
  { label: 'Cek Status', path: PMB.statusCheck },
  { label: 'Kontak', path: PMB.contact },
]

/** Kerangka website publik PMB: header tenant, menu, footer kontak (PUB-01..14). */
export function PublicLayout() {
  const { data: site, error } = useLoad(() => pmbPublic.site(), [])
  const loggedIn = useApplicantStore((s) => s.token !== null)
  const [open, setOpen] = useState(false)
  const name = site?.university.short_name || site?.university.name || 'PMB'

  return (
    <SiteContext.Provider value={site}>
      <div className="flex min-h-screen flex-col bg-background">
        <header className="sticky top-0 z-30 border-b border-border bg-surface/95 backdrop-blur">
          <div className="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4">
            <Link to={PMB.home} className="flex items-center gap-2 font-semibold text-ink-primary">
              {site?.university.logo_url ? <img src={site.university.logo_url} alt="" className="size-8 rounded" /> : <span className="flex size-8 items-center justify-center rounded bg-primary text-sm text-white">{name.slice(0, 2)}</span>}
              <span>PMB {name}</span>
            </Link>
            <nav className="ml-auto hidden items-center gap-1 lg:flex">
              {MENU.map((item) => (
                <NavLink key={item.path} to={item.path} className={({ isActive }) => cn('rounded-lg px-2.5 py-1.5 text-sm', isActive ? 'bg-primary-100 text-primary-700' : 'text-ink-secondary hover:text-ink-primary')}>
                  {item.label}
                </NavLink>
              ))}
            </nav>
            <div className="ml-auto flex items-center gap-2 lg:ml-2">
              {loggedIn ? (
                <Link to={PMB.portal.home} className="rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-white">Portal Saya</Link>
              ) : (
                <>
                  <Link to={PMB.login} className="rounded-lg px-3 py-1.5 text-sm font-medium text-ink-primary hover:bg-surface-hover">Masuk</Link>
                  <Link to={PMB.register} className="rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-white">Daftar</Link>
                </>
              )}
              <button type="button" className="rounded-lg p-1.5 lg:hidden" aria-label="Menu" onClick={() => setOpen(!open)}>{open ? <X className="size-5" /> : <Menu className="size-5" />}</button>
            </div>
          </div>
          {open && (
            <nav className="grid grid-cols-2 gap-1 border-t border-border p-3 lg:hidden">
              {MENU.map((item) => <Link key={item.path} to={item.path} onClick={() => setOpen(false)} className="rounded-lg px-3 py-2 text-sm text-ink-primary hover:bg-surface-hover">{item.label}</Link>)}
            </nav>
          )}
        </header>

        {error && error.status === 400 && (
          <div className="mx-auto mt-6 max-w-3xl rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
            Kampus tidak dapat ditentukan dari alamat ini. Buka website PMB melalui domain kampus atau tambahkan <code>?kampus=KODE</code> pada alamat.
          </div>
        )}

        <main className="flex-1">
          <Outlet />
        </main>

        <footer className="border-t border-border bg-surface">
          <div className="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:grid-cols-3">
            <div>
              <p className="font-semibold text-ink-primary">{site?.university.name ?? 'Penerimaan Mahasiswa Baru'}</p>
              <p className="mt-1 text-sm text-ink-secondary">Panitia Penerimaan Mahasiswa Baru</p>
            </div>
            <ul className="space-y-1.5 text-sm text-ink-secondary">
              {site?.contact.address && <li className="flex gap-2"><MapPin className="mt-0.5 size-4 shrink-0" />{site.contact.address}</li>}
              {site?.contact.phone && <li className="flex gap-2"><Phone className="size-4" />{site.contact.phone}</li>}
              {site?.contact.email && <li className="flex gap-2"><Mail className="size-4" />{site.contact.email}</li>}
              {site?.contact.hours && <li className="flex gap-2"><Clock className="size-4" />{site.contact.hours}</li>}
            </ul>
            <ul className="space-y-1.5 text-sm">
              {site?.legal.privacy_policy && <li><Link className="text-primary hover:underline" to={to(PMB.legal, { type: 'privacy_policy' })}>Kebijakan Privasi</Link></li>}
              {site?.legal.terms && <li><Link className="text-primary hover:underline" to={to(PMB.legal, { type: 'terms' })}>Ketentuan Pendaftaran</Link></li>}
              <li><Link className="text-primary hover:underline" to={PMB.reRegistrationInfo}>Info Daftar Ulang</Link></li>
              <li><Link className="text-primary hover:underline" to={PMB.statusCheck}>Cek Status Pendaftaran</Link></li>
            </ul>
          </div>
        </footer>
      </div>
    </SiteContext.Provider>
  )
}
