import { useState } from 'react'
import {
  BookMarked,
  BookOpen,
  Briefcase,
  Building,
  CalendarClock,
  CalendarDays,
  Database,
  ExternalLink,
  FileText,
  Library,
  MapPin,
  MessagesSquare,
  RefreshCw,
  Send,
  UserCheck,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { daysUntil } from '../lib/dates'
import { books, digitalBooks, ejournals, jobs, loanHistory, loans, companyPartners, type Book, type Employment, type Job } from '../data/services'
import { events } from '../data/campus'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Async, CardGridSkeleton, EmptyState, ListSkeleton } from '../components/ui/Feedback'
import { Cover, FilterChips, IconTile, MetaItem, PageHeader, Stat, initials } from '../components/ui/Display'
import { SearchInput } from '../components/ui/Form'
import { TabPanel, Tabs } from '../components/ui/Tabs'
import { BookmarkButton, useDue } from '../components/shared/bits'

/* ============================ Library ============================ */

type LTab = 'catalog' | 'borrowed' | 'history' | 'digital' | 'ejournal'

export function LibraryPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const due = useDue()
  const [tab, setTab] = useState<LTab>('catalog')
  const [query, setQuery] = useState('')
  const [cat, setCat] = useState('all')
  const borrowedIds = useApp((s) => s.borrowed)
  const add = useApp((s) => s.add)
  const q = useMockData(() => books, [] as Book[])

  const categories = Array.from(new Set(books.map((b) => b.category)))
  const needle = query.toLowerCase()
  const catalog = q.data.filter((b) => (cat === 'all' || b.category === cat) && `${b.title} ${b.author} ${b.isbn}`.toLowerCase().includes(needle))
  const myLoans = [...loans, ...borrowedIds.map((id, i) => ({ id: `new-${id}`, bookId: id, borrowed: new Date().toISOString(), due: new Date(Date.now() + (14 + i) * 86_400_000).toISOString() }))]

  return (
    <>
      <PageHeader title={t('library.title')} description={t('library.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('library.title') }]} />

      <section className="relative mb-6 overflow-hidden rounded-3xl bg-one-hero px-5 py-8 text-white shadow-lift sm:px-10 sm:py-12">
        <div aria-hidden className="one-cover-pattern absolute inset-0 opacity-25" />
        <div className="relative mx-auto max-w-3xl">
          <h2 className="t-h1 text-center text-white">{t('library.searchPlaceholder')}</h2>
          <SearchInput
            size="lg"
            value={query}
            onValueChange={(v) => {
              setQuery(v)
              setTab('catalog')
            }}
            label={t('library.searchPlaceholder')}
            placeholder={t('library.searchPlaceholder')}
            className="mt-6"
            clearLabel={t('common.clearFilters')}
          />
          <dl className="mt-6 grid grid-cols-3 gap-3 text-center">
            {[
              { k: t('library.stats.titles'), v: '248K' },
              { k: t('library.stats.ejournals'), v: '32K' },
              { k: t('library.stats.databases'), v: '46' },
            ].map((s) => (
              <div key={s.k}>
                <dd className="font-display text-[22px] font-bold sm:text-[26px]">{s.v}</dd>
                <dt className="text-[12.5px] text-white/70">{s.k}</dt>
              </div>
            ))}
          </dl>
        </div>
      </section>

      <Tabs
        id="lib"
        label={t('library.title')}
        value={tab}
        onChange={setTab}
        className="mb-6"
        items={(['catalog', 'borrowed', 'history', 'digital', 'ejournal'] as LTab[]).map((v) => ({
          value: v,
          label: t(`library.tabs.${v}`),
          count: v === 'borrowed' ? myLoans.length : undefined,
        }))}
      />
      <TabPanel id="lib" value={tab} key={tab}>
        {tab === 'catalog' && (
          <>
            <FilterChips
              className="mb-5"
              label={t('common.category')}
              value={cat}
              onChange={setCat}
              options={[{ value: 'all', label: t('common.all') }, ...categories.map((c) => ({ value: c, label: c }))]}
            />
            <Async
              query={q}
              skeleton={<CardGridSkeleton count={8} className="lg:grid-cols-4 xl:grid-cols-4" />}
              isEmpty={() => catalog.length === 0}
              empty={<Card><EmptyState icon={BookOpen} title={t('states.emptyBooks')} body={t('states.emptyBooksBody')} /></Card>}
            >
              {() => (
                <ul className="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                  {catalog.map((b) => {
                    const mine = borrowedIds.includes(b.id) || loans.some((l) => l.bookId === b.id)
                    const available = b.available > 0
                    return (
                      <li key={b.id}>
                        <Card as="article" className="flex h-full flex-col p-3 sm:p-4">
                          <Cover tone={b.tone} className="aspect-[3/4] rounded-xl shadow-lift" label={b.title}>
                            <div className="absolute inset-y-0 left-2 w-px bg-white/25" />
                            <div className="absolute inset-x-4 top-5">
                              <p className="font-display text-[13px] font-bold leading-snug text-white sm:text-[15px]">{b.title}</p>
                              <p className="mt-1.5 text-[11px] text-white/75">{b.author}</p>
                            </div>
                            <span className="absolute right-2 top-2"><BookmarkButton id={b.id} inverse className="h-8 w-8" /></span>
                          </Cover>
                          <div className="flex flex-1 flex-col pt-3">
                            <p className="t-caption text-one-subtle">{b.category}</p>
                            <h3 className="mt-1 line-clamp-2 text-[14px] font-semibold leading-snug text-one-fg">{b.title}</h3>
                            <p className="mt-0.5 truncate text-[12.5px] text-one-subtle">{b.author} · {b.year}</p>
                            <div className="mt-2">
                              <Badge tone={available ? 'ok' : 'neutral'} dot>
                                {available ? `${t('status.available')} · ${b.available}/${b.copies}` : t('status.onLoan')}
                              </Badge>
                            </div>
                            <div className="mt-auto pt-3">
                              <Button
                                size="sm"
                                block
                                variant={available && !mine ? 'primary' : 'secondary'}
                                disabled={mine}
                                icon={mine ? UserCheck : available ? BookMarked : CalendarClock}
                                onClick={() => {
                                  if (available) {
                                    add('borrowed', b.id)
                                    toast(t('library.borrowedToast', { title: b.title }))
                                  } else toast(t('library.reservedToast', { title: b.title }))
                                }}
                              >
                                {mine ? t('status.onLoan') : available ? t('library.borrow') : t('library.reserve')}
                              </Button>
                            </div>
                          </div>
                        </Card>
                      </li>
                    )
                  })}
                </ul>
              )}
            </Async>
          </>
        )}

        {tab === 'borrowed' &&
          (myLoans.length === 0 ? (
            <Card><EmptyState icon={BookOpen} title={t('states.emptyBorrowed')} body={t('states.emptyBorrowedBody')} /></Card>
          ) : (
            <Card>
              <ul className="divide-y divide-one-line">
                {myLoans.map((l) => {
                  const b = books.find((x) => x.id === l.bookId)
                  if (!b) return null
                  const d = due(l.due)
                  return (
                    <li key={l.id} className="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:px-6">
                      <Cover tone={b.tone} className="h-16 w-12 shrink-0 rounded-md" />
                      <div className="min-w-0 flex-1">
                        <p className="font-medium text-one-fg">{b.title}</p>
                        <p className="text-[12.5px] text-one-subtle">{b.author} · {t('library.borrowedOn', { date: fmt.date(l.borrowed) })}</p>
                      </div>
                      <Badge tone={daysUntil(l.due) <= 3 ? 'warn' : 'neutral'}>{t('library.dueBack', { date: fmt.date(l.due, { day: 'numeric', month: 'short' }) })} · {d.label}</Badge>
                      <Button size="sm" variant="secondary" icon={RefreshCw} onClick={() => toast(t('library.renewedToast'))}>{t('library.renew')}</Button>
                    </li>
                  )
                })}
              </ul>
            </Card>
          ))}

        {tab === 'history' && (
          <Card>
            <ul className="divide-y divide-one-line">
              {loanHistory.map((l) => {
                const b = books.find((x) => x.id === l.bookId)!
                return (
                  <li key={l.id} className="flex items-center gap-4 p-4 sm:px-6">
                    <Cover tone={b.tone} className="h-14 w-10 shrink-0 rounded-md" />
                    <div className="min-w-0 flex-1">
                      <p className="truncate font-medium text-one-fg">{b.title}</p>
                      <p className="text-[12.5px] text-one-subtle">{t('library.borrowedOn', { date: fmt.date(l.borrowed) })} · {t('library.returnedOn', { date: fmt.date(l.returned) })}</p>
                    </div>
                    <Badge tone="neutral" dot>{t('status.returned')}</Badge>
                  </li>
                )
              })}
            </ul>
          </Card>
        )}

        {tab === 'digital' && (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {digitalBooks.map((b) => (
              <li key={b.id}>
                <Card className="flex h-full gap-4 p-4">
                  <Cover tone={b.tone} icon={FileText} className="h-24 w-[72px] shrink-0 rounded-lg" />
                  <div className="flex min-w-0 flex-col">
                    <Badge tone="teal" className="self-start">{t('library.access')} · {b.format}</Badge>
                    <p className="mt-2 line-clamp-2 text-[14px] font-semibold text-one-fg">{b.title}</p>
                    <p className="truncate text-[12.5px] text-one-subtle">{b.author}</p>
                    <button type="button" onClick={() => toast(`${t('library.readOnline')}: ${b.title}`, 'info')} className="mt-auto inline-flex items-center gap-1 pt-2 text-[13px] font-medium text-one-royal hover:underline">
                      {t('library.readOnline')} <ExternalLink size={13} aria-hidden />
                    </button>
                  </div>
                </Card>
              </li>
            ))}
          </ul>
        )}

        {tab === 'ejournal' && (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {ejournals.map((j) => (
              <li key={j.id}>
                <Card className="flex h-full items-start gap-4 p-5">
                  <IconTile icon={Database} tone="royal" size="lg" />
                  <div className="min-w-0 flex-1">
                    <p className="font-semibold text-one-fg">{j.name}</p>
                    <p className="text-[12.5px] text-one-subtle">{t('library.publisher')}: {j.publisher}</p>
                    <p className="t-small mt-2 text-one-muted">{j.field} · {j.titles}</p>
                    <button type="button" onClick={() => toast(`${j.name} — ${t('library.access')}`, 'info')} className="mt-3 inline-flex items-center gap-1 text-[13px] font-medium text-one-royal hover:underline">
                      {t('library.readOnline')} <ExternalLink size={13} aria-hidden />
                    </button>
                  </div>
                </Card>
              </li>
            ))}
          </ul>
        )}
      </TabPanel>
    </>
  )
}

/* ============================ Career ============================ */

type CTab = 'jobs' | 'internships' | 'events'

export function CareerPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const due = useDue()
  const applied = useApp((s) => s.applied)
  const add = useApp((s) => s.add)
  const [tab, setTab] = useState<CTab>('jobs')
  const [query, setQuery] = useState('')
  const q = useMockData(() => jobs, [] as Job[])
  const careerEvents = events.filter((e) => e.category === 'career' || e.category === 'seminar')

  const list = q.data.filter(
    (j) => (tab === 'internships' ? j.type === 'internship' : j.type !== 'internship') && `${j.position} ${j.company} ${j.tags.join(' ')}`.toLowerCase().includes(query.toLowerCase()),
  )

  const typeTone: Record<Employment, 'royal' | 'teal' | 'gold' | 'sky'> = { fullTime: 'royal', partTime: 'sky', internship: 'teal', contract: 'gold' }

  return (
    <>
      <PageHeader
        title={t('career.title')}
        description={t('career.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.career') }]}
        actions={<Button icon={MessagesSquare} onClick={() => toast(t('career.sessionToast'))}>{t('career.bookSession')}</Button>}
      />
      <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <Stat label={t('career.tabs.jobs')} value={jobs.filter((j) => j.type !== 'internship').length} icon={Briefcase} tone="royal" />
        <Stat label={t('career.tabs.internships')} value={jobs.filter((j) => j.type === 'internship').length} icon={Building} tone="teal" />
        <Stat label={t('career.tabs.events')} value={careerEvents.length} icon={CalendarDays} tone="gold" />
        <Stat label={t('career.partners')} value={companyPartners.length * 15} icon={Library} tone="ok" />
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2">
          <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Tabs
              id="car"
              label={t('career.title')}
              variant="pill"
              value={tab}
              onChange={setTab}
              items={(['jobs', 'internships', 'events'] as CTab[]).map((v) => ({ value: v, label: t(`career.tabs.${v}`) }))}
            />
            {tab !== 'events' && (
              <SearchInput value={query} onValueChange={setQuery} label={t('career.searchPlaceholder')} placeholder={t('career.searchPlaceholder')} className="sm:w-64" clearLabel={t('common.clearFilters')} />
            )}
          </div>
          <TabPanel id="car" value={tab} key={tab}>
            {tab === 'events' ? (
              <Card>
                <ul className="divide-y divide-one-line">
                  {careerEvents.map((e) => (
                    <li key={e.id} className="flex items-center gap-4 p-4 sm:px-6">
                      <div className="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-one-royal/10 text-one-royal">
                        <span className="text-[11px] font-semibold uppercase">{fmt.date(e.date, { month: 'short' })}</span>
                        <span className="font-display text-[19px] font-bold leading-none">{new Date(e.date).getDate()}</span>
                      </div>
                      <div className="min-w-0 flex-1">
                        <p className="font-medium text-one-fg">{e.title}</p>
                        <MetaItem icon={MapPin}>{e.location} · {fmt.time(e.date)}</MetaItem>
                      </div>
                      <Button size="sm" variant="secondary" to={`/one/campus/events?e=${e.id}`}>{t('events.viewEvent')}</Button>
                    </li>
                  ))}
                </ul>
              </Card>
            ) : (
              <Async query={q} skeleton={<Card padded><ListSkeleton /></Card>} isEmpty={() => list.length === 0} empty={<Card><EmptyState icon={Briefcase} title={t('states.emptyJobs')} body={t('states.emptyJobsBody')} /></Card>}>
                {() => (
                  <ul className="space-y-3">
                    {list.map((j) => {
                      const isApplied = applied.includes(j.id)
                      const d = due(j.deadline)
                      return (
                        <li key={j.id}>
                          <Card as="article" className="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-5">
                            <Cover tone={j.tone} className="h-12 w-12 shrink-0 rounded-xl">
                              <span className="absolute inset-0 flex items-center justify-center font-display text-[14px] font-bold text-white">{initials(j.company)}</span>
                            </Cover>
                            <div className="min-w-0 flex-1">
                              <p className="text-[13px] text-one-subtle">{j.company}</p>
                              <h3 className="text-[15.5px] font-semibold text-one-fg">{j.position}</h3>
                              <div className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                <MetaItem icon={MapPin}>{j.location}</MetaItem>
                                <Badge tone={typeTone[j.type]}>{t(`career.employment.${j.type}`)}</Badge>
                                <span className="text-[12.5px] text-one-subtle">{t('common.deadline')}: {fmt.date(j.deadline, { day: 'numeric', month: 'short' })}</span>
                                <Badge tone={d.tone}>{d.label}</Badge>
                              </div>
                            </div>
                            <div className="flex items-center gap-2">
                              <BookmarkButton id={j.id} />
                              <Button
                                variant={isApplied ? 'secondary' : 'primary'}
                                icon={isApplied ? UserCheck : Send}
                                disabled={isApplied}
                                onClick={() => {
                                  add('applied', j.id)
                                  toast(t('career.appliedToast', { company: j.company }))
                                }}
                              >
                                {isApplied ? t('common.applied') : t('common.apply')}
                              </Button>
                            </div>
                          </Card>
                        </li>
                      )
                    })}
                  </ul>
                )}
              </Async>
            )}
          </TabPanel>
        </div>

        <aside className="space-y-6">
          <Card className="overflow-hidden">
            <Cover tone="teal" icon={MessagesSquare} className="h-24" />
            <div className="p-5">
              <h2 className="t-h3 text-one-fg">{t('career.counseling')}</h2>
              <p className="t-small mt-1 text-one-muted">{t('career.counselingDesc')}</p>
              <Button className="mt-4" block variant="secondary" onClick={() => toast(t('career.sessionToast'))}>{t('career.bookSession')}</Button>
            </div>
          </Card>
          <Card>
            <CardHeader title={t('career.cvResources')} />
            <CardBody className="pt-3">
              <ul className="space-y-1">
                {(['template', 'guide', 'linkedin'] as const).map((k) => (
                  <li key={k}>
                    <button type="button" onClick={() => toast(`${t('common.download')}: ${t(`career.cvItems.${k}`)}`, 'info')} className="flex w-full items-center gap-3 rounded-xl px-2 py-2.5 text-left hover:bg-one-sunken">
                      <IconTile icon={FileText} tone="royal" size="sm" />
                      <span className="flex-1 text-[14px] text-one-fg">{t(`career.cvItems.${k}`)}</span>
                    </button>
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
          <Card>
            <CardHeader title={t('career.partners')} />
            <CardBody>
              <ul className="grid grid-cols-2 gap-2">
                {companyPartners.map((c, i) => (
                  <li key={c} className={cn('flex items-center gap-2 rounded-xl border border-one-line p-2.5')}>
                    <Cover tone={(['navy', 'royal', 'teal', 'gold', 'violet', 'rose', 'slate', 'green'] as const)[i % 8]} className="h-7 w-7 shrink-0 rounded-lg">
                      <span className="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white">{initials(c)}</span>
                    </Cover>
                    <span className="truncate text-[12.5px] font-medium text-one-fg">{c}</span>
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
        </aside>
      </div>
    </>
  )
}
