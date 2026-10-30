import type { ReactNode } from 'react'
import { ArrowRight, ArrowUpRight, CalendarClock, Mail, MapPin, MessageCircle, Phone, type LucideIcon } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { BKK_CONTACT, PILLARS } from '../../data/pillars'
import { activityTarget } from '../../lib/activities'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { SectionLink } from '../layout/SectionLink'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { ActivityLink } from './ActivityLink'
import { PillarImage } from './PillarImage'

/** Closing section: how the pillars connect to campus activities, and who to contact (BKK). */
export function ActivitiesSection() {
  const { m } = useLandingI18n()
  const c = m.studentLife.connect
  const target = activityTarget()

  return (
    <section id={STUDENT_LIFE_IDS.activities} aria-labelledby="slj-activities-title" className="lp-section lp-warm">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-16">
        <Reveal className="lg:col-span-6 lg:pt-4">
          <p className="lp-eyebrow text-lp-brand-text">{c.eyebrow}</p>
          <h2 id="slj-activities-title" className="lp-h2 mt-3 text-lp-fg">
            {c.title}
          </h2>
          <p className="lp-lead mt-5 text-lp-muted">{c.body}</p>

          {/* The five official icons, each a shortcut back to its pillar — same URLs as the sections above, so no extra download. */}
          <ul className="mt-8 flex flex-wrap gap-2">
            {PILLARS.map((pillar) => (
              <li key={pillar.id}>
                <SectionLink
                  section={pillar.id}
                  path={LANDING_PATHS.studentLife}
                  className="group flex rounded-lp bg-lp-surface/60 ring-1 ring-inset ring-lp-line transition-[background-color,box-shadow] hover:bg-lp-surface hover:ring-lp-brand/30"
                >
                  <PillarImage pillar={pillar} alt="" size="sm" className="h-16 w-16 transition-transform duration-300 group-hover:scale-105" />
                  <span lang="id" className="sr-only">
                    {pillar.title}
                  </span>
                </SectionLink>
              </li>
            ))}
          </ul>

          {target ? (
            <ActivityLink target={target} className={buttonClass('primary', 'lg', 'group mt-10')}>
              {m.studentLife.cta.primary}
              <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </ActivityLink>
          ) : (
            <div className="mt-10 max-w-xl rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface/70 p-5 sm:p-6">
              <p className="flex items-center gap-2.5 text-[0.9375rem] font-semibold text-lp-fg">
                <CalendarClock size={18} aria-hidden className="shrink-0 text-lp-gold-text" />
                {c.pendingTitle}
              </p>
              <p className="lp-small mt-2 text-lp-muted">{c.pendingBody}</p>
            </div>
          )}
        </Reveal>

        <Reveal delay={80} className="lg:col-span-6">
          <BkkContact />
        </Reveal>
      </div>
    </section>
  )
}

const linkClass = 'font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text hover:decoration-lp-brand-text'

function ContactRow({ icon: Icon, label, children }: { icon: LucideIcon; label: string; children: ReactNode }) {
  return (
    <div className="relative py-4 pl-14">
      <dt className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">
        <span aria-hidden className="absolute left-0 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
          <Icon size={18} />
        </span>
        {label}
      </dt>
      <dd className="mt-1 text-[0.9375rem] leading-relaxed text-lp-fg">{children}</dd>
    </div>
  )
}

/** BKK's channels exactly as the source page lists them. */
function BkkContact() {
  const { m } = useLandingI18n()
  const k = m.studentLife.contact
  const newTab = <span className="sr-only"> {k.newTab}</span>

  return (
    <section id={STUDENT_LIFE_IDS.contact} aria-labelledby="slj-contact-title" className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md sm:p-8">
      <p className="lp-eyebrow text-lp-brand-text">{k.eyebrow}</p>
      <h3 id="slj-contact-title" className="lp-h3 mt-2 text-[1.5rem] text-lp-fg">
        {k.title}
      </h3>
      <p className="lp-small mt-2 text-lp-muted">{k.body}</p>

      <dl className="mt-6 divide-y divide-lp-line border-y border-lp-line">
        <ContactRow icon={MapPin} label={k.address}>
          <address className="not-italic">{BKK_CONTACT.address}</address>
        </ContactRow>
        <ContactRow icon={MessageCircle} label={k.whatsapp}>
          <a href={BKK_CONTACT.whatsapp.href} target="_blank" rel="noopener noreferrer" className={linkClass}>
            {BKK_CONTACT.whatsapp.label}
            {newTab}
          </a>
        </ContactRow>
        <ContactRow icon={Phone} label={k.phone}>
          {BKK_CONTACT.phone.areaCode}{' '}
          {BKK_CONTACT.phone.numbers.map((n, i) => (
            <span key={n.href}>
              {i > 0 && ', '}
              <a href={n.href} className={linkClass}>
                {n.label}
              </a>
            </span>
          ))}{' '}
          {BKK_CONTACT.phone.extension}
        </ContactRow>
        <ContactRow icon={Mail} label={k.email}>
          <a href={`mailto:${BKK_CONTACT.email}`} className={`${linkClass} break-all`}>
            {BKK_CONTACT.email}
          </a>
        </ContactRow>
      </dl>

      <h4 className="mt-6 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{k.social}</h4>
      <ul className="mt-3 flex flex-wrap gap-2">
        {BKK_CONTACT.socials.map((social) => (
          <li key={social.href}>
            <a
              href={social.href}
              target="_blank"
              rel="noopener noreferrer"
              className="group inline-flex min-h-11 items-center gap-1.5 rounded-full border border-lp-strong px-4 text-[0.875rem] font-semibold text-lp-fg transition-colors hover:border-lp-brand/40 hover:text-lp-brand-text"
            >
              {social.name}
              <ArrowUpRight size={15} aria-hidden className="transition-transform duration-200 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
              {newTab}
            </a>
          </li>
        ))}
      </ul>
    </section>
  )
}
