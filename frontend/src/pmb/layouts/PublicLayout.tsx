import { useState, type ReactNode } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { ArrowRight, ChevronRight, Clock, ExternalLink, Info, Mail, MapPin, Menu, MessageCircle, Phone, UserRound, X } from 'lucide-react'
import { ThemeToggle } from '@/components/layout/ThemeToggle'
import { buttonClass } from '@/landing/components/ui/buttonClass'
import { mobileLinkClass, navLinkClass } from '@/landing/components/layout/navStyles'
import { LANDING_PATHS } from '@/landing/paths'
import { pmbPublic } from '@/pmb/api/public'
import { useLoad } from '@/pmb/components/useLoad'
import { usePmbTheme } from '@/pmb/components/usePmbTheme'
import { SiteContext } from '@/pmb/components/SiteContext'
import { NavUnderline, SiteMark } from '@/pmb/components/site'
import { useApplicantStore } from '@/pmb/stores/applicantStore'
import { PMB, to } from '@/pmb/routes'

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

/** Pita oranye footer — padanan daftar fakultas di footer landing. */
const INFO_LINKS = [
  { label: 'Program Studi', path: PMB.programs },
  { label: 'Jalur Masuk', path: PMB.paths },
  { label: 'Biaya Kuliah', path: PMB.fees },
  { label: 'Jadwal Pendaftaran', path: PMB.schedule },
  { label: 'Panduan Pendaftaran', path: PMB.guide },
  { label: 'Pertanyaan Umum', path: PMB.faq },
]

const footerLink = 'inline-flex min-h-11 items-center gap-2 text-[0.875rem] text-lp-band-muted transition-colors hover:text-lp-band-fg'

/** Kerangka website publik PMB: header tenant, menu, footer kontak (PUB-01..14) — bergaya landing page. */
export function PublicLayout() {
  usePmbTheme()
  const { data: site, error } = useLoad(() => pmbPublic.site(), [])
  const loggedIn = useApplicantStore((s) => s.token !== null)
  const [open, setOpen] = useState(false)
  const contact = site?.contact ?? {}
  const website = site?.university.website

  return (
    <SiteContext.Provider value={site}>
      <div className="flex min-h-screen flex-col bg-lp-canvas text-lp-fg">
        <a href="#pmb-main" className="sr-only z-[100] rounded-full bg-lp-brand px-5 py-2.5 text-[0.875rem] font-semibold text-lp-onbrand focus:not-sr-only focus:fixed focus:left-4 focus:top-3">
          Lewati ke konten
        </a>

        <header className="sticky top-0 z-30 border-b border-lp-line bg-lp-surface/95 backdrop-blur-xl backdrop-saturate-150">
          <div className="lp-container flex min-h-16 max-w-[90rem] items-center gap-3 py-1 lg:min-h-[4.5rem]">
            <Link to={PMB.home} className="-ml-1 mr-auto flex min-w-0 rounded-lp-sm p-1">
              <span className="sr-only">Beranda PMB</span>
              <SiteMark site={site} />
            </Link>

            <nav aria-label="Menu PMB" className="mr-2 hidden h-full xl:block">
              <ul className="flex h-full items-stretch">
                {MENU.map((item) => (
                  <li key={item.path} className="flex">
                    <NavLink to={item.path} className={({ isActive }) => navLinkClass(isActive)}>
                      {({ isActive }) => (
                        <>
                          {item.label}
                          <NavUnderline active={isActive} />
                        </>
                      )}
                    </NavLink>
                  </li>
                ))}
              </ul>
            </nav>

            <div className="flex shrink-0 items-center gap-1">
              <ThemeToggle />
              {loggedIn ? (
                <Link to={PMB.portal.home} className={buttonClass('primary', 'sm', 'ml-1 hidden px-5 sm:inline-flex')}>
                  <UserRound size={16} aria-hidden />
                  Portal Saya
                </Link>
              ) : (
                <>
                  <Link to={PMB.login} className={buttonClass('ghost', 'sm', 'hidden sm:inline-flex')}>Masuk</Link>
                  <Link to={PMB.register} className={buttonClass('primary', 'sm', 'ml-1 hidden px-5 sm:inline-flex')}>Daftar</Link>
                </>
              )}
              <button
                type="button"
                className="flex size-9 items-center justify-center rounded-lg text-lp-muted transition-colors hover:bg-lp-sunken hover:text-lp-fg xl:hidden"
                aria-label={open ? 'Tutup menu' : 'Buka menu'}
                aria-expanded={open}
                aria-controls="pmb-mobile-menu"
                onClick={() => setOpen(!open)}
              >
                {open ? <X size={22} aria-hidden /> : <Menu size={22} aria-hidden />}
              </button>
            </div>
          </div>

          {open && (
            <nav id="pmb-mobile-menu" aria-label="Menu PMB" className="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-lp-line bg-lp-surface xl:hidden">
              <div className="lp-container py-3">
                <ul className="grid gap-0.5 sm:grid-cols-2 sm:gap-x-4">
                  {MENU.map((item) => (
                    <li key={item.path}>
                      <NavLink to={item.path} onClick={() => setOpen(false)} className={({ isActive }) => mobileLinkClass(isActive)}>
                        {item.label}
                        <ChevronRight size={17} aria-hidden className="text-lp-subtle" />
                      </NavLink>
                    </li>
                  ))}
                </ul>
                <div className="mt-3 grid gap-2 border-t border-lp-line pt-3 sm:hidden">
                  {loggedIn ? (
                    <Link to={PMB.portal.home} onClick={() => setOpen(false)} className={buttonClass('primary', 'md', 'w-full')}>Portal Saya</Link>
                  ) : (
                    <>
                      <Link to={PMB.register} onClick={() => setOpen(false)} className={buttonClass('primary', 'md', 'w-full')}>Daftar Akun</Link>
                      <Link to={PMB.login} onClick={() => setOpen(false)} className={buttonClass('secondary', 'md', 'w-full')}>Masuk</Link>
                    </>
                  )}
                </div>
              </div>
            </nav>
          )}
        </header>

        {error && error.status === 400 && (
          <div className="lp-container mt-6">
            <p role="alert" className="mx-auto flex max-w-3xl items-start gap-3 rounded-lp-lg border border-lp-gold/40 bg-lp-gold/[0.07] p-4 text-[0.875rem] leading-relaxed text-lp-fg">
              <Info size={18} aria-hidden className="mt-0.5 shrink-0 text-lp-gold-text" />
              <span>Kampus tidak dapat ditentukan dari alamat ini. Buka website PMB melalui domain kampus atau tambahkan <code>?kampus=KODE</code> pada alamat.</span>
            </p>
          </div>
        )}

        <main id="pmb-main" tabIndex={-1} className="flex-1 focus:outline-none">
          <Outlet />
        </main>

        <footer className="lp-on-band bg-lp-band text-lp-band-fg">
          <nav aria-labelledby="pmb-footer-info" className="lp-on-brand bg-lp-brand text-lp-onbrand">
            <div className="lp-container flex flex-col gap-6 py-10 sm:py-12 lg:flex-row lg:items-start lg:gap-12">
              <div className="shrink-0 lg:w-48">
                <h2 id="pmb-footer-info" className="text-[0.8125rem] font-semibold uppercase tracking-[0.12em]">Informasi Pendaftaran</h2>
                <span aria-hidden className="mt-3 block h-0.5 w-8 rounded-full bg-white/70" />
              </div>
              <ul className="grid flex-1 gap-x-8 sm:grid-cols-2 lg:grid-cols-3">
                {INFO_LINKS.map((item) => (
                  <li key={item.path}>
                    <Link to={item.path} className="inline-flex min-h-11 items-center py-1 text-[0.9375rem] font-medium decoration-2 underline-offset-4 hover:underline">
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
              <Link to={loggedIn ? PMB.portal.home : PMB.register} className={buttonClass('band', 'md', 'group shrink-0 self-start')}>
                {loggedIn ? 'Buka Portal' : 'Daftar Sekarang'}
                <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
              </Link>
            </div>
          </nav>

          <div className="lp-container py-14 sm:py-16">
            <div className="grid gap-12 lg:grid-cols-12">
              <div className="lg:col-span-4">
                <Link to={PMB.home} className="inline-flex max-w-full rounded-lp-sm">
                  <span className="sr-only">Beranda PMB</span>
                  <SiteMark site={site} tone="band" size="lg" />
                </Link>
                <p className="lp-small mt-6 max-w-sm text-lp-band-muted">
                  Website resmi Panitia Penerimaan Mahasiswa Baru. Pendaftaran, pembayaran, ujian, hingga daftar ulang dilakukan secara daring melalui portal pendaftar.
                </p>
              </div>

              <div className="grid gap-x-8 gap-y-10 sm:grid-cols-2 md:grid-cols-3 lg:col-span-8">
                <Column title="Kontak Panitia">
                  {contact.address && <li className="flex gap-2 py-1.5 text-[0.875rem] text-lp-band-muted"><MapPin size={15} aria-hidden className="mt-0.5 shrink-0 text-lp-accent" />{contact.address}</li>}
                  {contact.phone && <li><a href={`tel:${contact.phone.replace(/[^\d+]/g, '')}`} className={footerLink}><Phone size={15} aria-hidden className="shrink-0 text-lp-accent" />{contact.phone}</a></li>}
                  {contact.whatsapp && <li><a href={`https://wa.me/${contact.whatsapp.replace(/\D/g, '')}`} target="_blank" rel="noopener noreferrer" className={footerLink}><MessageCircle size={15} aria-hidden className="shrink-0 text-lp-accent" />{contact.whatsapp}</a></li>}
                  {contact.email && <li><a href={`mailto:${contact.email}`} className={`${footerLink} break-all`}><Mail size={15} aria-hidden className="shrink-0 text-lp-accent" />{contact.email}</a></li>}
                  {contact.hours && <li className="flex gap-2 py-1.5 text-[0.875rem] text-lp-band-muted"><Clock size={15} aria-hidden className="mt-0.5 shrink-0 text-lp-accent" />{contact.hours}</li>}
                  {!contact.address && !contact.phone && !contact.email && <li className="lp-small text-lp-band-muted">Kontak panitia belum diatur.</li>}
                </Column>
                <Column title="Layanan Pendaftar">
                  {loggedIn ? (
                    <li><Link to={PMB.portal.home} className={footerLink}>Portal Saya</Link></li>
                  ) : (
                    <>
                      <li><Link to={PMB.login} className={footerLink}>Masuk Portal</Link></li>
                      <li><Link to={PMB.register} className={footerLink}>Daftar Akun</Link></li>
                      <li><Link to={PMB.forgot} className={footerLink}>Lupa Kata Sandi</Link></li>
                    </>
                  )}
                  <li><Link to={PMB.statusCheck} className={footerLink}>Cek Status Pendaftaran</Link></li>
                  <li><Link to={PMB.reRegistrationInfo} className={footerLink}>Info Daftar Ulang</Link></li>
                </Column>
                <Column title="Kabar & Bantuan">
                  <li><Link to={PMB.news} className={footerLink}>Berita & Informasi</Link></li>
                  <li><Link to={PMB.contact} className={footerLink}>Hubungi Panitia</Link></li>
                  {site?.legal.privacy_policy && <li><Link to={to(PMB.legal, { type: 'privacy_policy' })} className={footerLink}>Kebijakan Privasi</Link></li>}
                  {site?.legal.terms && <li><Link to={to(PMB.legal, { type: 'terms' })} className={footerLink}>Ketentuan Pendaftaran</Link></li>}
                </Column>
              </div>
            </div>

            <div className="mt-14 flex flex-col gap-4 border-t border-lp-band-line pt-8 sm:flex-row sm:items-center sm:justify-between">
              <p className="text-[0.8125rem] text-lp-band-muted">
                © {new Date().getFullYear()} {site?.university.name ?? 'Penerimaan Mahasiswa Baru'} · Panitia Penerimaan Mahasiswa Baru
              </p>
              {website ? (
                <a href={website} target="_blank" rel="noopener noreferrer" className={footerLink}>
                  Website Universitas
                  <ExternalLink size={14} aria-hidden />
                </a>
              ) : (
                <Link to={LANDING_PATHS.home} className={footerLink}>
                  Beranda Universitas
                  <ArrowRight size={14} aria-hidden />
                </Link>
              )}
            </div>
          </div>
        </footer>
      </div>
    </SiteContext.Provider>
  )
}

function Column({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div>
      <h2 className="text-[0.8125rem] font-semibold uppercase tracking-[0.12em] text-lp-band-fg">{title}</h2>
      <span aria-hidden className="mt-3 block h-0.5 w-8 rounded-full bg-lp-accent" />
      <ul className="mt-4 space-y-0.5">{children}</ul>
    </div>
  )
}
