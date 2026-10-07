import { useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import {
  ArrowRight,
  BedDouble,
  BookOpen,
  Building2,
  CalendarDays,
  CalendarPlus,
  Church,
  Clock,
  Coffee,
  Dumbbell,
  FlaskConical,
  HandHeart,
  HeartPulse,
  Mail,
  MapPin,
  MessageCircleHeart,
  Share2,
  Sparkles,
  Stethoscope,
  Theater,
  UserPlus,
  Users,
  UserCheck,
  type LucideIcon,
} from 'lucide-react'
import { useI18n, type TKey } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { daysUntil } from '../lib/dates'
import { events, facilities, organizations, type CampusEvent, type EventCategory, type Facility, type FacilityType, type OrgCategory, type Organization } from '../data/campus'
import { Card } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Async, CardGridSkeleton, EmptyState } from '../components/ui/Feedback'
import { Cover, FilterChips, MetaItem, PageHeader } from '../components/ui/Display'
import { Field, Input, SearchInput, Select, Textarea } from '../components/ui/Form'
import { Segmented } from '../components/ui/Tabs'
import { Modal } from '../components/ui/Overlay'
import { EventCard, SectionTitle, eventIcon } from '../components/shared/bits'

/* ============================ Campus Life hub ============================ */

const sections: Array<{ key: string; icon: LucideIcon; to: string; tone: 'navy' | 'royal' | 'teal' | 'gold' | 'rose' | 'violet' | 'slate' | 'green' }> = [
  { key: 'organizations', icon: Users, to: '/one/campus/organizations', tone: 'navy' },
  { key: 'clubs', icon: Theater, to: '/one/campus/organizations', tone: 'violet' },
  { key: 'events', icon: CalendarDays, to: '/one/campus/events', tone: 'royal' },
  { key: 'facilities', icon: Building2, to: '/one/campus/facilities', tone: 'slate' },
  { key: 'dormitory', icon: BedDouble, to: '/one/services', tone: 'gold' },
  { key: 'health', icon: HeartPulse, to: '/one/campus/facilities', tone: 'rose' },
  { key: 'counseling', icon: MessageCircleHeart, to: '/one/services', tone: 'teal' },
  { key: 'ministry', icon: Church, to: '/one/campus/facilities', tone: 'navy' },
  { key: 'community', icon: HandHeart, to: '/one/campus/organizations', tone: 'green' },
]

export function CampusLifePage() {
  const { t } = useI18n()
  const [viewing, setViewing] = useState<CampusEvent | null>(null)
  const upcoming = events.filter((e) => daysUntil(e.date) >= 0).slice(0, 3)
  return (
    <>
      <PageHeader title={t('campus.title')} description={t('campus.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('campus.title') }]} />
      <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {sections.map((s) => (
          <li key={s.key}>
            <Card as="article" interactive className="group relative flex h-full overflow-hidden">
              <Cover tone={s.tone} className="w-24 shrink-0 sm:w-28">
                <span className="absolute inset-0 flex items-center justify-center text-white">
                  <s.icon size={30} aria-hidden strokeWidth={1.75} />
                </span>
              </Cover>
              <div className="flex flex-1 flex-col p-5">
                <h2 className="t-h3 text-one-fg">
                  <Link to={s.to} className="after:absolute after:inset-0 group-hover:text-one-royal">
                    {t(`campus.sections.${s.key}.title` as TKey)}
                  </Link>
                </h2>
                <p className="t-small mt-1 text-one-muted">{t(`campus.sections.${s.key}.desc` as TKey)}</p>
                <span className="mt-auto inline-flex items-center gap-1 pt-3 text-[13px] font-medium text-one-royal">
                  {t('campus.explore')} <ArrowRight size={14} aria-hidden className="transition-transform group-hover:translate-x-0.5" />
                </span>
              </div>
            </Card>
          </li>
        ))}
      </ul>

      <section aria-labelledby="hl-h" className="mt-10">
        <SectionTitle
          id="hl-h"
          title={t('campus.highlights')}
          action={<Link to="/one/campus/events" className="text-[13px] font-medium text-one-royal hover:underline">{t('common.viewAll')}</Link>}
        />
        <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
          {upcoming.map((e) => (
            <li key={e.id}>
              <EventCard event={e} onView={() => setViewing(e)} />
            </li>
          ))}
        </ul>
      </section>
      <EventModal event={viewing} onClose={() => setViewing(null)} />
    </>
  )
}

/* ============================ Organizations ============================ */

const orgCats: OrgCategory[] = ['academic', 'sports', 'arts', 'social', 'religious', 'technology', 'entrepreneurship']

export function OrganizationsPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const joined = useApp((s) => s.joined)
  const toggle = useApp((s) => s.toggle)
  const [cat, setCat] = useState<'all' | OrgCategory>('all')
  const [query, setQuery] = useState('')
  const q = useMockData(() => organizations, [] as Organization[])

  const list = q.data.filter((o) => (cat === 'all' || o.category === cat) && `${o.name} ${o.short} ${o.description}`.toLowerCase().includes(query.toLowerCase()))

  return (
    <>
      <PageHeader
        title={t('orgs.title')}
        description={t('orgs.subtitle', { count: organizations.length })}
        crumbs={[{ label: t('nav.campus'), to: '/one/campus' }, { label: t('orgs.title') }]}
      />
      <div className="mb-6 space-y-3">
        <SearchInput value={query} onValueChange={setQuery} label={t('orgs.searchPlaceholder')} placeholder={t('orgs.searchPlaceholder')} className="max-w-md" clearLabel={t('common.clearFilters')} />
        <FilterChips
          label={t('common.category')}
          value={cat}
          onChange={setCat}
          options={[
            { value: 'all', label: t('common.all'), count: q.data.length },
            ...orgCats.map((c) => ({ value: c, label: t(`orgs.categories.${c}`), count: q.data.filter((o) => o.category === c).length })),
          ]}
        />
      </div>
      <Async
        query={q}
        skeleton={<CardGridSkeleton media={false} />}
        isEmpty={() => list.length === 0}
        empty={
          <Card>
            <EmptyState
              icon={Users}
              title={t('states.emptyOrganizations')}
              body={t('states.emptyOrganizationsBody')}
              action={<Button variant="secondary" onClick={() => { setCat('all'); setQuery('') }}>{t('common.clearFilters')}</Button>}
            />
          </Card>
        }
      >
        {() => (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {list.map((o) => {
              const isJoined = joined.includes(o.id)
              return (
                <li key={o.id}>
                  <Card as="article" className="flex h-full flex-col p-5">
                    <div className="flex items-start gap-4">
                      <Cover tone={o.tone} className="h-14 w-14 shrink-0 rounded-2xl">
                        <span className="absolute inset-0 flex items-center justify-center font-display text-[15px] font-bold text-white">{o.short}</span>
                      </Cover>
                      <div className="min-w-0">
                        <h2 className="text-[16px] font-semibold leading-snug text-one-fg">{o.name}</h2>
                        <div className="mt-1.5 flex flex-wrap items-center gap-2">
                          <Badge tone="royal">{t(`orgs.categories.${o.category}`)}</Badge>
                          <MetaItem icon={Users}>{t('orgs.members', { count: fmt.number(o.members) })}</MetaItem>
                        </div>
                      </div>
                    </div>
                    <p className="t-small mt-4 text-one-muted">{o.description}</p>
                    <div className="mt-4 rounded-xl bg-one-sunken/70 px-3.5 py-3">
                      <p className="t-caption text-one-subtle">{t('orgs.upcomingEvent')}</p>
                      <p className="mt-1 text-[14px] font-medium text-one-fg">{o.nextEvent.title}</p>
                      <p className="text-[12.5px] text-one-subtle">{fmt.date(o.nextEvent.date, { weekday: 'short', day: 'numeric', month: 'short' })} · {fmt.time(o.nextEvent.date)}</p>
                    </div>
                    <div className="mt-auto pt-5">
                      <Button
                        block
                        variant={isJoined ? 'secondary' : 'primary'}
                        icon={isJoined ? UserCheck : UserPlus}
                        aria-pressed={isJoined}
                        onClick={() => {
                          toggle('joined', o.id)
                          if (!isJoined) toast(t('orgs.joinedToast', { name: o.name }))
                        }}
                      >
                        {isJoined ? t('orgs.joined') : t('orgs.join')}
                      </Button>
                    </div>
                  </Card>
                </li>
              )
            })}
          </ul>
        )}
      </Async>
    </>
  )
}

/* ============================ Events ============================ */

const eventCats: EventCategory[] = ['seminar', 'workshop', 'competition', 'cultural', 'sports', 'career']
type DateFilter = 'any' | 'week' | 'month' | 'later'

export function EventsPage() {
  const { t } = useI18n()
  const [params, setParams] = useSearchParams()
  const [query, setQuery] = useState('')
  const [cat, setCat] = useState<'all' | EventCategory>('all')
  const [range, setRange] = useState<DateFilter>('any')
  const q = useMockData(() => events, [] as CampusEvent[])

  const list = useMemo(
    () =>
      q.data.filter((e) => {
        const d = daysUntil(e.date)
        const inRange = range === 'any' || (range === 'week' && d <= 7) || (range === 'month' && d <= 30) || (range === 'later' && d > 30)
        return d >= 0 && inRange && (cat === 'all' || e.category === cat) && `${e.title} ${e.organizer} ${e.location}`.toLowerCase().includes(query.toLowerCase())
      }),
    [q.data, range, cat, query],
  )
  const viewing = events.find((e) => e.id === params.get('e')) ?? null

  return (
    <>
      <PageHeader title={t('events.title')} description={t('events.subtitle')} crumbs={[{ label: t('nav.campus'), to: '/one/campus' }, { label: t('events.title') }]} />
      <div className="mb-6 space-y-3">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <SearchInput value={query} onValueChange={setQuery} label={t('events.searchPlaceholder')} placeholder={t('events.searchPlaceholder')} className="flex-1 sm:max-w-md" clearLabel={t('common.clearFilters')} />
          <Segmented
            label={t('events.dateRange')}
            value={range}
            onChange={setRange}
            size="sm"
            className="self-start"
            items={(['any', 'week', 'month', 'later'] as DateFilter[]).map((v) => ({ value: v, label: t(`events.dates.${v}`) }))}
          />
        </div>
        <FilterChips
          label={t('common.category')}
          value={cat}
          onChange={setCat}
          options={[{ value: 'all', label: t('common.all') }, ...eventCats.map((c) => ({ value: c, label: t(`events.categories.${c}`) }))]}
        />
      </div>
      <Async
        query={q}
        skeleton={<CardGridSkeleton />}
        isEmpty={() => list.length === 0}
        empty={
          <Card>
            <EmptyState
              icon={CalendarDays}
              title={t('states.emptyEvents')}
              body={t('states.emptyEventsBody')}
              action={<Button variant="secondary" onClick={() => { setQuery(''); setCat('all'); setRange('any') }}>{t('common.clearFilters')}</Button>}
            />
          </Card>
        }
      >
        {() => (
          <>
            <p className="mb-3 text-[13px] text-one-subtle" aria-live="polite">{t('common.results', { count: list.length })}</p>
            <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {list.map((e) => (
                <li key={e.id}>
                  <EventCard event={e} onView={() => setParams({ e: e.id })} />
                </li>
              ))}
            </ul>
          </>
        )}
      </Async>
      <EventModal event={viewing} onClose={() => setParams({})} />
    </>
  )
}

export function EventModal({ event, onClose }: { event: CampusEvent | null; onClose: () => void }) {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const registered = useApp((s) => (event ? s.registered.includes(event.id) : false))
  const toggle = useApp((s) => s.toggle)
  if (!event) return null
  return (
    <Modal
      open
      onClose={onClose}
      size="lg"
      title={event.title}
      footer={
        <>
          <Button
            variant="ghost"
            icon={Share2}
            onClick={() => {
              void navigator.clipboard?.writeText(`${window.location.origin}/one/campus/events?e=${event.id}`)
              toast(t('common.linkCopied'), 'info')
            }}
          >
            {t('common.share')}
          </Button>
          <Button variant="secondary" icon={CalendarPlus} onClick={() => toast(t('events.calendarToast'), 'info')}>
            {t('events.addToCalendar')}
          </Button>
          <Button
            icon={registered ? UserCheck : Sparkles}
            variant={registered ? 'secondary' : 'primary'}
            aria-pressed={registered}
            onClick={() => {
              toggle('registered', event.id)
              if (!registered) toast(t('events.registeredToast', { name: event.title }))
            }}
          >
            {registered ? t('common.registered') : t('common.register')}
          </Button>
        </>
      }
    >
      <Cover tone={event.tone} icon={eventIcon[event.category]} className="-mt-1 mb-5 aspect-[21/9] rounded-2xl" />
      <div className="flex flex-wrap gap-2">
        <Badge tone="teal">{t(`events.categories.${event.category}`)}</Badge>
        <Badge tone="warn">{t('events.seatsLeft', { count: event.seats })}</Badge>
      </div>
      <dl className="mt-4 grid gap-3 sm:grid-cols-2">
        {[
          { icon: CalendarDays, label: t('common.date'), value: fmt.date(event.date, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) },
          { icon: Clock, label: t('common.time'), value: `${fmt.time(event.date)} – ${fmt.time(event.end)}` },
          { icon: MapPin, label: t('common.location'), value: event.location },
          { icon: Users, label: t('events.organizer'), value: event.organizer },
        ].map((m) => (
          <div key={m.label} className="flex items-start gap-3 rounded-xl bg-one-sunken/60 p-3">
            <m.icon size={16} aria-hidden className="mt-0.5 text-one-subtle" />
            <div>
              <dt className="text-[12px] text-one-subtle">{m.label}</dt>
              <dd className="text-[14px] font-medium text-one-fg">{m.value}</dd>
            </div>
          </div>
        ))}
      </dl>
      <h3 className="t-h3 mt-5 text-one-fg">{t('events.about')}</h3>
      <p className="t-body mt-1.5 text-one-muted">{event.description}</p>
    </Modal>
  )
}

/* ============================ Facilities ============================ */

const facilityIcon: Record<FacilityType, LucideIcon> = {
  library: BookOpen,
  laboratory: FlaskConical,
  sports: Dumbbell,
  auditorium: Theater,
  cafeteria: Coffee,
  clinic: Stethoscope,
  studentCenter: Users,
  ministry: Church,
}

export function FacilitiesPage() {
  const { t } = useI18n()
  const [type, setType] = useState<'all' | FacilityType>('all')
  const [booking, setBooking] = useState<Facility | null>(null)
  const q = useMockData(() => facilities, [] as Facility[])
  const hour = new Date().getHours()
  const list = q.data.filter((f) => type === 'all' || f.type === type)

  return (
    <>
      <PageHeader title={t('facilities.title')} description={t('facilities.subtitle')} crumbs={[{ label: t('nav.campus'), to: '/one/campus' }, { label: t('facilities.title') }]} />
      <FilterChips
        className="mb-6"
        label={t('common.category')}
        value={type}
        onChange={setType}
        options={[{ value: 'all', label: t('common.all') }, ...(Object.keys(facilityIcon) as FacilityType[]).map((k) => ({ value: k, label: t(`facilities.types.${k}`) }))]}
      />
      <Async query={q} skeleton={<CardGridSkeleton />} isEmpty={() => list.length === 0} empty={<Card><EmptyState icon={Building2} title={t('states.emptyFacilities')} /></Card>}>
        {() => (
          <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {list.map((f) => {
              const open = hour >= f.open && hour < f.close
              return (
                <li key={f.id}>
                  <Card as="article" className="flex h-full flex-col overflow-hidden">
                    <Cover tone={f.tone} icon={facilityIcon[f.type]} className="aspect-[16/8]">
                      <span className="absolute left-3 top-3">
                        <Badge tone="neutral" className="bg-white/90 text-one-ink ring-0">{t(`facilities.types.${f.type}`)}</Badge>
                      </span>
                    </Cover>
                    <div className="flex flex-1 flex-col p-5">
                      <div className="flex items-start justify-between gap-3">
                        <h2 className="text-[16px] font-semibold text-one-fg">{f.name}</h2>
                        <Badge tone={open ? 'ok' : 'neutral'} dot>{open ? t('facilities.openNow') : t('facilities.closedNow')}</Badge>
                      </div>
                      <p className="t-small mt-2 text-one-muted">{f.description}</p>
                      <div className="mt-4 space-y-1.5">
                        <MetaItem icon={MapPin}>{f.location}</MetaItem>
                        <MetaItem icon={Clock}>{f.days} · {String(f.open).padStart(2, '0')}:00–{String(f.close).padStart(2, '0')}:00</MetaItem>
                        <MetaItem icon={Mail}>{f.contact}</MetaItem>
                      </div>
                      <p className="t-caption mt-4 text-one-subtle">{t('facilities.services')}</p>
                      <ul className="mt-2 flex flex-wrap gap-1.5">
                        {f.services.map((s) => (
                          <li key={s} className="rounded-lg bg-one-sunken px-2 py-1 text-[12px] text-one-muted">{s}</li>
                        ))}
                      </ul>
                      {f.bookable && (
                        <div className="mt-auto pt-5">
                          <Button variant="secondary" block icon={CalendarPlus} onClick={() => setBooking(f)}>{t('facilities.book')}</Button>
                        </div>
                      )}
                    </div>
                  </Card>
                </li>
              )
            })}
          </ul>
        )}
      </Async>
      <BookingModal facility={booking} onClose={() => setBooking(null)} />
    </>
  )
}

function BookingModal({ facility, onClose }: { facility: Facility | null; onClose: () => void }) {
  const { t } = useI18n()
  const toast = useToast()
  const [date, setDate] = useState('')
  const [slot, setSlot] = useState('')
  const [purpose, setPurpose] = useState('')
  const [errors, setErrors] = useState<Record<string, string>>({})

  const close = () => {
    setDate('')
    setSlot('')
    setPurpose('')
    setErrors({})
    onClose()
  }

  const submit = () => {
    const e: Record<string, string> = {}
    if (!date) e.date = t('validation.required')
    if (!slot) e.slot = t('validation.required')
    if (purpose.trim().length < 3) e.purpose = t('validation.required')
    setErrors(e)
    if (Object.keys(e).length) return
    toast(t('facilities.bookedToast', { name: facility?.name ?? '' }))
    close()
  }

  return (
    <Modal
      open={!!facility}
      onClose={close}
      title={t('facilities.bookTitle', { name: facility?.name ?? '' })}
      description={facility?.location}
      footer={
        <>
          <Button variant="secondary" onClick={close}>{t('common.cancel')}</Button>
          <Button onClick={submit}>{t('common.submit')}</Button>
        </>
      }
    >
      <form
        className="grid gap-4 sm:grid-cols-2"
        onSubmit={(e) => {
          e.preventDefault()
          submit()
        }}
        noValidate
      >
        <Field label={t('facilities.bookDate')} error={errors.date} required>
          <Input type="date" value={date} min={new Date().toISOString().slice(0, 10)} onChange={(e) => setDate(e.target.value)} />
        </Field>
        <Field label={t('facilities.bookTime')} error={errors.slot} required>
          <Select value={slot} onChange={(e) => setSlot(e.target.value)}>
            <option value="">—</option>
            {['08:00–10:00', '10:00–12:00', '13:00–15:00', '15:00–17:00', '18:00–20:00'].map((s) => (
              <option key={s}>{s}</option>
            ))}
          </Select>
        </Field>
        <Field label={t('facilities.bookPurpose')} error={errors.purpose} required className="sm:col-span-2">
          <Textarea value={purpose} onChange={(e) => setPurpose(e.target.value)} />
        </Field>
        <button type="submit" className="hidden" aria-hidden tabIndex={-1} />
      </form>
    </Modal>
  )
}

