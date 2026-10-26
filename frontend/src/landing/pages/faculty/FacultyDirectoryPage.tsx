import { Link } from 'react-router-dom'
import { ArrowRight, Info } from 'lucide-react'
import { PMB } from '@/pmb/routes'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { FACULTIES, isFacultyName, type Faculty } from '../../data/faculties'
import { matchesQuery } from '../../data/student-affairs'
import { usePmbPrograms } from '../../lib/pmbPrograms'
import { LANDING_PATHS } from '../../paths'
import { PageHero } from '../../components/student-affairs/PageHero'
import { FilterPanel, ResultSummary, SearchField } from '../../components/student-affairs/Filters'
import { EmptyResults } from '../../components/student-affairs/CardGrid'
import { FacultyCard } from '../../components/faculty/FacultyCard'

const FILTER_KEYS = ['q'] as const

/**
 * /fakultas — the six faculties. Search matches faculty names and the study
 * programs the PMB website lists under them (kept in the URL as `?q=`).
 */
export function FacultyDirectoryPage() {
  const { m } = useLandingI18n()
  const d = m.faculty.directory
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: d.metaTitle }), description: d.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.faculties })

  const programs = usePmbPrograms()
  const filters = useFilterParams(FILTER_KEYS)
  const { q } = filters.values
  const items = programs.status === 'ready' ? programs.items : []
  const programsOf = (faculty: Faculty) => items.filter((p) => isFacultyName(faculty, p.faculty))
  const listed = FACULTIES.flatMap(programsOf)
  const results = FACULTIES.filter((f) => matchesQuery(q, f.name, ...programsOf(f).map((p) => p.name)))
  const noun = d.noun
  const summary = q ? fill(m.affairs.filters.summaryFiltered, { matched: results.length, total: FACULTIES.length, noun }) : fill(m.affairs.filters.summaryAll, { total: FACULTIES.length, noun })

  return (
    <>
      <PageHero
        titleId="faculties-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.faculties }]}
        eyebrow={d.eyebrow}
        titleLead={d.titleLead}
        titleAccent={d.titleAccent}
        lead={d.lead}
        photo="campusFacade"
        stats={[
          { value: String(FACULTIES.length), label: d.statFaculties },
          ...(programs.status === 'ready' && listed.length > 0 ? [{ value: String(listed.length), label: d.statPrograms }] : []),
        ]}
      />

      <section aria-labelledby="faculties-results" className="lp-section pt-10 sm:pt-12 lg:pt-14">
        <div className="lp-container">
          <div role="note" className="mb-6 flex items-start gap-3 rounded-lp-lg border border-lp-line bg-lp-surface p-4 text-[0.875rem] leading-relaxed text-lp-fg shadow-lp-sm sm:p-5">
            <Info size={18} aria-hidden className="mt-0.5 shrink-0 text-lp-brand-text" />
            <span>
              <strong className="font-semibold">{d.noticeTitle}</strong> {d.noticeBody}
            </span>
          </div>
          <FilterPanel label={fill(m.affairs.filters.panel, { noun })}>
            <SearchField label={d.searchLabel} placeholder={d.searchPlaceholder} value={q} onChange={(v) => filters.set('q', v)} />
          </FilterPanel>

          <h2 id="faculties-results" className="sr-only">
            {d.resultsTitle}
          </h2>
          <ResultSummary text={summary} canReset={filters.active} onReset={filters.reset} />
          <div className="mt-5">
            {results.length > 0 ? (
              <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                {results.map((faculty) => (
                  <li key={faculty.slug} className="flex">
                    <FacultyCard faculty={faculty} programs={programs} />
                  </li>
                ))}
              </ul>
            ) : (
              <EmptyResults noun={noun} onReset={filters.reset} />
            )}
          </div>

          <p className="mt-10 flex flex-col gap-3 rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface/70 p-5 text-[0.875rem] text-lp-muted sm:flex-row sm:items-center sm:justify-between">
            <span>{d.allPrograms}</span>
            <Link to={PMB.programs} className="group inline-flex min-h-11 shrink-0 items-center gap-2 font-semibold text-lp-brand-text">
              {d.allProgramsCta}
              <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
          </p>
        </div>
      </section>
    </>
  )
}
