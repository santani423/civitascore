import { Link } from 'react-router-dom'
import { ArrowRight, CalendarDays, MapPin, UsersRound } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { activityStatus, organizerOf, type Org, type ResolvedActivity } from '../../data/student-affairs'
import { facultyName } from '../../data/faculties'
import { detailPath, LANDING_PATHS, ORG_LIST_PATHS } from '../../paths'
import { formatActivityDate, formatClock } from '../../lib/affairsFormat'
import { CategoryBadge, DemoBadge, StatusBadge } from './Badges'
import { CoverArt, OrgLogo } from './Covers'
import { ACTIVITY_STYLE, TONE, orgStyle } from './styles'

/** Passed as router state so a detail page's "back" link returns to the same filtered list. */
export interface ListState {
  from?: string
}

const cardClass =
  'group relative flex w-full flex-col overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg'
/** Stretched link: the whole card is clickable, the heading stays the accessible name. */
const stretchedLink = "after:absolute after:inset-0 after:content-[''] focus-visible:outline-none"

function CardFooter({ label }: { label?: string }) {
  const { m } = useLandingI18n()
  return (
    <div className="mt-auto flex items-center justify-between gap-3 pt-5">
      <span aria-hidden className="inline-flex items-center gap-1.5 text-[0.875rem] font-semibold text-lp-brand-text">
        {label ?? m.affairs.card.viewDetail}
        <ArrowRight size={16} className="transition-transform duration-200 group-hover:translate-x-1" />
      </span>
      <DemoBadge />
    </div>
  )
}

/** Visible focus ring for the stretched link. */
function FocusRing() {
  return <span aria-hidden className="pointer-events-none absolute inset-0 rounded-lp-lg ring-lp-ring group-has-[:focus-visible]:ring-2" />
}

export function ActivityCard({ activity, state, headingLevel: Heading = 'h3' }: { activity: ResolvedActivity; state?: ListState; headingLevel?: 'h2' | 'h3' }) {
  const { m, intl, locale } = useLandingI18n()
  const a = m.affairs
  const organizer = organizerOf(activity)

  return (
    <article className={cardClass}>
      <div className="relative">
        <CoverArt style={ACTIVITY_STYLE[activity.category]} photo={activity.photos?.[0]} className="aspect-[16/9]" />
        <div className="absolute inset-x-3 top-3 flex flex-wrap items-start justify-between gap-2">
          <CategoryBadge label={a.categories.activity[activity.category]} />
          <StatusBadge status={activityStatus(activity)} />
        </div>
      </div>
      <div className="flex flex-1 flex-col p-5">
        <p className="flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.8125rem] font-semibold text-lp-brand-text">
          <CalendarDays size={14} aria-hidden className="shrink-0" />
          <time dateTime={activity.startsAt.toISOString()}>{formatActivityDate(activity, intl)}</time>
          <span aria-hidden>·</span>
          <span>{fill(a.activity.timeRange, { start: formatClock(activity.start.time, locale), end: formatClock(activity.end.time, locale) })}</span>
        </p>
        <Heading lang="id" className="lp-h4 mt-2.5 text-lp-fg [overflow-wrap:anywhere]">
          <Link to={detailPath(LANDING_PATHS.activities, activity.slug)} state={state} data-card-link className={stretchedLink}>
            {activity.title}
          </Link>
        </Heading>
        <p lang="id" className="lp-small mt-2 line-clamp-3 text-lp-muted">
          {activity.excerpt}
        </p>
        <dl className="mt-4 space-y-1.5 text-[0.8125rem] text-lp-muted">
          <div>
            <dt className="sr-only">{a.activity.venue}</dt>
            <dd lang="id" className="flex items-start gap-2">
              <MapPin size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              {activity.venue}
            </dd>
          </div>
          {organizer && (
            <div>
              <dt className="sr-only">{a.activity.organizer}</dt>
              <dd lang="id" className="flex items-start gap-2">
                <UsersRound size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
                {organizer.name}
              </dd>
            </div>
          )}
        </dl>
        <CardFooter />
      </div>
      <FocusRing />
    </article>
  )
}

/** Ormawa or UKM card: monogram/logo, name, category, affiliation, introduction and focus tags. */
/** `ctaLabel` replaces the visual "Lihat Detail" (e.g. "Lihat Organisasi" on faculty pages); the link name stays the org name. */
export function OrgCard({ org, state, headingLevel: Heading = 'h3', ctaLabel }: { org: Org; state?: ListState; headingLevel?: 'h2' | 'h3'; ctaLabel?: string }) {
  const { m } = useLandingI18n()
  const a = m.affairs
  const category = org.kind === 'ormawa' ? a.categories.ormawa[org.category] : a.categories.ukm[org.category]
  const faculty = org.kind === 'ormawa' && org.faculty ? facultyName(org.faculty) : null

  return (
    <article className={cn(cardClass, 'p-5 sm:p-6')}>
      <span aria-hidden className={cn('absolute inset-x-0 top-0 h-1', TONE[orgStyle(org).tone].surface)} />
      <div className="flex items-start gap-4">
        <OrgLogo org={org} />
        <div className="min-w-0 flex-1">
          <CategoryBadge label={category} className="bg-lp-brand-soft text-lp-brand-text ring-transparent" />
          <Heading lang="id" className="lp-h4 mt-2 text-lp-fg [overflow-wrap:anywhere]">
            <Link to={detailPath(ORG_LIST_PATHS[org.kind], org.slug)} state={state} data-card-link className={stretchedLink}>
              {org.name}
            </Link>
          </Heading>
          {org.kind === 'ormawa' && (
            <p lang={faculty ? 'id' : undefined} className="mt-1 text-[0.8125rem] text-lp-subtle">
              {faculty ?? a.org.universityLevel}
            </p>
          )}
        </div>
      </div>
      <p lang="id" className="lp-small mt-4 line-clamp-3 text-lp-muted">
        {org.excerpt}
      </p>
      <ul aria-label={a.org.focus} className="mt-4 flex flex-wrap gap-1.5">
        {org.focus.slice(0, 3).map((tag) => (
          <li key={tag} lang="id" className="rounded-full bg-lp-sunken px-2.5 py-1 text-[0.75rem] font-medium text-lp-muted">
            {tag}
          </li>
        ))}
      </ul>
      <CardFooter label={ctaLabel} />
      <FocusRing />
    </article>
  )
}
