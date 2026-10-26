import { useMemo } from 'react'
import { useLocation } from 'react-router-dom'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import {
  ACTIVITY_CATEGORIES,
  ACTIVITY_STATUSES,
  RESOLVED_ACTIVITIES,
  activityMonths,
  activityStatus,
  monthKey,
  matchesQuery,
  organizerOf,
  sortActivities,
  type ResolvedActivity,
} from '../../data/student-affairs'
import { PILLARS } from '../../data/pillars'
import { LANDING_PATHS } from '../../paths'
import { formatMonthKey } from '../../lib/affairsFormat'
import { PageHero, DemoNotice } from '../../components/student-affairs/PageHero'
import { ChipGroup, FilterPanel, ResultSummary, SearchField, SelectField } from '../../components/student-affairs/Filters'
import { CardGrid, EmptyResults } from '../../components/student-affairs/CardGrid'
import { ActivityCard } from '../../components/student-affairs/Cards'

const FILTER_KEYS = ['q', 'kategori', 'status', 'bulan', 'pilar'] as const
const PAGE_SIZE = 9

/**
 * /kegiatan — sample activities with search, category, status, month and
 * Student Life Journey pillar filters (combinable, kept in the URL; the
 * 5 Pilar page can link here with `?pilar=<slug>`).
 */
export function ActivitiesPage() {
  const { m, intl } = useLandingI18n()
  const { search } = useLocation()
  const a = m.affairs
  const copy = a.activities
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: copy.metaTitle }), description: copy.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.activities })

  const months = useMemo(() => activityMonths(), [])
  const filters = useFilterParams(FILTER_KEYS, {
    kategori: ACTIVITY_CATEGORIES,
    status: ACTIVITY_STATUSES,
    bulan: months,
    pilar: PILLARS.map((p) => p.id),
  })
  const { q, kategori, status, bulan, pilar } = filters.values

  const all = useMemo(() => sortActivities(RESOLVED_ACTIVITIES), [])
  const pillarKey = PILLARS.find((p) => p.id === pilar)?.key

  // Every filter except the category, so each chip can show how many results it would give.
  const matchesOthers = (activity: ResolvedActivity) =>
    matchesQuery(q, activity.title, activity.excerpt, organizerOf(activity)?.name) &&
    (!status || activityStatus(activity) === status) &&
    (!bulan || monthKey(activity.startsAt) === bulan) &&
    (!pillarKey || activity.pillars.includes(pillarKey))
  const base = all.filter(matchesOthers)
  const results = kategori ? base.filter((activity) => activity.category === kategori) : base

  const noun = copy.noun
  const summary = filters.active ? fill(a.filters.summaryFiltered, { matched: results.length, total: all.length, noun }) : fill(a.filters.summaryAll, { total: all.length, noun })

  return (
    <>
      <PageHero
        titleId="activities-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.activities }]}
        eyebrow={m.nav.studentAffairs}
        titleLead={copy.titleLead}
        titleAccent={copy.titleAccent}
        lead={copy.lead}
        photo="campusFacade"
        stats={[
          { value: String(all.length), label: copy.statTotal },
          { value: String(all.filter((x) => activityStatus(x) === 'upcoming').length), label: a.status.upcoming },
        ]}
      />

      <section aria-labelledby="activities-results" className="lp-section pt-10 sm:pt-12 lg:pt-14">
        <div className="lp-container">
          <DemoNotice className="mb-6" />
          <FilterPanel label={fill(a.filters.panel, { noun: copy.noun })}>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-[minmax(0,1.5fr)_repeat(3,minmax(0,1fr))]">
              <SearchField label={copy.searchLabel} placeholder={copy.searchPlaceholder} value={q} onChange={(v) => filters.set('q', v)} />
              <SelectField
                label={a.filters.status}
                value={status}
                onChange={(v) => filters.set('status', v)}
                options={[{ value: '', label: a.filters.allStatuses }, ...ACTIVITY_STATUSES.map((s) => ({ value: s, label: a.status[s] }))]}
              />
              <SelectField
                label={a.filters.month}
                value={bulan}
                onChange={(v) => filters.set('bulan', v)}
                options={[{ value: '', label: a.filters.allMonths }, ...months.map((key) => ({ value: key, label: formatMonthKey(key, intl) }))]}
              />
              <SelectField
                label={a.filters.pillar}
                value={pilar}
                onChange={(v) => filters.set('pilar', v)}
                options={[{ value: '', label: a.filters.allPillars }, ...PILLARS.map((p) => ({ value: p.id, label: p.title, lang: 'id' }))]}
              />
            </div>
            <div className="mt-5 border-t border-lp-line pt-5">
              <ChipGroup
                label={a.filters.category}
                value={kategori}
                onChange={(v) => filters.set('kategori', v)}
                options={[
                  { value: '', label: a.filters.all, count: base.length },
                  ...ACTIVITY_CATEGORIES.map((c) => ({ value: c, label: a.categories.activity[c], count: base.filter((x) => x.category === c).length })),
                ]}
              />
            </div>
          </FilterPanel>

          <h2 id="activities-results" className="sr-only">
            {copy.resultsTitle}
          </h2>
          <ResultSummary text={summary} canReset={filters.active} onReset={filters.reset} />
          <div className="mt-5">
            {results.length > 0 ? (
              <CardGrid
                items={results}
                getKey={(x) => x.slug}
                pageSize={PAGE_SIZE}
                resetKey={search}
                render={(activity) => <ActivityCard activity={activity} state={{ from: search }} />}
              />
            ) : (
              <EmptyResults noun={noun} onReset={filters.reset} />
            )}
          </div>
        </div>
      </section>
    </>
  )
}
