import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { Accessibility, Mail, MessageSquare, Phone } from 'lucide-react'
import { ROUTES } from '@/constants/routes'
import { PMB } from '@/pmb/routes'
import { fill, useLandingI18n } from '../../i18n'
import { landingConfig } from '../../config'
import { useAppEntry } from '../../hooks/useAppEntry'
import { NAV_ITEMS } from '../../data/navigation'
import { PHOTOS, PHOTO_LICENSE } from '../../data/photos'
import { LANDING_PATHS } from '../../paths'
import { useLandingUi } from '../../store/ui'
import { useA11y } from '../../store/a11y'
import { LanguageSegmented } from './LanguageSwitcher'
import { ThemeSegmented } from './ThemeToggle'
import { NavItemLink } from './NavItemLink'
import { Wordmark } from './Wordmark'

const linkClass = 'inline-flex min-h-11 items-center text-[0.875rem] text-lp-band-muted transition-colors hover:text-lp-band-fg'

function Column({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div>
      <h2 className="text-[0.8125rem] font-semibold uppercase tracking-[0.12em] text-lp-band-fg">{title}</h2>
      <span aria-hidden className="mt-3 block h-0.5 w-8 rounded-full bg-lp-accent" />
      <ul className="mt-4 space-y-0.5">{children}</ul>
    </div>
  )
}

export function Footer() {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
  const openA11y = useA11y((s) => s.openPanel)
  const entry = useAppEntry()
  const { contactEmail, contactPhone } = landingConfig

  return (
    <footer className="lp-on-band bg-lp-band text-lp-band-fg">
      <div className="lp-container py-14 sm:py-16">
        <div className="grid gap-12 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <span className="flex">
              <Wordmark size="lg" tone="band" />
            </span>
            <p className="lp-small mt-6 max-w-sm text-lp-band-muted">{m.footer.description}</p>
          </div>

          <div className="grid gap-10 sm:grid-cols-3 lg:col-span-8">
            <Column title={m.footer.columns.explore}>
              {NAV_ITEMS.map((item) => (
                <li key={item.key}>
                  <NavItemLink item={item} className={linkClass}>
                    {m.nav[item.key]}
                  </NavItemLink>
                </li>
              ))}
            </Column>
            <Column title={m.footer.columns.access}>
              <li>
                <Link to={entry.to} className={linkClass}>
                  {entry.authenticated ? m.nav.openDashboard : m.footer.links.signIn}
                </Link>
              </li>
              {!entry.authenticated && (
                <li>
                  <Link to={ROUTES.forgotPassword} className={linkClass}>
                    {m.footer.links.forgotPassword}
                  </Link>
                </li>
              )}
              <li>
                <Link to={PMB.home} className={linkClass}>
                  {m.footer.links.admissions}
                </Link>
              </li>
              <li>
                <Link to={PMB.news} className={linkClass}>
                  {m.footer.links.admissionsNews}
                </Link>
              </li>
              <li>
                <Link to={PMB.contact} className={linkClass}>
                  {m.footer.links.admissionsContact}
                </Link>
              </li>
            </Column>
            <Column title={m.footer.columns.contact}>
              {contactEmail && (
                <li>
                  <a href={`mailto:${contactEmail}`} className={`${linkClass} inline-flex items-center gap-2 break-all`}>
                    <Mail size={15} aria-hidden className="shrink-0 text-lp-accent" />
                    {contactEmail}
                  </a>
                </li>
              )}
              {contactPhone && (
                <li>
                  <a href={`tel:${contactPhone.replace(/[^\d+]/g, '')}`} className={`${linkClass} inline-flex items-center gap-2`}>
                    <Phone size={15} aria-hidden className="shrink-0 text-lp-accent" />
                    {contactPhone}
                  </a>
                </li>
              )}
              {!contactEmail && !contactPhone && <li className="lp-small text-lp-band-muted">{m.footer.contactPending}</li>}
              <li>
                <button type="button" className={`${linkClass} inline-flex items-center gap-2 text-left`} onClick={openContact}>
                  <MessageSquare size={15} aria-hidden className="shrink-0 text-lp-accent" />
                  {m.footer.links.contactTeam}
                </button>
              </li>
              <li>
                <button type="button" className={`${linkClass} gap-2 text-left`} onClick={openA11y}>
                  <Accessibility size={15} aria-hidden className="shrink-0 text-lp-accent" />
                  {m.a11y.open}
                </button>
              </li>
            </Column>
          </div>
        </div>

        <div className="mt-14 flex flex-col gap-6 border-t border-lp-band-line pt-8 lg:flex-row lg:items-start lg:justify-between">
          <div className="max-w-2xl space-y-2">
            <p className="text-[0.8125rem] text-lp-band-muted">{fill(m.footer.rights, { year: new Date().getFullYear() })}</p>
            <p className="text-[0.75rem] leading-relaxed text-lp-band-muted/80">
              {m.footer.photoCredit}{' '}
              {Object.values(PHOTOS).map((photo, i) => (
                <span key={photo.source}>
                  {i > 0 && ', '}
                  <a href={photo.source} target="_blank" rel="noopener noreferrer" className="underline decoration-lp-band-line underline-offset-2 hover:text-lp-band-fg">
                    {fill(m.footer.photoItem, { n: i + 1, author: photo.author })}
                  </a>
                </span>
              ))}
              {' · '}
              <a href={PHOTO_LICENSE.url} target="_blank" rel="noopener noreferrer license" className="underline decoration-lp-band-line underline-offset-2 hover:text-lp-band-fg">
                {PHOTO_LICENSE.name}
              </a>
            </p>
          </div>
          <div className="flex flex-col gap-5 sm:flex-row sm:flex-wrap sm:items-center lg:justify-end">
            <div className="flex gap-5">
              <Link to={LANDING_PATHS.privacy} className="inline-flex min-h-11 items-center text-[0.8125rem] text-lp-band-muted hover:text-lp-band-fg">
                {m.footer.privacy}
              </Link>
              <Link to={LANDING_PATHS.terms} className="inline-flex min-h-11 items-center text-[0.8125rem] text-lp-band-muted hover:text-lp-band-fg">
                {m.footer.terms}
              </Link>
            </div>
            <div className="flex flex-wrap items-center gap-2" aria-label={m.footer.preferences} role="group">
              <LanguageSegmented tone="compact" />
              <ThemeSegmented tone="compact" />
            </div>
          </div>
        </div>
      </div>
    </footer>
  )
}
