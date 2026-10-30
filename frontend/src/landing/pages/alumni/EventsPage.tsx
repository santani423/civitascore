import { useMemo } from 'react'
import { CalendarCheck, CalendarClock, LayoutGrid } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useCountdown, useDemoLoading } from '../../hooks/useAlumniDemo'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { useFilterParams } from '../../hooks/useFilterParams'
import { EVENT_STATUSES, EVENT_TYPES, RESOLVED_EVENTS, eventStatus, findEvent, matchesQuery, sortEvents, upcomingEvents, type ResolvedEvent } from '../../data/alumni'
import { LANDING_PATHS } from '../../paths'
import { formatEventDate } from '../../lib/alumniFormat'
import { TabList, TabPanel, type TabItem } from '../../components/ui/Tabs'
import { ChipGroup, FilterPanel, ResultSummary, SearchField } from '../../components/student-affairs/Filters'
import { EmptyResults } from '../../components/student-affairs/CardGrid'
import { EventCard, EventDialog, EventGridSkeleton } from '../../components/alumni/Events'
import { AlumniDemoNotice, AlumniPageHero } from '../../components/alumni/Shared'

const FILTER_KEYS = ['q', 'jenis', 'status', 'acara'] as const
type StatusTab = 'all' | 'upcoming' | 'completed'

function NextEventTile() {
  const { m, intl } = useLandingI18n()
  const countdown = useCountdown()
  const next = upcomingEvents()[0]
  return (
    <div className="rounded-lp-lg border border-lp-band-line bg-lp-band-elev p-5 shadow-lp-lg">
      <p className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-accent">{m.alumni.overview.nextEvent}</p>
      <p lang="id" className="lp-h4 mt-2 text-lp-band-fg">
        {next.title}
      </p>
      <p className="mt-1.5 text-[0.875rem] text-lp-band-muted">{formatEventDate(next, intl, 'long')}</p>
      <p className="mt-4 font-display text-[1.75rem] font-extrabold leading-none text-lp-band-fg">{countdown(next.startsAt)}</p>
    </div>
  )
}

/** /komunitas-alumni/kegiatan — the eight event types; `?acara=<slug>` opens an event's dialog, so it can be linked to. */
export function EventsPage() {
  const { m } = useLandingI18n()
  const e = m.alumni.events
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: e.metaTitle }), description: e.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumniEvents })

  const filters = useFilterParams(FILTER_KEYS, { jenis: EVENT_TYPES, status: EVENT_STATUSES, acara: RESOLVED_EVENTS.map((event) => event.slug) })
  const { q, jenis, status, acara } = filters.values
  const loading = useDemoLoading()
  const tab: StatusTab = status === 'upcoming' || status === 'completed' ? status : 'all'
  const all = useMemo(() => sortEvents(RESOLVED_EVENTS), [])

  const matchesText = (event: ResolvedEvent) => matchesQuery(q, event.title, event.excerpt, event.venue)
  const byStatus = all.filter((event) => matchesText(event) && (tab === 'all' || eventStatus(event) === tab))
  const results = jenis ? byStatus.filter((event) => event.type === jenis) : byStatus

  const narrowed = q !== '' || jenis !== '' || tab !== 'all'
  const summary = fill(e.summary, { n: results.length, total: all.length })
  const tabs: Array<TabItem<StatusTab>> = [
    { key: 'all', label: `${e.tabs.all} (${all.filter(matchesText).length})`, icon: LayoutGrid },
    { key: 'upcoming', label: `${e.tabs.upcoming} (${all.filter((x) => matchesText(x) && eventStatus(x) === 'upcoming').length})`, icon: CalendarClock },
    { key: 'completed', label: `${e.tabs.completed} (${all.filter((x) => matchesText(x) && eventStatus(x) === 'completed').length})`, icon: CalendarCheck },
  ]

  return (
    <>
      <AlumniPageHero
        titleId="events-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniEvents }]}
        eyebrow={e.eyebrow}
        titleLead={e.titleLead}
        titleAccent={e.titleAccent}
        lead={e.lead}
        aside={<NextEventTile />}
      />

      <section aria-labelledby="events-results" className="lp-section pt-8 sm:pt-10">
        <div className="lp-container">
          <AlumniDemoNotice className="mb-6" />
          <FilterPanel label={e.filterPanel}>
            <SearchField label={e.searchLabel} placeholder={e.searchPlaceholder} value={q} onChange={(v) => filters.set('q', v)} />
            <div className="mt-5 border-t border-lp-line pt-5">
              <ChipGroup
                label={e.typeLabel}
                value={jenis}
                onChange={(v) => filters.set('jenis', v)}
                options={[{ value: '', label: m.affairs.filters.all, count: byStatus.length }, ...EVENT_TYPES.map((type) => ({ value: type, label: e.types[type], count: byStatus.filter((x) => x.type === type).length }))]}
              />
            </div>
          </FilterPanel>

          <div className="mt-8">
            <TabList items={tabs} value={tab} onChange={(key) => filters.set('status', key === 'all' ? '' : key)} label={e.statusLabel} idPrefix="alumni-events" className="w-fit" />
          </div>

          <h2 id="events-results" className="sr-only">
            {e.resultsTitle}
          </h2>
          <TabPanel idPrefix="alumni-events" tabKey={tab}>
            <div className="[&>div]:mt-4">
              <ResultSummary text={summary} canReset={narrowed} onReset={() => filters.reset()} />
            </div>
            <div className="mt-5">
              {loading ? (
                <EventGridSkeleton />
              ) : results.length > 0 ? (
                <ul className="grid animate-fade-in gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
                  {results.map((event) => (
                    <li key={event.slug} className="flex">
                      <EventCard event={event} onOpen={() => filters.set('acara', event.slug)} />
                    </li>
                  ))}
                </ul>
              ) : (
                <EmptyResults noun={e.noun} onReset={() => filters.reset()} />
              )}
            </div>
          </TabPanel>
        </div>
      </section>

      <EventDialog event={findEvent(acara) ?? null} onClose={() => filters.set('acara', '')} />
    </>
  )
}
