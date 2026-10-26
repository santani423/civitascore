import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, ArrowUpRight, Hourglass } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useAppEntry } from '../../hooks/useAppEntry'
import { SERVICES, UPCOMING, type AudienceKey, type ServiceDef } from '../../data/catalog'
import { SECTION_IDS } from '../../paths'
import { TabList, TabPanel } from '../ui/Tabs'
import { Reveal, SectionHeader, StatusPill } from '../ui/Primitives'
import { ServiceDialog } from '../ServiceDialog'

type Filter = 'all' | AudienceKey
const FILTERS: Filter[] = ['all', 'students', 'lecturers', 'staff', 'leaders', 'applicants']
const TABS_ID = 'lp-services'

/** Section D — only services that exist in Civitas Core, filterable by who uses them. */
export function ServicesSection() {
  const { m } = useLandingI18n()
  const [filter, setFilter] = useState<Filter>('all')
  const [open, setOpen] = useState<ServiceDef | null>(null)
  const visible = filter === 'all' ? SERVICES : SERVICES.filter((s) => s.audiences.includes(filter))

  return (
    <section id={SECTION_IDS.services} aria-labelledby="services-title" className="lp-section bg-lp-canvas">
      <div className="lp-container">
        <SectionHeader id="services-title" eyebrow={m.services.eyebrow} title={m.services.title} description={m.services.description} />
        <Reveal className="mt-8 flex min-w-0 flex-col gap-3 md:flex-row md:items-center md:gap-4">
          <p aria-hidden className="shrink-0 text-[13.5px] font-medium text-lp-muted">
            {m.services.filterLabel}
          </p>
          {/* On narrow screens the chips scroll sideways; the faded edge hints there is more. */}
          <TabList
            items={FILTERS.map((key) => ({ key, label: m.services.filters[key] }))}
            value={filter}
            onChange={setFilter}
            label={m.services.filterLabel}
            idPrefix={TABS_ID}
            className="min-w-0 self-start [mask-image:linear-gradient(to_right,#000_88%,transparent)] lg:[mask-image:none]"
          />
        </Reveal>

        <TabPanel idPrefix={TABS_ID} tabKey={filter} className="mt-10">
          <p className="sr-only" aria-live="polite">
            {fill(m.services.countLabel, { n: visible.length })}
          </p>
          <ul key={filter} className="lp-tab-enter grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {visible.map((service) => (
              <li key={service.key} className="flex">
                <ServiceCard service={service} onDetails={() => setOpen(service)} />
              </li>
            ))}
          </ul>
        </TabPanel>

        <Reveal className="mt-8">
          <div className="flex flex-col gap-4 rounded-lp border border-dashed border-lp-strong bg-lp-surface/60 p-5 sm:flex-row sm:items-start sm:gap-5 sm:p-6">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-lp-gold/10 text-lp-gold-text">
              <Hourglass size={18} aria-hidden />
            </span>
            <div>
              <p className="flex flex-wrap items-center gap-2.5">
                <span className="lp-h4 text-lp-fg">{m.services.upcomingTitle}</span>
                <StatusPill status="phased" compact />
              </p>
              <p className="lp-small mt-1.5 text-lp-muted">{m.services.upcomingBody}</p>
              <ul className="mt-3 flex flex-wrap gap-2">
                {UPCOMING.map((key) => (
                  <li key={key} className="rounded-full border border-lp-line bg-lp-surface px-3 py-1 text-[13px] font-medium text-lp-muted">
                    {m.services.upcoming[key]}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </Reveal>
      </div>

      <ServiceDialog service={open} onClose={() => setOpen(null)} />
    </section>
  )
}

function ServiceCard({ service, onDetails }: { service: ServiceDef; onDetails: () => void }) {
  const { m } = useLandingI18n()
  const entry = useAppEntry()
  const copy = m.services.items[service.key]
  const Icon = service.icon
  const pmb = service.access.kind === 'pmb'

  return (
    <article className="group flex w-full flex-col rounded-lp border border-lp-line bg-lp-surface p-6 shadow-lp-sm transition-[box-shadow,border-color,transform] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg">
      <div className="flex items-start justify-between gap-4">
        <span className="flex h-12 w-12 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text transition-colors duration-300 group-hover:bg-lp-brand group-hover:text-lp-onbrand">
          <Icon size={22} aria-hidden />
        </span>
        <StatusPill status={service.status} compact />
      </div>
      <h3 className="lp-h3 mt-5 text-lp-fg">{copy.name}</h3>
      <p className="lp-small mt-2 flex-1 text-lp-muted">{copy.summary}</p>
      <p className="mt-4 flex flex-wrap gap-1.5">
        {service.audiences.map((a) => (
          <span key={a} className="rounded-full bg-lp-sunken px-2.5 py-0.5 text-[11.5px] font-medium text-lp-muted">
            {m.services.filters[a]}
          </span>
        ))}
      </p>
      <div className="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-lp-line pt-4">
        <button
          type="button"
          onClick={onDetails}
          aria-label={fill(m.services.detailsFor, { name: copy.name })}
          aria-haspopup="dialog"
          className="inline-flex items-center gap-1.5 rounded-lp-sm text-[13.5px] font-semibold text-lp-muted transition-colors hover:text-lp-fg"
        >
          {m.services.details}
        </button>
        <Link
          to={service.access.kind === 'pmb' ? service.access.to : entry.to}
          className="inline-flex items-center gap-1.5 rounded-lp-sm text-[13.5px] font-semibold text-lp-brand-text transition-colors hover:text-lp-brand-hover"
        >
          {pmb ? m.services.accessPmb : entry.authenticated ? m.nav.openDashboard : m.services.accessLogin}
          {pmb ? <ArrowUpRight size={15} aria-hidden /> : <ArrowRight size={15} aria-hidden />}
        </Link>
      </div>
    </article>
  )
}
