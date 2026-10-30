import { Link } from 'react-router-dom'
import { Building2, CalendarCheck, MapPin } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { facultyName } from '../../data/faculties'
import type { Alumnus } from '../../data/alumni'
import { alumnusPath } from '../../lib/alumniPaths'
import { Portrait } from './Portrait'
import { FavoriteButton, MentorBadge, ProfessionalBadge, Skeleton, SkeletonFrame } from './Shared'

/**
 * Directory card: portrait on top from `sm` up; on phones it sits beside the text
 * so a single column stays scannable without very tall cards. The name is a
 * stretched link; the favorite button stays reachable above it.
 */
export function ProfileCard({ person, state, headingLevel: Heading = 'h3' }: { person: Alumnus; state?: { from?: string }; headingLevel?: 'h2' | 'h3' }) {
  const { m } = useLandingI18n()
  const c = m.alumni.card
  return (
    <article className="group relative flex w-full overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg sm:flex-col">
      <Portrait person={person} className="w-[6.75rem] shrink-0 sm:aspect-[1/0.95] sm:w-full" imageClassName="transition-transform duration-500 ease-out group-hover:scale-[1.04]" />
      <div className="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
        <div className="flex flex-wrap items-center gap-1.5 pr-10 sm:pr-0">
          <ProfessionalBadge status={person.status} />
          {person.mentor && <MentorBadge />}
        </div>
        <Heading lang="id" className="lp-h4 mt-2.5 text-lp-fg [overflow-wrap:anywhere]">
          <Link to={alumnusPath(person.slug)} state={state} data-card-link className="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none">
            {person.name}
          </Link>
        </Heading>
        <p lang="id" className="mt-0.5 text-[0.8125rem] text-lp-subtle">
          {person.degree}
        </p>
        <p lang="id" className="mt-2.5 text-[0.875rem] font-semibold leading-snug text-lp-fg">
          {person.position}
        </p>
        <dl className="mt-3 space-y-1.5 text-[0.8125rem] leading-snug text-lp-muted">
          <div>
            <dt className="sr-only">{c.company}</dt>
            <dd lang="id" className="flex items-start gap-2">
              <Building2 size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              {person.company}
            </dd>
          </div>
          <div>
            <dt className="sr-only">{c.location}</dt>
            <dd lang="id" className="flex items-start gap-2">
              <MapPin size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              {person.location}
            </dd>
          </div>
          <div>
            <dt className="sr-only">{c.education}</dt>
            <dd className="flex items-start gap-2">
              <CalendarCheck size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              <span>
                <span lang="id">{person.program}</span> · <span lang="id">{facultyName(person.faculty).replace('Fakultas ', '')}</span> · <span className="font-semibold text-lp-fg">{person.gradYear}</span>
              </span>
            </dd>
          </div>
        </dl>
      </div>
      <FavoriteButton id={`alumni:${person.slug}`} name={person.name} className="absolute right-2.5 top-2.5" />
      <span aria-hidden className="pointer-events-none absolute inset-0 rounded-lp-lg ring-lp-ring group-has-[a:focus-visible]:ring-2" />
    </article>
  )
}

export function ProfileGridSkeleton({ count = 12 }: { count?: number }) {
  return (
    <SkeletonFrame className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="flex overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface sm:flex-col">
          <Skeleton className="w-[6.75rem] shrink-0 rounded-none sm:aspect-[1/0.95] sm:w-full" />
          <div className="flex-1 space-y-3 p-4 sm:p-5">
            <Skeleton className="h-5 w-24 rounded-full" />
            <Skeleton className="h-5 w-4/5" />
            <Skeleton className="h-4 w-3/5" />
            <Skeleton className="h-4 w-full" />
          </div>
        </div>
      ))}
    </SkeletonFrame>
  )
}
