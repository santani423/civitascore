import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, BadgeCheck, GraduationCap } from 'lucide-react'
import { PMB, to } from '@/pmb/routes'
import { fill, plural, useLandingI18n } from '../../i18n'
import type { StudyProgram, ProgramsState } from '../../lib/pmbPrograms'
import { buttonClass } from '../ui/buttonClass'
import { ChipGroup } from '../student-affairs/Filters'
import { FacultySection, PendingInfo } from './FacultySection'
import { FACULTY_SECTION_IDS, useDegreeLabel } from './styles'

function ProgramCard({ program }: { program: StudyProgram }) {
  const { m } = useLandingI18n()
  const p = m.faculty.programs
  const degreeLabel = useDegreeLabel()

  return (
    <article className="flex h-full flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-6 shadow-lp-sm">
      <div className="flex items-start justify-between gap-3">
        <span aria-hidden className="flex h-12 w-12 items-center justify-center rounded-lp bg-lp-brand-soft text-lp-brand-text">
          <GraduationCap size={22} />
        </span>
        <span className="rounded-full bg-lp-sunken px-2.5 py-1 text-[0.75rem] font-semibold text-lp-muted">{degreeLabel(program.degree_level)}</span>
      </div>
      <h3 lang="id" className="lp-h4 mt-5 text-lp-fg">
        {program.name}
      </h3>
      {program.summary && (
        <p lang="id" className="lp-small mt-2 line-clamp-3 text-lp-muted">
          {program.summary}
        </p>
      )}
      {program.accreditation && (
        <p className="mt-4 inline-flex items-center gap-1.5 text-[0.8125rem] font-semibold text-lp-fg">
          <BadgeCheck size={16} aria-hidden className="text-lp-ok" />
          {p.accreditation}: <span lang="id">{program.accreditation}</span>
        </p>
      )}
      <div className="mt-auto pt-6">
        <Link to={to(PMB.program, { slug: program.slug })} className={buttonClass('secondary', 'sm', 'group')}>
          {p.detail}
          <span className="sr-only">: {program.name}</span>
          <ArrowRight size={15} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
        </Link>
      </div>
    </article>
  )
}

function ProgramSkeleton({ label }: { label: string }) {
  return (
    <div>
      <span className="sr-only">{label}</span>
      <ul aria-hidden className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        {[0, 1, 2].map((i) => (
          <li key={i} className={i === 2 ? 'hidden rounded-lp-lg border border-lp-line bg-lp-surface p-6 lg:block' : 'rounded-lp-lg border border-lp-line bg-lp-surface p-6'}>
            <div className="h-12 w-12 animate-pulse rounded-lp bg-lp-sunken" />
            <div className="mt-5 h-4 w-2/3 animate-pulse rounded bg-lp-sunken" />
            <div className="mt-3 h-3 w-full animate-pulse rounded bg-lp-sunken" />
            <div className="mt-2 h-3 w-4/5 animate-pulse rounded bg-lp-sunken" />
          </li>
        ))}
      </ul>
    </div>
  )
}

/**
 * "Program Studi": the faculty's programs as listed on the PMB website
 * (`programs` already matched to this faculty). Nothing is filled in by
 * hand: no list from PMB means an empty state, not a guessed one. The level
 * filter appears only when the faculty has more than one level.
 */
export function StudyProgramGrid({ state, programs, tone }: { state: ProgramsState['status']; programs: StudyProgram[]; tone?: 'canvas' | 'warm' }) {
  const { m, intl } = useLandingI18n()
  const p = m.faculty.programs
  const degreeLabel = useDegreeLabel()
  const [level, setLevel] = useState('')
  const levels = [...new Set(programs.map((x) => x.degree_level))]
  const shown = level && levels.includes(level) ? programs.filter((x) => x.degree_level === level) : programs
  const allPrograms = (
    <Link to={PMB.programs} className="group inline-flex min-h-11 items-center gap-2 text-[0.9063rem] font-semibold text-lp-brand-text">
      {p.all}
      <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
    </Link>
  )

  return (
    <FacultySection id={FACULTY_SECTION_IDS.programs} tone={tone} eyebrow={p.eyebrow} title={p.title} description={p.lead} action={allPrograms}>
      {state === 'loading' ? (
        <ProgramSkeleton label={p.loading} />
      ) : state === 'error' ? (
        <PendingInfo icon={GraduationCap} title={p.errorTitle} body={p.errorBody}>
          <Link to={PMB.programs} className={buttonClass('secondary', 'sm')}>
            {p.all}
          </Link>
        </PendingInfo>
      ) : programs.length === 0 ? (
        <PendingInfo icon={GraduationCap} title={p.emptyTitle} body={p.emptyBody}>
          <Link to={PMB.programs} className={buttonClass('secondary', 'sm')}>
            {p.all}
          </Link>
        </PendingInfo>
      ) : (
        <>
          {levels.length > 1 && (
            <div className="mb-6">
              <ChipGroup
                label={p.level}
                value={level}
                onChange={setLevel}
                options={[{ value: '', label: p.allLevels, count: programs.length }, ...levels.map((l) => ({ value: l, label: degreeLabel(l), count: programs.filter((x) => x.degree_level === l).length }))]}
              />
            </div>
          )}
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {shown.map((program) => (
              <li key={program.slug}>
                <ProgramCard program={program} />
              </li>
            ))}
          </ul>
          <p className="mt-6 text-[0.8125rem] text-lp-subtle">{fill(plural(intl, programs.length, p.source), { n: programs.length })}</p>
        </>
      )}
    </FacultySection>
  )
}
