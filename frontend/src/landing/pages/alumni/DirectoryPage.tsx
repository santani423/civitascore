import { useMemo, useRef, useState } from 'react'
import { useLocation } from 'react-router-dom'
import { Heart, SlidersHorizontal, X } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { useDemoLoading } from '../../hooks/useAlumniDemo'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { prefersReducedMotion } from '../../hooks/useScroll'
import { ALUMNI, ALUMNI_SORTS, INDUSTRIES, alumniPrograms, alumniYears, matchesQuery, sortAlumni, type AlumniSort } from '../../data/alumni'
import { FACULTIES, FACULTY_KEYS, facultyName, type FacultyKey } from '../../data/faculties'
import { LANDING_PATHS } from '../../paths'
import { useAlumniStore } from '../../store/alumni'
import { Button, IconButton } from '../../components/ui/Button'
import { Drawer } from '../../components/ui/Overlay'
import { FilterPanel, ResultSummary, SearchField, SelectField, type FilterOption } from '../../components/student-affairs/Filters'
import { EmptyResults } from '../../components/student-affairs/CardGrid'
import { Pagination } from '../../components/alumni/Pagination'
import { ProfileCard, ProfileGridSkeleton } from '../../components/alumni/ProfileCard'
import { AlumniDemoNotice, AlumniPageHero } from '../../components/alumni/Shared'

const FILTER_KEYS = ['q', 'fakultas', 'prodi', 'lulus', 'industri', 'urut', 'favorit', 'halaman'] as const
const PAGE_SIZE = 12

/**
 * /komunitas-alumni/direktori — search, four filters, sorting and pagination
 * over the sample alumni, all kept in the URL so a filtered page can be shared
 * and Back from a profile returns to it. On phones the filters move into a
 * drawer so they don't push the results off the screen.
 */
export function DirectoryPage() {
  const { m } = useLandingI18n()
  const { search } = useLocation()
  const d = m.alumni.directory
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: d.metaTitle }), description: d.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumniDirectory })

  const years = useMemo(() => alumniYears().map(String), [])
  const filters = useFilterParams(FILTER_KEYS, {
    fakultas: FACULTY_KEYS,
    prodi: alumniPrograms(),
    lulus: years,
    industri: INDUSTRIES,
    urut: ALUMNI_SORTS,
    favorit: ['1'],
  })
  const { q, fakultas, prodi, lulus, industri, urut, favorit, halaman } = filters.values
  const favorites = useAlumniStore((s) => s.favorites)
  const loading = useDemoLoading()
  const [drawerOpen, setDrawerOpen] = useState(false)
  const resultsRef = useRef<HTMLDivElement>(null)

  /** Any change to what's listed goes back to the first page. */
  const setFilter = (key: Exclude<(typeof FILTER_KEYS)[number], 'halaman'>, value: string) => {
    filters.set('halaman', '')
    filters.set(key, value)
  }
  const setFaculty = (value: string) => {
    setFilter('fakultas', value)
    // A program from another faculty would leave the list empty for no visible reason.
    if (value && prodi && !alumniPrograms(value).includes(prodi)) filters.set('prodi', '')
  }

  const favoriteCount = favorites.filter((id) => id.startsWith('alumni:')).length
  const results = useMemo(() => {
    const matched = ALUMNI.filter(
      (a) =>
        matchesQuery(q, a.name) &&
        (!fakultas || a.faculty === fakultas) &&
        (!prodi || a.program === prodi) &&
        (!lulus || String(a.gradYear) === lulus) &&
        (!industri || a.industry === industri) &&
        (!favorit || favorites.includes(`alumni:${a.slug}`)),
    )
    return sortAlumni(matched, (urut || 'name') as AlumniSort)
  }, [q, fakultas, prodi, lulus, industri, urut, favorit, favorites])

  const totalPages = Math.max(1, Math.ceil(results.length / PAGE_SIZE))
  const page = Math.min(totalPages, Math.max(1, Number(halaman) || 1))
  const visible = results.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE)

  const goToPage = (next: number) => {
    filters.set('halaman', next > 1 ? String(next) : '')
    // Start the new page at its first card, and let keyboard users continue from there.
    resultsRef.current?.scrollIntoView({ block: 'start', behavior: prefersReducedMotion() ? 'auto' : 'smooth' })
    resultsRef.current?.focus({ preventScroll: true })
  }

  const facultyOptions: FilterOption[] = [{ value: '', label: m.affairs.filters.allFaculties }, ...FACULTIES.map((f) => ({ value: f.slug, label: f.name, lang: 'id' }))]
  const programOptions: FilterOption[] = [{ value: '', label: d.allPrograms }, ...alumniPrograms(fakultas || undefined).map((p) => ({ value: p, label: p, lang: 'id' }))]
  const yearOptions: FilterOption[] = [{ value: '', label: d.allYears }, ...years.map((y) => ({ value: y, label: y }))]
  const industryOptions: FilterOption[] = [{ value: '', label: d.allIndustries }, ...INDUSTRIES.map((i) => ({ value: i, label: m.alumni.industries[i] }))]
  const sortOptions: FilterOption[] = ALUMNI_SORTS.map((s) => ({ value: s, label: d.sorts[s] }))

  const facultySelect = <SelectField label={d.faculty} value={fakultas} onChange={setFaculty} options={facultyOptions} />
  const programSelect = <SelectField label={d.program} value={prodi} onChange={(v) => setFilter('prodi', v)} options={programOptions} />
  const yearSelect = <SelectField label={d.year} value={lulus} onChange={(v) => setFilter('lulus', v)} options={yearOptions} />
  const industrySelect = <SelectField label={d.industry} value={industri} onChange={(v) => setFilter('industri', v)} options={industryOptions} />

  const activeChips = [
    fakultas && { key: 'fakultas' as const, label: facultyName(fakultas as FacultyKey), lang: 'id' },
    prodi && { key: 'prodi' as const, label: prodi, lang: 'id' },
    lulus && { key: 'lulus' as const, label: fill(d.yearChip, { year: lulus }) },
    industri && { key: 'industri' as const, label: m.alumni.industries[industri as keyof typeof m.alumni.industries] },
  ].filter((chip) => !!chip)
  const narrowed = activeChips.length > 0 || q !== '' || favorit !== ''
  const reset = () => {
    filters.reset()
  }

  const start = results.length === 0 ? 0 : (page - 1) * PAGE_SIZE + 1
  const summary = fill(d.summary, { start, end: Math.min(page * PAGE_SIZE, results.length), total: results.length })

  return (
    <>
      <AlumniPageHero
        titleId="directory-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniDirectory }]}
        eyebrow={d.eyebrow}
        titleLead={d.titleLead}
        titleAccent={d.titleAccent}
        lead={d.lead}
      />

      <section aria-labelledby="directory-results" className="lp-section pt-8 sm:pt-10">
        <div className="lp-container">
          <AlumniDemoNotice className="mb-6" />

          <FilterPanel label={d.filterPanel}>
            <div className="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
              <SearchField label={d.searchLabel} placeholder={d.searchPlaceholder} value={q} onChange={(v) => setFilter('q', v)} />
              <div className="hidden lg:block">
                <SelectField label={d.sort} value={urut || 'name'} onChange={(v) => filters.set('urut', v === 'name' ? '' : v)} options={sortOptions} />
              </div>
            </div>
            <div className="mt-4 hidden gap-4 lg:grid lg:grid-cols-4">
              {facultySelect}
              {programSelect}
              {yearSelect}
              {industrySelect}
            </div>
            <div className="mt-3 flex items-end gap-3 lg:hidden">
              <Button variant="secondary" className="shrink-0" onClick={() => setDrawerOpen(true)} aria-expanded={drawerOpen} aria-controls="alumni-filter-drawer">
                <SlidersHorizontal size={16} aria-hidden />
                {d.filterButton}
                {activeChips.length > 0 && <span className="lp-tabular flex h-5 min-w-5 items-center justify-center rounded-full bg-lp-brand px-1 text-[0.75rem] leading-none text-lp-onbrand">{activeChips.length}</span>}
              </Button>
              <div className="min-w-0 flex-1">
                <SelectField label={d.sort} value={urut || 'name'} onChange={(v) => filters.set('urut', v === 'name' ? '' : v)} options={sortOptions} />
              </div>
            </div>
          </FilterPanel>

          <Drawer open={drawerOpen} onClose={() => setDrawerOpen(false)} label={d.filterPanel} id="alumni-filter-drawer">
            <div className="flex h-16 shrink-0 items-center justify-between gap-3 border-b border-lp-line pl-5 pr-3">
              <p className="lp-h4 text-lp-fg">{d.filterButton}</p>
              <IconButton onClick={() => setDrawerOpen(false)} aria-label={m.common.close}>
                <X size={20} aria-hidden />
              </IconButton>
            </div>
            <div className="flex-1 space-y-5 overflow-y-auto p-5">
              {facultySelect}
              {programSelect}
              {yearSelect}
              {industrySelect}
            </div>
            <div className="shrink-0 space-y-2 border-t border-lp-line p-4">
              <Button className="w-full" onClick={() => setDrawerOpen(false)}>
                {fill(d.showResults, { n: results.length })}
              </Button>
              {narrowed && (
                <Button variant="ghost" className="w-full" onClick={reset}>
                  {m.affairs.filters.reset}
                </Button>
              )}
            </div>
          </Drawer>

          <h2 id="directory-results" className="sr-only">
            {d.resultsTitle}
          </h2>
          <div ref={resultsRef} tabIndex={-1} className="lp-below-bars focus:outline-none">
            <ResultSummary text={summary} canReset={narrowed} onReset={reset} />
          </div>

          <div className="mt-3 flex flex-wrap items-center gap-2">
            <button
              type="button"
              aria-pressed={favorit === '1'}
              onClick={() => setFilter('favorit', favorit ? '' : '1')}
              className={cn(
                'inline-flex h-11 items-center gap-2 rounded-full border px-4 text-[0.875rem] font-semibold transition-colors',
                favorit ? 'border-lp-brand bg-lp-brand text-lp-onbrand' : 'border-lp-strong bg-lp-surface text-lp-muted hover:border-lp-brand/40 hover:text-lp-fg',
              )}
            >
              <Heart size={15} aria-hidden className={favorit ? 'fill-current' : undefined} />
              {d.favoritesOnly}
              <span className={cn('lp-tabular min-w-[1.5rem] rounded-full px-1.5 py-0.5 text-center text-[0.75rem] leading-none', favorit ? 'bg-white/20' : 'bg-lp-sunken text-lp-subtle')}>{favoriteCount}</span>
            </button>
            {activeChips.map((chip) => (
              <button
                key={chip.key}
                type="button"
                onClick={() => setFilter(chip.key, '')}
                aria-label={fill(d.removeFilter, { label: chip.label })}
                className="inline-flex h-11 max-w-full items-center gap-1.5 rounded-full bg-lp-brand-soft px-3.5 text-[0.8125rem] font-semibold text-lp-brand-text transition-colors hover:bg-lp-brand hover:text-lp-onbrand"
              >
                <span lang={'lang' in chip ? chip.lang : undefined} className="truncate">
                  {chip.label}
                </span>
                <X size={14} aria-hidden className="shrink-0" />
              </button>
            ))}
          </div>

          <div className="mt-6">
            {loading ? (
              <ProfileGridSkeleton />
            ) : visible.length > 0 ? (
              <ul className="grid animate-fade-in gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {visible.map((person) => (
                  <li key={person.slug} className="flex">
                    <ProfileCard person={person} state={{ from: search }} />
                  </li>
                ))}
              </ul>
            ) : (
              <EmptyResults noun={d.noun} onReset={reset} />
            )}
          </div>

          <Pagination page={page} total={totalPages} onChange={goToPage} className="mt-10" />
        </div>
      </section>
    </>
  )
}
