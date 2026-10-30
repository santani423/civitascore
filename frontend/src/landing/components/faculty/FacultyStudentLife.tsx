import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Org, ResolvedActivity } from '../../data/student-affairs'
import { LANDING_PATHS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { ActivityCard, OrgCard } from '../student-affairs/Cards'
import { DemoNotice } from '../student-affairs/PageHero'
import { EmptyNote, FacultySection } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

function SeeAll({ to, label }: { to: string; label: string }) {
  return (
    <Link to={to} className="group inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text">
      {label}
      <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
    </Link>
  )
}

/**
 * Sample Ormawa whose (sample) faculty is this one — from the Ormawa
 * directory's data, linking to their profiles. The demo notice here covers
 * the Ormawa, UKM, activity and achievement sections below it.
 */
export function FacultyOrmawaSection({ facultySlug, ormawa, tone }: { facultySlug: string; ormawa: Org[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const o = m.faculty.ormawa
  return (
    <FacultySection
      id={FACULTY_SECTION_IDS.ormawa}
      tone={tone}
      eyebrow={m.faculty.studentLife.eyebrow}
      title={o.title}
      description={o.lead}
      action={<SeeAll to={`${LANDING_PATHS.ormawa}?fakultas=${facultySlug}`} label={o.all} />}
    >
      <DemoNotice className="mb-8" />
      {ormawa.length > 0 ? (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
          {ormawa.map((org) => (
            <li key={org.slug} className="flex">
              <OrgCard org={org} ctaLabel={o.cta} />
            </li>
          ))}
        </ul>
      ) : (
        <EmptyNote text={o.empty}>
          <Link to={LANDING_PATHS.ormawa} className={buttonClass('secondary', 'sm', 'shrink-0')}>
            {m.affairs.ormawa.relatedAll}
          </Link>
        </EmptyNote>
      )}
    </FacultySection>
  )
}

/** UKM are university-wide: this shows a few sample units students of this faculty might join, and says they're open to everyone. */
export function FacultyUkmSection({ ukm, tone }: { ukm: Org[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const u = m.faculty.ukm
  return (
    <FacultySection id={FACULTY_SECTION_IDS.ukm} tone={tone} eyebrow={m.faculty.studentLife.eyebrow} title={u.title} description={u.lead} action={<SeeAll to={LANDING_PATHS.ukm} label={u.all} />}>
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        {ukm.map((org) => (
          <li key={org.slug} className="flex">
            <OrgCard org={org} />
          </li>
        ))}
      </ul>
    </FacultySection>
  )
}

/** Sample activities run by this faculty's sample Ormawa — the only activity–faculty link the data has. */
export function FacultyActivitiesSection({ activities, tone }: { activities: ResolvedActivity[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const a = m.faculty.activities
  return (
    <FacultySection
      id={FACULTY_SECTION_IDS.activities}
      tone={tone}
      eyebrow={m.faculty.studentLife.eyebrow}
      title={a.title}
      description={a.lead}
      action={<SeeAll to={LANDING_PATHS.activities} label={a.all} />}
    >
      {activities.length > 0 ? (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
          {activities.map((activity) => (
            <li key={activity.slug} className="flex">
              <ActivityCard activity={activity} />
            </li>
          ))}
        </ul>
      ) : (
        <EmptyNote text={a.empty}>
          <Link to={LANDING_PATHS.activities} className={buttonClass('secondary', 'sm', 'shrink-0')}>
            {a.all}
          </Link>
        </EmptyNote>
      )}
    </FacultySection>
  )
}
