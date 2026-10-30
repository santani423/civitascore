import { Link } from 'react-router-dom'
import { ArrowRight, BookOpenCheck, Building2, CalendarDays, CheckCircle2, Clock, HeartHandshake, MapPin, Send, UsersRound } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { openingsAt, type Company, type OpportunityType, type ResolvedOpportunity } from '../../data/alumni'
import { opportunityPath } from '../../lib/alumniPaths'
import { useAlumniStore } from '../../store/alumni'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { Dialog } from '../ui/Overlay'
import { DemoBadge } from '../student-affairs/Badges'
import { BulletList, InfoItem, InfoList, Paragraphs } from '../student-affairs/Detail'
import { OPPORTUNITY_ICON } from './opportunityIcons'
import { CompanyLogo, FavoriteButton, Skeleton, SkeletonFrame } from './Shared'

function TypePill({ type }: { type: OpportunityType }) {
  const { m } = useLandingI18n()
  const Icon = OPPORTUNITY_ICON[type]
  return (
    <span className="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-lp-brand-soft px-2.5 py-1 text-[0.75rem] font-semibold leading-none text-lp-brand-text">
      <Icon size={13} aria-hidden />
      {m.alumni.careers.types[type]}
    </span>
  )
}

function AppliedPill() {
  const { m } = useLandingI18n()
  return (
    <span className="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-lp-ok/10 px-2.5 py-1 text-[0.75rem] font-semibold leading-none text-lp-ok ring-1 ring-inset ring-lp-ok/25">
      <CheckCircle2 size={13} aria-hidden />
      {m.alumni.careers.applied}
    </span>
  )
}

/** Company, logo, position, location, work type, publication date and "Lihat Detail". */
export function OpportunityCard({ item, onOpen }: { item: ResolvedOpportunity; onOpen: () => void }) {
  const { m, fmt } = useLandingI18n()
  const c = m.alumni.careers
  const applied = useAlumniStore((s) => s.applications.includes(item.slug))
  return (
    <article className="group relative flex h-full w-full flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg sm:p-6">
      <div className="flex items-start gap-4">
        <CompanyLogo company={item.employer} />
        <div className="min-w-0 flex-1 pr-10">
          <p lang="id" className="text-[0.875rem] font-medium text-lp-muted">
            {item.employer.name}
          </p>
          <h3 lang="id" className="lp-h4 mt-0.5 text-lp-fg [overflow-wrap:anywhere]">
            {item.title}
          </h3>
        </div>
      </div>
      <FavoriteButton id={`opportunity:${item.slug}`} name={item.title} className="absolute right-3 top-3 shadow-none ring-0" />
      <div className="mt-3 flex flex-wrap items-center gap-1.5">
        <TypePill type={item.type} />
        {applied && <AppliedPill />}
      </div>
      <p lang="id" className="lp-small mt-3 line-clamp-2 text-lp-muted">
        {item.excerpt}
      </p>
      <dl className="mt-4 flex flex-wrap gap-x-5 gap-y-1.5 text-[0.8125rem] text-lp-muted">
        <div>
          <dt className="sr-only">{c.location}</dt>
          <dd lang="id" className="flex items-center gap-1.5">
            <MapPin size={14} aria-hidden className="shrink-0 text-lp-subtle" />
            {item.location}
          </dd>
        </div>
        <div>
          <dt className="sr-only">{c.workType}</dt>
          <dd className="flex items-center gap-1.5">
            <Clock size={14} aria-hidden className="shrink-0 text-lp-subtle" />
            {c.workTypes[item.workType]}
          </dd>
        </div>
        <div>
          <dt className="sr-only">{c.posted}</dt>
          <dd className="flex items-center gap-1.5">
            <CalendarDays size={14} aria-hidden className="shrink-0 text-lp-subtle" />
            <time dateTime={item.postedAt.toISOString()}>{fmt.date(item.postedAt)}</time>
          </dd>
        </div>
      </dl>
      <div className="mt-auto pt-5">
        <Button variant="secondary" className="group/btn w-full sm:w-auto" onClick={onOpen} aria-label={`${c.viewDetail}: ${item.title}`}>
          {c.viewDetail}
          <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover/btn:translate-x-1" />
        </Button>
      </div>
    </article>
  )
}

export function OpportunityRow({ item }: { item: ResolvedOpportunity }) {
  const { m, fmt } = useLandingI18n()
  return (
    <Link to={opportunityPath(item.slug)} className="group flex items-center gap-4 border-b border-lp-line py-4 last:border-0">
      <CompanyLogo company={item.employer} />
      <div className="min-w-0 flex-1">
        <p lang="id" className="text-[0.9375rem] font-semibold leading-snug text-lp-fg group-hover:text-lp-brand-text">
          {item.title}
        </p>
        <p className="mt-0.5 text-[0.8125rem] text-lp-muted">
          <span lang="id">{item.employer.name}</span> · <span lang="id">{item.location}</span>
        </p>
        <p className="mt-0.5 text-[0.75rem] text-lp-subtle">
          {m.alumni.careers.types[item.type]} · {fmt.date(item.postedAt)}
        </p>
      </div>
      <ArrowRight size={17} aria-hidden className="shrink-0 text-lp-subtle transition-transform duration-200 group-hover:translate-x-1" />
    </Link>
  )
}

/** Partner company. `onSelect` narrows the list above to its openings. */
export function CompanyCard({ company, onSelect }: { company: Company; onSelect: () => void }) {
  const { m, fmt } = useLandingI18n()
  const c = m.alumni.careers
  const openings = openingsAt(company)
  return (
    <button
      type="button"
      onClick={onSelect}
      aria-label={fill(c.companyAction, { name: company.name })}
      className="group flex h-full w-full flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-5 text-left shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-md"
    >
      <span className="flex items-center gap-3">
        <CompanyLogo company={company} />
        <span className="min-w-0">
          <span lang="id" className="block text-[0.9375rem] font-semibold leading-snug text-lp-fg group-hover:text-lp-brand-text">
            {company.name}
          </span>
          <span className="mt-0.5 block text-[0.8125rem] text-lp-subtle">{m.alumni.industries[company.industry]}</span>
        </span>
      </span>
      <span lang="id" className="lp-small mt-3 block text-lp-muted">
        {company.blurb}
      </span>
      <span className="mt-auto flex flex-wrap gap-x-4 gap-y-1 pt-4 text-[0.8125rem] font-medium text-lp-muted">
        <span className="inline-flex items-center gap-1.5">
          <UsersRound size={14} aria-hidden className="text-lp-subtle" />
          {fill(c.alumniAt, { n: fmt.number(company.alumniCount) })}
        </span>
        <span className="inline-flex items-center gap-1.5">
          <Building2 size={14} aria-hidden className="text-lp-subtle" />
          {fill(c.openings, { n: openings })}
        </span>
      </span>
    </button>
  )
}

export function OpportunityGridSkeleton({ count = 6 }: { count?: number }) {
  return (
    <SkeletonFrame className="grid gap-5 md:grid-cols-2">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="rounded-lp-lg border border-lp-line bg-lp-surface p-5 sm:p-6">
          <div className="flex gap-4">
            <Skeleton className="h-12 w-12 shrink-0 rounded-lp" />
            <div className="flex-1 space-y-2">
              <Skeleton className="h-4 w-1/2" />
              <Skeleton className="h-5 w-4/5" />
            </div>
          </div>
          <Skeleton className="mt-4 h-4 w-full" />
          <Skeleton className="mt-2 h-4 w-3/4" />
          <Skeleton className="mt-5 h-11 w-36" />
        </div>
      ))}
    </SkeletonFrame>
  )
}

/** Opportunity details. Applying is simulated: it only marks the card in this browser. */
export function OpportunityDialog({ item, onClose }: { item: ResolvedOpportunity | null; onClose: () => void }) {
  const { m, fmt } = useLandingI18n()
  const c = m.alumni.careers
  const applied = useAlumniStore((s) => (item ? s.applications.includes(item.slug) : false))
  const apply = useAlumniStore((s) => s.apply)
  const toast = useLandingUi((s) => s.toast)
  if (!item) return null

  return (
    <Dialog
      open
      onClose={onClose}
      size="lg"
      icon={<CompanyLogo company={item.employer} />}
      title={<span lang="id">{item.title}</span>}
      description={<span lang="id">{item.employer.name}</span>}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>
            {m.common.close}
          </Button>
          <Button
            disabled={applied}
            onClick={() => {
              apply(item.slug)
              toast(fill(c.appliedToast, { title: item.title }))
            }}
          >
            {applied ? <CheckCircle2 size={16} aria-hidden /> : <Send size={16} aria-hidden />}
            {applied ? c.applied : c.apply}
          </Button>
        </>
      }
    >
      <div className="flex flex-wrap items-center gap-2">
        <TypePill type={item.type} />
        <DemoBadge />
      </div>
      <InfoList className="mt-5">
        <InfoItem icon={MapPin} label={c.location}>
          <span lang="id">{item.location}</span>
        </InfoItem>
        <InfoItem icon={Clock} label={c.workType}>
          {c.workTypes[item.workType]}
        </InfoItem>
        <InfoItem icon={CalendarDays} label={c.posted}>
          {fmt.date(item.postedAt, { day: 'numeric', month: 'long', year: 'numeric' })} — {fill(c.closes, { date: fmt.date(item.closesAt, { day: 'numeric', month: 'long', year: 'numeric' }) })}
        </InfoItem>
      </InfoList>
      <div className="mt-6">
        <Paragraphs items={item.description} />
      </div>
      <div className="mt-6 grid gap-6 sm:grid-cols-2">
        <div>
          <h3 className="lp-h4 flex items-center gap-2 text-lp-fg">
            <BookOpenCheck size={17} aria-hidden className="text-lp-brand-text" />
            {c.requirements}
          </h3>
          <BulletList items={item.requirements} className="mt-3" />
        </div>
        <div>
          <h3 className="lp-h4 flex items-center gap-2 text-lp-fg">
            <HeartHandshake size={17} aria-hidden className="text-lp-brand-text" />
            {c.benefits}
          </h3>
          <BulletList items={item.benefits} className="mt-3" />
        </div>
      </div>
      <p className="mt-6 rounded-lp-sm bg-lp-sunken px-3.5 py-3 text-[0.8125rem] leading-relaxed text-lp-muted">{c.simulationNote}</p>
    </Dialog>
  )
}
