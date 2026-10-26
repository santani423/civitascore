import { Link } from 'react-router-dom'
import { ArrowRight, GraduationCap } from 'lucide-react'
import { fill, plural, useLandingI18n } from '../../i18n'
import { isFacultyName, type Faculty } from '../../data/faculties'
import type { ProgramsState } from '../../lib/pmbPrograms'
import { LANDING_PATHS, detailPath } from '../../paths'
import { FacultyCover } from './FacultySection'

/**
 * Directory card: cover, name, the official tagline and dean when supplied,
 * and the study programs the PMB website lists for this faculty (live).
 * The whole card links to the faculty page.
 */
export function FacultyCard({ faculty, programs }: { faculty: Faculty; programs: ProgramsState }) {
  const { m, intl } = useLandingI18n()
  const c = m.faculty.card
  const list = programs.status === 'ready' ? programs.items.filter((p) => isFacultyName(faculty, p.faculty)) : []

  return (
    <article className="group relative flex w-full flex-col overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg">
      <FacultyCover faculty={faculty} className="aspect-[16/9]" />
      <div className="flex flex-1 flex-col p-6">
        <h3 lang="id" className="lp-h3 text-lp-fg [overflow-wrap:anywhere]">
          <Link to={detailPath(LANDING_PATHS.faculties, faculty.slug)} data-card-link className="after:absolute after:inset-0 after:content-[''] focus-visible:outline-none">
            {faculty.name}
          </Link>
        </h3>
        {faculty.tagline && (
          <p lang="id" className="lp-small mt-2 line-clamp-3 text-lp-muted">
            {faculty.tagline}
          </p>
        )}
        {faculty.dean && (
          <p className="mt-3 text-[0.8125rem] text-lp-muted">
            <span className="font-semibold text-lp-fg">{m.faculty.dean.position}:</span> <span lang="id">{faculty.dean.name}</span>
          </p>
        )}
        <div className="mt-4 flex items-start gap-2.5 text-[0.8125rem] text-lp-muted">
          <GraduationCap size={16} aria-hidden className="mt-0.5 shrink-0 text-lp-brand-text" />
          {programs.status === 'loading' ? (
            <span className="mt-1 h-3 w-40 animate-pulse rounded bg-lp-sunken" aria-hidden />
          ) : programs.status === 'error' ? (
            <span>{c.programsUnavailable}</span>
          ) : list.length > 0 ? (
            <span>
              <span className="font-semibold text-lp-fg">{fill(plural(intl, list.length, c.programs), { n: list.length })}</span>
              <span lang="id" className="mt-0.5 block">
                {list.map((p) => p.name).join(' · ')}
              </span>
            </span>
          ) : (
            <span>{c.noPrograms}</span>
          )}
        </div>
        <span aria-hidden className="mt-auto inline-flex items-center gap-1.5 pt-6 text-[0.9063rem] font-semibold text-lp-brand-text">
          {c.cta}
          <ArrowRight size={16} className="transition-transform duration-200 group-hover:translate-x-1" />
        </span>
      </div>
      <span aria-hidden className="pointer-events-none absolute inset-0 rounded-lp-lg ring-lp-ring group-has-[:focus-visible]:ring-2" />
    </article>
  )
}
