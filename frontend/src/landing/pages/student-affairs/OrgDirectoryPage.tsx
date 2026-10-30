import { useLocation } from 'react-router-dom'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { ORMAWA_CATEGORIES, UKM_CATEGORIES, matchesQuery, orgsOf, type OrgKind } from '../../data/student-affairs'
import { FACULTIES, FACULTY_KEYS } from '../../data/faculties'
import type { PhotoKey } from '../../data/photos'
import { LANDING_PATHS, ORG_LIST_PATHS } from '../../paths'
import { PageHero, DemoNotice } from '../../components/student-affairs/PageHero'
import { ChipGroup, FilterPanel, ResultSummary, SearchField, SelectField } from '../../components/student-affairs/Filters'
import { CardGrid, EmptyResults } from '../../components/student-affairs/CardGrid'
import { OrgCard } from '../../components/student-affairs/Cards'

const FILTER_KEYS = ['q', 'kategori', 'fakultas', 'urut'] as const
// The first one is the default (not written to the URL): grouped by category, so a directory reads from university bodies down.
const SORTS = ['kategori', 'nama', 'nama-za'] as const
const PAGE_SIZE = 12

const PAGE: Record<OrgKind, { photo: PhotoKey; categories: readonly string[] }> = {
  ormawa: { photo: 'campusTower', categories: ORMAWA_CATEGORIES },
  ukm: { photo: 'grotto', categories: UKM_CATEGORIES },
}

/**
 * /daftar-ormawa and /daftar-ukm — one directory template. Search, category
 * chips, sorting and (Ormawa only) a faculty filter, all kept in the URL.
 */
export function OrgDirectoryPage({ kind }: { kind: OrgKind }) {
  const { m, intl } = useLandingI18n()
  const { search } = useLocation()
  const a = m.affairs
  const copy = a[kind]
  const config = PAGE[kind]
  const path = ORG_LIST_PATHS[kind]
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: copy.metaTitle }), description: copy.metaDescription, ogLocale: m.meta.ogLocale, path })

  const filters = useFilterParams(FILTER_KEYS, { kategori: config.categories, fakultas: FACULTY_KEYS, urut: SORTS })
  const { q, kategori, fakultas, urut } = filters.values
  const all = orgsOf(kind)
  const categoryLabels: Record<string, string> = kind === 'ormawa' ? a.categories.ormawa : a.categories.ukm

  const base = all.filter(
    (org) => matchesQuery(q, org.name, org.short, org.excerpt, ...org.focus) && (!fakultas || (org.kind === 'ormawa' && org.faculty === fakultas)),
  )
  const filtered = kategori ? base.filter((org) => org.category === kategori) : base
  const collator = new Intl.Collator(intl, { sensitivity: 'base' })
  const results = [...filtered].sort((x, y) => {
    if (urut === 'nama') return collator.compare(x.name, y.name)
    if (urut === 'nama-za') return collator.compare(y.name, x.name)
    return config.categories.indexOf(x.category) - config.categories.indexOf(y.category) || all.indexOf(x) - all.indexOf(y)
  })

  const categoryOptions = config.categories.map((c) => ({
    value: c,
    label: categoryLabels[c],
    count: base.filter((org) => org.category === c).length,
  }))
  const noun = copy.noun
  // Sorting alone doesn't narrow the list, so it doesn't count as "filtered".
  const narrowed = Boolean(q || kategori || fakultas)
  const summary = narrowed ? fill(a.filters.summaryFiltered, { matched: results.length, total: all.length, noun }) : fill(a.filters.summaryAll, { total: all.length, noun })

  return (
    <>
      <PageHero
        titleId={`${kind}-title`}
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav[kind] }]}
        eyebrow={m.nav.studentAffairs}
        titleLead={copy.titleLead}
        titleAccent={copy.titleAccent}
        lead={copy.lead}
        photo={config.photo}
        stats={[
          { value: String(all.length), label: copy.statTotal },
          { value: String(new Set(all.map((org) => org.category)).size), label: a.filters.categoryCount },
        ]}
      />

      <section aria-labelledby={`${kind}-results`} className="lp-section pt-10 sm:pt-12 lg:pt-14">
        <div className="lp-container">
          <DemoNotice className="mb-6" />
          <FilterPanel label={fill(a.filters.panel, { noun: copy.noun })}>
            <div className={kind === 'ormawa' ? 'grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]' : 'grid gap-4 md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]'}>
              <SearchField label={copy.searchLabel} placeholder={copy.searchPlaceholder} value={q} onChange={(v) => filters.set('q', v)} />
              {kind === 'ormawa' && (
                <SelectField
                  label={a.filters.faculty}
                  value={fakultas}
                  onChange={(v) => filters.set('fakultas', v)}
                  options={[{ value: '', label: a.filters.allFaculties }, ...FACULTIES.map((f) => ({ value: f.slug, label: f.name, lang: 'id' }))]}
                />
              )}
              <SelectField
                label={a.filters.sort}
                value={urut || SORTS[0]}
                onChange={(v) => filters.set('urut', v === SORTS[0] ? '' : v)}
                options={[
                  { value: 'kategori', label: a.filters.sortCategory },
                  { value: 'nama', label: a.filters.sortName },
                  { value: 'nama-za', label: a.filters.sortNameDesc },
                ]}
              />
            </div>
            <div className="mt-5 border-t border-lp-line pt-5">
              <ChipGroup
                label={a.filters.category}
                value={kategori}
                onChange={(v) => filters.set('kategori', v)}
                options={[{ value: '', label: a.filters.all, count: base.length }, ...categoryOptions]}
              />
            </div>
          </FilterPanel>

          <h2 id={`${kind}-results`} className="sr-only">
            {copy.resultsTitle}
          </h2>
          <ResultSummary text={summary} canReset={filters.active} onReset={filters.reset} />
          <div className="mt-5">
            {results.length > 0 ? (
              <CardGrid
                items={results}
                getKey={(org) => org.slug}
                pageSize={PAGE_SIZE}
                resetKey={search}
                render={(org) => <OrgCard org={org} state={{ from: search }} />}
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
