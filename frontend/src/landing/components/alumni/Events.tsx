import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, CalendarCheck, CalendarDays, CheckCircle2, Clock, Handshake, MapPin, Megaphone, MessagesSquare, Mic, MonitorPlay, Presentation, Sparkles, Ticket, Users, UsersRound, Video, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { useCountdown } from '../../hooks/useAlumniDemo'
import { eventStatus, findAlumnus, type EventMode, type EventType, type ResolvedEvent } from '../../data/alumni'
import { LANDING_PATHS } from '../../paths'
import { formatEventDate, isEmail } from '../../lib/alumniFormat'
import { alumnusPath, eventPath } from '../../lib/alumniPaths'
import { formatClock } from '../../lib/affairsFormat'
import { useAlumniStore } from '../../store/alumni'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Dialog } from '../ui/Overlay'
import { DemoBadge, StatusBadge } from '../student-affairs/Badges'
import { CoverArt } from '../student-affairs/Covers'
import { InfoItem, InfoList, Paragraphs } from '../student-affairs/Detail'
import type { CategoryStyle } from '../student-affairs/styles'
import { TextField } from './FormFields'
import { Meter } from './Charts'
import { Portrait } from './Portrait'
import { FavoriteButton, Skeleton, SkeletonFrame } from './Shared'

const EVENT_STYLE: Record<EventType, CategoryStyle> = {
  gathering: { icon: UsersRound, tone: 'brand' },
  reuni: { icon: Sparkles, tone: 'gold' },
  'career-talk': { icon: Mic, tone: 'band' },
  networking: { icon: Handshake, tone: 'accent' },
  seminar: { icon: Presentation, tone: 'deep' },
  'job-fair': { icon: Megaphone, tone: 'band' },
  webinar: { icon: MonitorPlay, tone: 'soft' },
  mentoring: { icon: MessagesSquare, tone: 'sunken' },
}

const MODE_ICON: Record<EventMode, LucideIcon> = { onsite: MapPin, online: Video, hybrid: Video }

/** The date stamped on a banner. */
function DateTile({ date, className }: { date: Date; className?: string }) {
  const { intl } = useLandingI18n()
  return (
    <span aria-hidden className={cn('flex w-14 flex-col items-center rounded-lp-sm bg-lp-surface py-1.5 text-center shadow-lp-md', className)}>
      <span className="text-[0.6875rem] font-bold uppercase tracking-wider text-lp-brand-text">{new Intl.DateTimeFormat(intl, { month: 'short' }).format(date)}</span>
      <span className="font-display text-[1.375rem] font-extrabold leading-none text-lp-fg">{date.getDate()}</span>
    </span>
  )
}

function RegisteredPill() {
  const { m } = useLandingI18n()
  return (
    <span className="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full bg-lp-ok/10 px-2.5 py-1 text-[0.75rem] font-semibold leading-none text-lp-ok ring-1 ring-inset ring-lp-ok/25">
      <CheckCircle2 size={13} aria-hidden />
      {m.alumni.events.registered}
    </span>
  )
}

/** Event card: banner, date, title, when and where, and the "Lihat Detail" button that opens the dialog. */
export function EventCard({ event, onOpen, featured }: { event: ResolvedEvent; onOpen: () => void; featured?: boolean }) {
  const { m, intl, locale } = useLandingI18n()
  const e = m.alumni.events
  const status = eventStatus(event)
  const registered = useAlumniStore((s) => Boolean(s.registrations[event.slug]))
  const ModeIcon = MODE_ICON[event.mode]

  return (
    <article className="group relative flex w-full flex-col overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg">
      <div className="relative">
        <CoverArt style={EVENT_STYLE[event.type]} photo={event.photo} className={cn(featured ? 'aspect-[16/8]' : 'aspect-[16/9]', status === 'completed' && 'saturate-[.55]')} />
        <DateTile date={event.startsAt} className="absolute left-3 top-3" />
        <FavoriteButton id={`event:${event.slug}`} name={event.title} className="absolute right-3 top-3" />
        <StatusBadge status={status} className="absolute bottom-3 left-3" />
      </div>
      <div className="flex flex-1 flex-col p-5">
        <div className="flex flex-wrap items-center gap-2">
          <p className="lp-eyebrow text-lp-brand-text">{e.types[event.type]}</p>
          {registered && <RegisteredPill />}
        </div>
        <h3 lang="id" className={cn('mt-2 text-lp-fg [overflow-wrap:anywhere]', featured ? 'lp-h3' : 'lp-h4')}>
          {event.title}
        </h3>
        <p lang="id" className="lp-small mt-2 line-clamp-2 text-lp-muted">
          {event.excerpt}
        </p>
        <dl className="mt-4 space-y-1.5 text-[0.8125rem] text-lp-muted">
          <div>
            <dt className="sr-only">{e.date}</dt>
            <dd className="flex items-start gap-2">
              <CalendarDays size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              <span>
                <time dateTime={event.startsAt.toISOString()}>{formatEventDate(event, intl)}</time> · {fill(e.timeRange, { start: formatClock(event.start.time, locale), end: formatClock(event.end.time, locale) })}
              </span>
            </dd>
          </div>
          <div>
            <dt className="sr-only">{e.venue}</dt>
            <dd lang="id" className="flex items-start gap-2">
              <ModeIcon size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
              {event.venue}
            </dd>
          </div>
        </dl>
        <div className="mt-auto pt-5">
          <Button variant="secondary" className="group/btn w-full" onClick={onOpen} aria-label={`${e.viewDetail}: ${event.title}`}>
            {e.viewDetail}
            <ArrowRight size={16} aria-hidden className="transition-transform duration-200 group-hover/btn:translate-x-1" />
          </Button>
        </div>
      </div>
    </article>
  )
}

/** One line of an agenda list; links to the events page with the dialog open. */
export function EventRow({ event }: { event: ResolvedEvent }) {
  const { m, intl } = useLandingI18n()
  const countdown = useCountdown()
  const registered = useAlumniStore((s) => Boolean(s.registrations[event.slug]))
  return (
    <Link to={eventPath(event.slug)} className="group flex items-center gap-4 rounded-lp-lg border border-lp-line bg-lp-surface p-4 shadow-lp-sm transition-[box-shadow,border-color] duration-200 hover:border-lp-brand/30 hover:shadow-lp-md">
      <DateTile date={event.startsAt} className="shrink-0 bg-lp-brand-soft shadow-none" />
      <div className="min-w-0 flex-1">
        <p className="text-[0.75rem] font-semibold uppercase tracking-[0.08em] text-lp-brand-text">{m.alumni.events.types[event.type]}</p>
        <p lang="id" className="mt-1 text-[0.9375rem] font-semibold leading-snug text-lp-fg group-hover:text-lp-brand-text">
          {event.title}
        </p>
        <p className="mt-1 text-[0.8125rem] text-lp-muted">
          {formatEventDate(event, intl)} · {countdown(event.startsAt)}
        </p>
      </div>
      {registered ? <CheckCircle2 size={18} aria-label={m.alumni.events.registered} className="shrink-0 text-lp-ok" /> : <ArrowRight size={17} aria-hidden className="shrink-0 text-lp-subtle transition-transform duration-200 group-hover:translate-x-1" />}
    </Link>
  )
}

export function EventGridSkeleton({ count = 6 }: { count?: number }) {
  return (
    <SkeletonFrame className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface">
          <Skeleton className="aspect-[16/9] rounded-none" />
          <div className="space-y-3 p-5">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-5 w-4/5" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-11 w-full" />
          </div>
        </div>
      ))}
    </SkeletonFrame>
  )
}

/* -------------------------------- Dialog --------------------------------- */

/** Registration is simulated: it only changes state in this browser and shows a confirmation. */
function RegistrationForm({ event }: { event: ResolvedEvent }) {
  const { m } = useLandingI18n()
  const f = m.alumni.events.form
  const register = useAlumniStore((s) => s.register)
  const toast = useLandingUi((s) => s.toast)
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [cohort, setCohort] = useState('')
  const [errors, setErrors] = useState<{ name?: string; email?: string }>({})

  const submit = (e: FormEvent) => {
    e.preventDefault()
    const next = {
      name: name.trim() ? undefined : f.nameError,
      email: isEmail(email) ? undefined : f.emailError,
    }
    setErrors(next)
    if (next.name || next.email) return
    register(event.slug)
    toast(fill(f.toast, { title: event.title }))
  }

  return (
    <form onSubmit={submit} noValidate className="rounded-lp-lg border border-lp-line bg-lp-sunken/60 p-4 sm:p-5">
      <h3 className="lp-h4 text-lp-fg">{f.title}</h3>
      <div className="mt-4 grid gap-4 sm:grid-cols-2">
        <TextField label={f.name} value={name} onChange={setName} error={errors.name} autoComplete="name" required requiredLabel={f.required} />
        <TextField label={f.email} type="email" value={email} onChange={setEmail} error={errors.email} autoComplete="email" required requiredLabel={f.required} />
        <div className="sm:col-span-2">
          <TextField label={f.cohort} value={cohort} onChange={setCohort} placeholder={f.cohortPlaceholder} />
        </div>
      </div>
      <Button type="submit" className="mt-5 w-full sm:w-auto">
        <Ticket size={16} aria-hidden />
        {f.submit}
      </Button>
      <p className="mt-3 text-[0.8125rem] text-lp-muted">{f.note}</p>
    </form>
  )
}

function Confirmation({ code }: { code: string }) {
  const { m } = useLandingI18n()
  const f = m.alumni.events.form
  return (
    <div role="status" className="animate-rise-in rounded-lp-lg border border-lp-ok/30 bg-lp-ok/[0.07] p-5 text-center">
      <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-lp-ok/10 text-lp-ok">
        <CalendarCheck size={24} aria-hidden />
      </span>
      <h3 className="lp-h4 mt-3 text-lp-fg">{f.confirmedTitle}</h3>
      <p className="lp-small mx-auto mt-1 max-w-sm text-lp-muted">{f.confirmedBody}</p>
      <p className="mt-4 inline-flex items-center gap-2 rounded-full bg-lp-surface px-4 py-2 font-display text-[0.9375rem] font-bold tracking-wider text-lp-fg ring-1 ring-lp-line">
        <Ticket size={16} aria-hidden className="text-lp-brand-text" />
        {code}
      </p>
    </div>
  )
}

export function EventDialog({ event, onClose }: { event: ResolvedEvent | null; onClose: () => void }) {
  const { m, intl, fmt, locale } = useLandingI18n()
  const e = m.alumni.events
  const code = useAlumniStore((s) => (event ? s.registrations[event.slug] : undefined))
  if (!event) return null

  const status = eventStatus(event)
  const speakers = (event.speakers ?? []).map((slug) => findAlumnus(slug)).filter((p) => p !== undefined)
  const ModeIcon = MODE_ICON[event.mode]

  return (
    <Dialog
      open
      onClose={onClose}
      size="lg"
      title={<span lang="id">{event.title}</span>}
      description={e.types[event.type]}
      footer={
        <Button variant="secondary" onClick={onClose}>
          {m.common.close}
        </Button>
      }
    >
      <div className="relative">
        <CoverArt style={EVENT_STYLE[event.type]} photo={event.photo} size="hero" sizes="(min-width: 640px) 42rem, 100vw" className="aspect-[21/9] rounded-lp" />
        <div className="absolute inset-x-3 top-3 flex flex-wrap items-start justify-between gap-2">
          <StatusBadge status={status} />
          <DemoBadge className="bg-lp-surface" />
        </div>
      </div>

      <InfoList className="mt-6">
        <InfoItem icon={CalendarDays} label={e.date}>
          <time dateTime={event.startsAt.toISOString()}>{formatEventDate(event, intl, 'long')}</time>
        </InfoItem>
        <InfoItem icon={Clock} label={e.time}>
          {fill(e.timeRange, { start: formatClock(event.start.time, locale), end: formatClock(event.end.time, locale) })}
        </InfoItem>
        <InfoItem icon={ModeIcon} label={e.venue}>
          <span lang="id">{event.venue}</span>
          <span className="ml-2 text-[0.8125rem] text-lp-subtle">({e.modes[event.mode]})</span>
        </InfoItem>
      </InfoList>

      <div className="mt-6">
        <Paragraphs items={event.description} />
      </div>

      {event.recap && status === 'completed' && (
        <div className="mt-5 rounded-lp border border-lp-line bg-lp-sunken p-4">
          <p className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{e.recap}</p>
          <p lang="id" className="lp-small mt-1.5 text-lp-fg">
            {event.recap}
          </p>
        </div>
      )}

      {event.agenda && (
        <div className="mt-6">
          <h3 className="lp-h4 text-lp-fg">{e.agenda}</h3>
          <ol className="mt-3 space-y-2">
            {event.agenda.map((item) => (
              <li key={item.time} className="flex gap-4 text-[0.9375rem]">
                <span className="lp-tabular w-14 shrink-0 font-semibold text-lp-brand-text">{formatClock(item.time, locale)}</span>
                <span lang="id" className="text-lp-muted">
                  {item.title}
                </span>
              </li>
            ))}
          </ol>
        </div>
      )}

      {speakers.length > 0 && (
        <div className="mt-6">
          <h3 className="lp-h4 text-lp-fg">{e.speakers}</h3>
          <ul className="mt-3 grid gap-2 sm:grid-cols-2">
            {speakers.map((person) => (
              <li key={person.slug}>
                <Link to={alumnusPath(person.slug)} className="group flex items-center gap-3 rounded-lp border border-lp-line p-2 transition-colors hover:border-lp-brand/30 hover:bg-lp-sunken">
                  <Portrait person={person} className="h-12 w-12 shrink-0 rounded-full" />
                  <span className="min-w-0">
                    <span lang="id" className="block truncate text-[0.875rem] font-semibold text-lp-fg group-hover:text-lp-brand-text">
                      {person.name}
                    </span>
                    <span lang="id" className="block truncate text-[0.8125rem] text-lp-muted">
                      {person.position}
                    </span>
                  </span>
                </Link>
              </li>
            ))}
          </ul>
        </div>
      )}

      {status === 'upcoming' && (
        <div className="mt-6">
          <Meter value={event.taken} max={event.capacity} label={e.capacity}>
            <span className="inline-flex items-center gap-1.5">
              <Users size={14} aria-hidden />
              {fill(e.seats, { taken: fmt.number(event.taken), capacity: fmt.number(event.capacity) })}
            </span>
            <span className="font-semibold text-lp-fg">{fmt.percent((event.taken / event.capacity) * 100, 0)}</span>
          </Meter>
          <div className="mt-5">{code ? <Confirmation code={code} /> : <RegistrationForm event={event} />}</div>
        </div>
      )}

      {status === 'completed' && (
        <Link to={LANDING_PATHS.alumniEvents} onClick={onClose} className={buttonClass('secondary', 'md', 'mt-6 w-full sm:w-auto')}>
          {e.seeUpcoming}
        </Link>
      )}
    </Dialog>
  )
}
