import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { Mail, Phone } from 'lucide-react'
import { ROUTES } from '@/constants/routes'
import { fill, useLandingI18n } from '../../i18n'
import { landingConfig } from '../../config'
import { ECOSYSTEM } from '../../data/catalog'
import { LANDING_PATHS } from '../../paths'
import { useLandingUi } from '../../store/ui'
import { LanguageSegmented } from './LanguageSwitcher'
import { ThemeSegmented } from './ThemeToggle'
import { SectionLink } from './SectionLink'
import { Wordmark } from './Wordmark'

const linkClass = 'text-[14px] text-lp-muted transition-colors hover:text-lp-fg'

function Column({ title, children }: { title: string; children: ReactNode }) {
  return (
    <div>
      <h2 className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{title}</h2>
      <ul className="mt-4 space-y-3">{children}</ul>
    </div>
  )
}

export function Footer() {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  const setPlatformCategory = useLandingUi((s) => s.setPlatformCategory)
  const { contactEmail, contactPhone } = landingConfig

  return (
    <footer className="border-t border-lp-line bg-lp-surface">
      <div className="lp-container py-14 sm:py-16">
        <div className="grid gap-12 lg:grid-cols-12">
          <div className="lg:col-span-4">
            <Wordmark size="lg" />
            <p className="lp-small mt-5 max-w-sm text-lp-muted">{m.footer.description}</p>
            <div className="mt-6">
              <h2 className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{m.footer.columns.contact}</h2>
              {contactEmail || contactPhone ? (
                <ul className="mt-3 space-y-2">
                  {contactEmail && (
                    <li>
                      <a href={`mailto:${contactEmail}`} className={`${linkClass} inline-flex items-center gap-2`}>
                        <Mail size={15} aria-hidden />
                        {contactEmail}
                      </a>
                    </li>
                  )}
                  {contactPhone && (
                    <li>
                      <a href={`tel:${contactPhone.replace(/[^\d+]/g, '')}`} className={`${linkClass} inline-flex items-center gap-2`}>
                        <Phone size={15} aria-hidden />
                        {contactPhone}
                      </a>
                    </li>
                  )}
                </ul>
              ) : (
                <p className="lp-small mt-3 text-lp-subtle">{m.footer.contactPending}</p>
              )}
            </div>
          </div>

          <div className="grid gap-10 sm:grid-cols-3 lg:col-span-8">
            <Column title={m.footer.columns.platform}>
              <li>
                <SectionLink section="platform" className={linkClass}>
                  {m.footer.links.overview}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="demo" className={linkClass}>
                  {m.footer.links.demo}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="personas" className={linkClass}>
                  {m.footer.links.personas}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="benefits" className={linkClass}>
                  {m.footer.links.benefits}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="governance" className={linkClass}>
                  {m.footer.links.governance}
                </SectionLink>
              </li>
            </Column>
            <Column title={m.footer.columns.modules}>
              {ECOSYSTEM.map((cat) => (
                <li key={cat.key}>
                  <SectionLink section="platform" className={linkClass} onNavigate={() => setPlatformCategory(cat.key)}>
                    {m.platform.categories[cat.key].name}
                  </SectionLink>
                </li>
              ))}
            </Column>
            <Column title={m.footer.columns.resources}>
              <li>
                <SectionLink section="about" className={linkClass}>
                  {m.footer.links.about}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="journey" className={linkClass}>
                  {m.footer.links.journey}
                </SectionLink>
              </li>
              <li>
                <SectionLink section="faq" className={linkClass}>
                  {m.footer.links.faq}
                </SectionLink>
              </li>
              <li>
                <button type="button" className={linkClass} onClick={() => openRequest('contact')}>
                  {m.footer.links.contactTeam}
                </button>
              </li>
              <li>
                <Link to={ROUTES.login} className={linkClass}>
                  {m.footer.links.signIn}
                </Link>
              </li>
            </Column>
          </div>
        </div>

        <div className="mt-14 flex flex-col gap-6 border-t border-lp-line pt-8 lg:flex-row lg:items-center lg:justify-between">
          <div className="space-y-2">
            <p className="text-[13px] text-lp-muted">{fill(m.footer.rights, { year: new Date().getFullYear() })}</p>
            <p className="text-[12px] text-lp-subtle">{m.footer.disclaimer}</p>
          </div>
          <div className="flex flex-col gap-5 sm:flex-row sm:flex-wrap sm:items-center">
            <div className="flex gap-5">
              <Link to={LANDING_PATHS.privacy} className="text-[13px] text-lp-muted hover:text-lp-fg">
                {m.footer.privacy}
              </Link>
              <Link to={LANDING_PATHS.terms} className="text-[13px] text-lp-muted hover:text-lp-fg">
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
