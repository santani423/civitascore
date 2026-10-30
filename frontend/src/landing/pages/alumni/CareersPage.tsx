import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Heart, LayoutGrid } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { useDemoLoading } from '../../hooks/useAlumniDemo'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { COMPANIES, OPPORTUNITY_TYPES, RESOLVED_OPPORTUNITIES, WORK_TYPES, findOpportunity, matchesQuery, type OpportunityType } from '../../data/alumni'
import { LANDING_PATHS } from '../../paths'
import { useAlumniStore } from '../../store/alumni'
import { buttonClass } from '../../components/ui/buttonClass'
import { Reveal } from '../../components/ui/Primitives'
import { TabList, TabPanel, type TabItem } from '../../components/ui/Tabs'
import { FilterPanel, ResultSummary, SearchField, SelectField } from '../../components/student-affairs/Filters'
import { EmptyResults } from '../../components/student-affairs/CardGrid'
import { CompanyCard, OpportunityCard, OpportunityDialog, OpportunityGridSkeleton } from '../../components/alumni/Opportunities'
import { OPPORTUNITY_ICON } from '../../components/alumni/opportunityIcons'
import { AlumniDemoNotice, AlumniPageHero } from '../../components/alumni/Shared'

const FILTER_KEYS = ['q', 'tipe', 'kerja', 'favorit', 'peluang'] as const
type TypeTab = 'all' | OpportunityType

/** /komunitas-alumni/karier — jobs, internships, trainee, development, partnership and mentoring programs. */
export function CareersPage() {
  const { m, fmt } = useLandingI18n()
  const c = m.alumni.careers
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: c.metaTitle }), description: c.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumniCareers })

  const filters = useFilterParams(FILTER_KEYS, { tipe: OPPORTUNITY_TYPES, kerja: WORK_TYPES, favorit: ['1'], peluang: RESOLVED_OPPORTUNITIES.map((o) => o.slug) })
  const { q, tipe, kerja, favorit, peluang } = filters.values
  const favorites = useAlumniStore((s) => s.favorites)
  const loading = useDemoLoading()
  const tab: TypeTab = (tipe || 'all') as TypeTab

  const base = useMemo(
    () =>
      RESOLVED_OPPORTUNITIES.filter(
        (o) => matchesQuery(q, o.title, o.excerpt, o.employer.name, o.location) && (!kerja || o.workType === kerja) && (!favorit || favorites.includes(`opportunity:${o.slug}`)),
      ),
    [q, kerja, favorit, favorites],
  )
  const results = tipe ? base.filter((o) => o.type === tipe) : base

  const tabs: Array<TabItem<TypeTab>> = [
    { key: 'all', label: `${c.tabAll} (${base.length})`, icon: LayoutGrid },
    ...OPPORTUNITY_TYPES.map((type) => ({ key: type, label: `${c.types[type]} (${base.filter((o) => o.type === type).length})`, icon: OPPORTUNITY_ICON[type] })),
  ]
  const narrowed = q !== '' || tipe !== '' || kerja !== '' || favorit !== ''
  const favoriteCount = favorites.filter((id) => id.startsWith('opportunity:')).length
  const featuredCompanies = useMemo(() => [...COMPANIES].sort((a, b) => b.alumniCount - a.alumniCount).slice(0, 6), [])

  return (
    <>
      <AlumniPageHero
        titleId="careers-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniCareers }]}
        eyebrow={c.eyebrow}
        titleLead={c.titleLead}
        titleAccent={c.titleAccent}
        lead={c.lead}
      >
        <dl className="mt-7 flex flex-wrap gap-x-10 gap-y-4">
          {[
            { value: fmt.number(RESOLVED_OPPORTUNITIES.length), label: c.statOpen },
            { value: fmt.number(COMPANIES.length), label: c.statCompanies },
          ].map((stat) => (
            <div key={stat.label} className="flex flex-col-reverse">
              <dt className="mt-1 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-band-muted">{stat.label}</dt>
              <dd className="font-display text-[2rem] font-extrabold leading-none text-lp-band-fg">{stat.value}</dd>
            </div>
          ))}
        </dl>
      </AlumniPageHero>

      <section aria-labelledby="careers-results" className="lp-section pb-0 pt-8 sm:pt-10">
        <div className="lp-container">
          <AlumniDemoNotice className="mb-6" />
          <FilterPanel label={c.filterPanel}>
            <div className="grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
              <SearchField label={c.searchLabel} placeholder={c.searchPlaceholder} value={q} onChange={(v) => filters.set('q', v)} />
              <SelectField
                label={c.workType}
                value={kerja}
                onChange={(v) => filters.set('kerja', v)}
                options={[{ value: '', label: c.allWorkTypes }, ...WORK_TYPES.map((w) => ({ value: w, label: c.workTypes[w] }))]}
              />
            </div>
          </FilterPanel>

          <div className="mt-8 border-b border-lp-line">
            <TabList items={tabs} value={tab} onChange={(key) => filters.set('tipe', key === 'all' ? '' : key)} label={c.typeLabel} idPrefix="alumni-careers" variant="underline" className="-mb-px border-b-0" />
          </div>

          <h2 id="careers-results" className="sr-only">
            {c.resultsTitle}
          </h2>
          <TabPanel idPrefix="alumni-careers" tabKey={tab}>
            <div className="[&>div]:mt-4">
              <ResultSummary text={fill(c.summary, { n: results.length, total: RESOLVED_OPPORTUNITIES.length })} canReset={narrowed} onReset={() => filters.reset()} />
            </div>
            <button
              type="button"
              aria-pressed={favorit === '1'}
              onClick={() => filters.set('favorit', favorit ? '' : '1')}
              className={cn(
                'mt-2 inline-flex h-11 items-center gap-2 rounded-full border px-4 text-[0.875rem] font-semibold transition-colors',
                favorit ? 'border-lp-brand bg-lp-brand text-lp-onbrand' : 'border-lp-strong bg-lp-surface text-lp-muted hover:border-lp-brand/40 hover:text-lp-fg',
              )}
            >
              <Heart size={15} aria-hidden className={favorit ? 'fill-current' : undefined} />
              {c.savedOnly}
              <span className={cn('lp-tabular min-w-[1.5rem] rounded-full px-1.5 py-0.5 text-center text-[0.75rem] leading-none', favorit ? 'bg-white/20' : 'bg-lp-sunken text-lp-subtle')}>{favoriteCount}</span>
            </button>

            <div className="mt-5">
              {loading ? (
                <OpportunityGridSkeleton />
              ) : results.length > 0 ? (
                <ul className="grid animate-fade-in gap-5 md:grid-cols-2">
                  {results.map((item) => (
                    <li key={item.slug} className="flex">
                      <OpportunityCard item={item} onOpen={() => filters.set('peluang', item.slug)} />
                    </li>
                  ))}
                </ul>
              ) : (
                <EmptyResults noun={c.noun} onReset={() => filters.reset()} />
              )}
            </div>
          </TabPanel>
        </div>
      </section>

      <section aria-labelledby="careers-companies" className="lp-section lp-warm mt-16 sm:mt-20">
        <div className="lp-container">
          <Reveal className="max-w-2xl">
            <p className="lp-eyebrow text-lp-brand-text">{c.companiesEyebrow}</p>
            <h2 id="careers-companies" className="lp-h2 mt-3 text-lp-fg">
              {c.companiesTitle}
            </h2>
            <p className="lp-body mt-3 text-lp-muted">{c.companiesLead}</p>
          </Reveal>
          <ul className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {featuredCompanies.map((company) => (
              <li key={company.slug} className="flex">
                <CompanyCard
                  company={company}
                  onSelect={() => {
                    filters.set('tipe', '')
                    filters.set('q', company.name)
                    document.getElementById('careers-results')?.parentElement?.scrollIntoView({ block: 'start' })
                  }}
                />
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section aria-labelledby="careers-mentoring" className="lp-section">
        <div className="lp-container">
          <Reveal className="lp-on-band flex flex-col gap-6 rounded-lp-xl bg-lp-band p-6 sm:p-10 lg:flex-row lg:items-center lg:justify-between">
            <div className="max-w-2xl">
              <h2 id="careers-mentoring" className="lp-h3 text-[1.5rem] text-lp-band-fg">
                {c.mentoringTitle}
              </h2>
              <p className="lp-body mt-2 text-lp-band-muted">{c.mentoringBody}</p>
            </div>
            <Link to={LANDING_PATHS.alumniContributions} className={buttonClass('primary', 'lg', 'group shrink-0')}>
              {c.mentoringCta}
              <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
            </Link>
          </Reveal>
        </div>
      </section>

      <OpportunityDialog item={findOpportunity(peluang) ?? null} onClose={() => filters.set('peluang', '')} />
    </>
  )
}
