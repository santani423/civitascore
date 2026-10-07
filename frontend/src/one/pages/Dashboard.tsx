import { useMemo } from 'react'
import { Link } from 'react-router-dom'
import {
  ArrowRight,
  ArrowUpRight,
  Award,
  BookOpen,
  Briefcase,
  CalendarDays,
  ClipboardList,
  Clock,
  GraduationCap,
  Layers,
  Library,
  MapPin,
  Megaphone,
  Sparkles,
  TrendingUp,
  UserCheck,
  Users,
  Wallet,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { student } from '../data/people'
import { assignments, courseById, exams } from '../data/academic'
import { announcements, events, news } from '../data/campus'
import { invoices } from '../data/services'
import { buildAgenda, type AgendaItem } from '../lib/agenda'
import { daysUntil, timeOfDay } from '../lib/dates'
import { kindTone, tone as tones } from '../lib/tones'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Async, DashboardSkeleton, EmptyState } from '../components/ui/Feedback'
import { Progress, Stat } from '../components/ui/Display'
import { Button } from '../components/ui/Button'
import { kindIcon } from '../components/ui/Calendar'
import { NewsCard, SectionTitle, newsIcon, useDue } from '../components/shared/bits'
import { Cover } from '../components/ui/Display'
import type { TKey } from '../i18n'

function buildDashboard() {
  const today = new Date()
  const todays = buildAgenda(today, today).filter((i) => i.kind === 'lecture')
  const horizon = new Date(today)
  horizon.setDate(horizon.getDate() + 14)

  const upcoming: AgendaItem[] = []
  const openAssignments = assignments.filter((a) => (a.status === 'upcoming' || a.status === 'inProgress') && daysUntil(a.due) >= 0)
  for (const a of openAssignments.slice(0, 2))
    upcoming.push({ id: a.id, kind: 'assignment', title: a.title, subtitle: courseById(a.courseId)?.name, start: new Date(a.due), to: '/one/assignments' })
  const exam = exams.find((e) => daysUntil(e.start) >= 0)
  if (exam) upcoming.push({ id: exam.id, kind: 'exam', title: courseById(exam.courseId)?.name ?? '', subtitle: exam.room, start: new Date(exam.start), to: '/one/exams' })
  const ev = events.find((e) => daysUntil(e.date) >= 0)
  if (ev) upcoming.push({ id: ev.id, kind: 'event', title: ev.title, subtitle: ev.location, start: new Date(ev.date), to: `/one/campus/events?e=${ev.id}` })
  const inv = invoices.find((i) => i.status === 'unpaid')
  if (inv) upcoming.push({ id: inv.id, kind: 'payment', title: inv.description, subtitle: inv.id, start: new Date(inv.due), to: '/one/finance', allDay: true })
  upcoming.sort((a, b) => a.start.getTime() - b.start.getTime())

  const tasksThisWeek = openAssignments.filter((a) => daysUntil(a.due) <= 7).length
  return { todays, upcoming, tasksThisWeek, nextPayment: inv?.due }
}

const empty = { todays: [] as AgendaItem[], upcoming: [] as AgendaItem[], tasksThisWeek: 0, nextPayment: undefined as string | undefined }

export function DashboardPage() {
  const { t, fmt } = useI18n()
  const q = useMockData(buildDashboard, empty)
  const pct = Math.round((student.creditsEarned / student.creditsRequired) * 100)

  return (
    <Async query={q} skeleton={<DashboardSkeleton />}>
      {(d) => (
        <div className="space-y-8">
          <Hero classes={d.todays.length} tasks={d.tasksThisWeek} nextPayment={d.nextPayment} next={d.todays.find((i) => (i.end ?? i.start) > new Date())} />

          <section aria-labelledby="overview-h">
            <SectionTitle
              id="overview-h"
              title={t('dashboard.academicOverview')}
              action={
                <Link to="/one/academic" className="text-[13px] font-medium text-one-royal hover:underline">
                  {t('common.viewAll')}
                </Link>
              }
            />
            <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
              <Stat label={t('dashboard.gpa')} value={fmt.number(student.gpa, 2)} icon={TrendingUp} tone="royal" hint={`/ ${fmt.number(4, 2)}`} />
              <Stat label={t('dashboard.totalCredits')} value={student.creditsEarned} icon={Layers} tone="teal" hint={t('dashboard.creditsOf', { done: student.creditsEarned, total: student.creditsRequired })} />
              <Stat label={t('dashboard.currentSemester')} value={student.semester} icon={GraduationCap} tone="gold" hint={student.program} />
              <Stat label={t('dashboard.academicProgress')} value={`${pct}%`} icon={Award} tone="ok">
                <Progress value={pct} tone="ok" label={t('dashboard.academicProgress')} className="mt-3" />
              </Stat>
            </div>
          </section>

          <div className="grid gap-6 lg:grid-cols-3">
            <TodaySchedule items={d.todays} />
            <Upcoming items={d.upcoming} />
          </div>

          <QuickAccess />

          <div className="grid gap-6 lg:grid-cols-3">
            <Announcements />
            <CampusNews />
          </div>
        </div>
      )}
    </Async>
  )
}

function Hero({ classes, tasks, nextPayment, next }: { classes: number; tasks: number; nextPayment?: string; next?: AgendaItem }) {
  const { t, fmt } = useI18n()
  return (
    <section aria-labelledby="hero-h" className="relative overflow-hidden rounded-3xl bg-one-hero text-white shadow-lift">
      <div aria-hidden className="absolute inset-0" style={{ backgroundImage: 'linear-gradient(120deg, transparent 30%, rgb(var(--one-brand) / 0.65) 100%)' }} />
      <div aria-hidden className="one-cover-pattern absolute inset-0 opacity-40 [mask-image:linear-gradient(to_left,black,transparent_70%)]" />
      <div aria-hidden className="absolute -right-24 -top-24 h-72 w-72 rounded-full border-[28px] border-white/[0.06]" />
      <div className="relative grid gap-6 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-end lg:p-10">
        <div>
          <p className="t-caption text-white/70">{fmt.date(new Date(), { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
          <h1 id="hero-h" className="t-display mt-2">
            {t(`greeting.${timeOfDay()}` as TKey, { name: student.firstName })} <span aria-hidden>👋</span>
          </h1>
          <p className="t-body mt-2 max-w-xl text-white/80">{t('dashboard.subtitle')}</p>
          <ul className="mt-5 flex flex-wrap gap-2">
            {[
              { icon: BookOpen, label: t('dashboard.classesToday', { count: classes }) },
              { icon: ClipboardList, label: t('dashboard.tasksDue', { count: tasks }) },
              ...(nextPayment ? [{ icon: Wallet, label: t('dashboard.nextPayment', { date: fmt.date(nextPayment, { day: 'numeric', month: 'short' }) }) }] : []),
            ].map((c) => (
              <li key={c.label} className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-[13px] font-medium text-white ring-1 ring-inset ring-white/15 backdrop-blur">
                <c.icon size={14} aria-hidden />
                {c.label}
              </li>
            ))}
          </ul>
        </div>
        {next && (
          <Link
            to={next.to}
            className="group relative block w-full rounded-2xl bg-white/10 p-4 ring-1 ring-inset ring-white/15 backdrop-blur-md transition-colors hover:bg-white/15 lg:w-80"
          >
            <p className="t-caption flex items-center gap-1.5 text-one-spark">
              <Sparkles size={13} aria-hidden /> {t('dashboard.next')}
            </p>
            <p className="mt-2 text-[17px] font-semibold leading-snug">{next.title}</p>
            <p className="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-white/75">
              <span className="inline-flex items-center gap-1.5">
                <Clock size={13} aria-hidden /> {fmt.time(next.start)}–{next.end && fmt.time(next.end)}
              </span>
              <span className="inline-flex items-center gap-1.5">
                <MapPin size={13} aria-hidden /> {next.location}
              </span>
            </p>
            <ArrowUpRight size={18} aria-hidden className="absolute right-4 top-4 text-white/60 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
          </Link>
        )}
      </div>
    </section>
  )
}

function TodaySchedule({ items }: { items: AgendaItem[] }) {
  const { t, fmt } = useI18n()
  const now = new Date()
  const nextIndex = items.findIndex((i) => i.start > now)

  return (
    <Card as="section" aria-labelledby="today-h" className="lg:col-span-2">
      <CardHeader id="today-h" title={t('dashboard.todaySchedule')} description={fmt.date(now, { weekday: 'long', day: 'numeric', month: 'long' })} href="/one/schedule" hrefLabel={t('dashboard.fullSchedule')} />
      <CardBody>
        {items.length === 0 ? (
          <EmptyState
            compact
            icon={CalendarDays}
            title={t('states.emptySchedule')}
            body={t('states.emptyScheduleBody')}
            action={<Button variant="secondary" size="sm" to="/one/campus/events">{t('states.browseEvents')}</Button>}
          />
        ) : (
          <ol className="space-y-2">
            {items.map((item, i) => {
              const live = item.start <= now && (item.end ?? item.start) >= now
              const done = (item.end ?? item.start) < now
              const isNext = !live && i === nextIndex
              const state = live ? 'now' : isNext ? 'next' : done ? 'done' : null
              return (
                <li key={item.id}>
                  <Link
                    to={item.to}
                    className={cn(
                      'group flex items-center gap-4 rounded-2xl border p-3.5 transition-colors sm:p-4',
                      live ? 'border-one-royal/30 bg-one-royal/[0.06]' : 'border-one-line hover:border-one-line2 hover:bg-one-sunken/40',
                      done && 'opacity-60',
                    )}
                  >
                    <div className="w-14 shrink-0 text-center sm:w-16">
                      <p className="font-display text-[17px] font-bold text-one-fg tabular">{fmt.time(item.start)}</p>
                      <p className="text-[12px] text-one-subtle tabular">{item.end && fmt.time(item.end)}</p>
                    </div>
                    <span aria-hidden className={cn('h-10 w-1 shrink-0 rounded-full', live ? 'bg-one-royal' : 'bg-one-line')} />
                    <div className="min-w-0 flex-1">
                      <p className="truncate text-[15px] font-semibold text-one-fg group-hover:text-one-royal">{item.title}</p>
                      <p className="mt-0.5 flex flex-wrap items-center gap-x-3 text-[13px] text-one-subtle">
                        <span>{item.subtitle}</span>
                        <span className="inline-flex items-center gap-1">
                          <MapPin size={13} aria-hidden />
                          {item.location}
                        </span>
                      </p>
                    </div>
                    {state && (
                      <Badge tone={state === 'now' ? 'royal' : state === 'next' ? 'gold' : 'neutral'} dot={state === 'now'}>
                        {t(`dashboard.${state}`)}
                      </Badge>
                    )}
                  </Link>
                </li>
              )
            })}
          </ol>
        )}
      </CardBody>
    </Card>
  )
}

function Upcoming({ items }: { items: AgendaItem[] }) {
  const { t, fmt } = useI18n()
  const due = useDue()
  return (
    <Card as="section" aria-labelledby="upcoming-h">
      <CardHeader id="upcoming-h" title={t('dashboard.upcoming')} href="/one/schedule" hrefLabel={t('common.viewAll')} />
      <CardBody className="pt-3">
        {items.length === 0 ? (
          <EmptyState compact title={t('states.emptyAssignments')} body={t('states.emptyAssignmentsBody')} />
        ) : (
          <ul className="divide-y divide-one-line">
            {items.map((item) => {
              const Icon = kindIcon[item.kind]
              const d = due(item.start.toISOString())
              return (
                <li key={item.id}>
                  <Link to={item.to} className="group flex items-start gap-3 py-3">
                    <span className={cn('flex h-9 w-9 shrink-0 items-center justify-center rounded-xl', tones[kindTone[item.kind]].soft)}>
                      <Icon size={16} aria-hidden />
                    </span>
                    <span className="min-w-0 flex-1">
                      <span className={cn('t-caption', tones[kindTone[item.kind]].text)}>{t(`kind.${item.kind}`)}</span>
                      <span className="mt-0.5 block truncate text-[14px] font-medium text-one-fg group-hover:text-one-royal">{item.title}</span>
                      <span className="block truncate text-[12.5px] text-one-subtle">
                        {fmt.date(item.start, { weekday: 'short', day: 'numeric', month: 'short' })}
                        {!item.allDay && ` · ${fmt.time(item.start)}`}
                      </span>
                    </span>
                    <Badge tone={d.tone}>{d.label}</Badge>
                  </Link>
                </li>
              )
            })}
          </ul>
        )}
      </CardBody>
    </Card>
  )
}

function QuickAccess() {
  const { t } = useI18n()
  const tiles = [
    { to: '/one/academic', label: t('nav.academic'), icon: GraduationCap, tone: 'royal' as const },
    { to: '/one/schedule', label: t('nav.schedule'), icon: CalendarDays, tone: 'sky' as const },
    { to: '/one/attendance', label: t('nav.attendance'), icon: UserCheck, tone: 'teal' as const },
    { to: '/one/finance', label: t('nav.finance'), icon: Wallet, tone: 'warn' as const },
    { to: '/one/library', label: t('nav.library'), icon: Library, tone: 'brand' as const },
    { to: '/one/career', label: t('nav.career'), icon: Briefcase, tone: 'gold' as const },
    { to: '/one/campus/organizations', label: t('nav.organizations'), icon: Users, tone: 'ok' as const },
  ]
  return (
    <section aria-labelledby="quick-h">
      <SectionTitle id="quick-h" title={t('dashboard.quickAccess')} />
      <ul className="grid grid-cols-4 gap-2 sm:gap-3 md:grid-cols-7">
        {tiles.map((tile) => (
          <li key={tile.to}>
            <Link
              to={tile.to}
              className="group flex h-full flex-col items-center gap-2.5 rounded-2xl border border-one-line bg-one-card px-2 py-4 text-center shadow-soft transition-[border-color,box-shadow,transform] hover:-translate-y-0.5 hover:border-one-line2 hover:shadow-lift"
            >
              <span className={cn('flex h-11 w-11 items-center justify-center rounded-2xl transition-transform group-hover:scale-105', tones[tile.tone].soft)}>
                <tile.icon size={20} aria-hidden />
              </span>
              <span className="text-[12px] font-medium leading-tight text-one-fg sm:text-[13px]">{tile.label}</span>
            </Link>
          </li>
        ))}
      </ul>
    </section>
  )
}

const annTone = { academic: 'royal', finance: 'warn', exam: 'bad', campus: 'teal' } as const
const annLabel: Record<keyof typeof annTone, TKey> = {
  academic: 'notifCategory.academic',
  finance: 'notifCategory.finance',
  exam: 'notifCategory.exam',
  campus: 'newsCategory.campus',
}

function Announcements() {
  const { t, fmt } = useI18n()
  return (
    <Card as="section" aria-labelledby="ann-h">
      <CardHeader
        id="ann-h"
        title={t('dashboard.announcements')}
        href="/one/news"
        hrefLabel={t('common.viewAll')}
        icon={
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-one-gold/10 text-one-gold">
            <Megaphone size={17} aria-hidden />
          </span>
        }
      />
      <CardBody className="space-y-3">
        {announcements.slice(0, 3).map((a) => (
          <article key={a.id} className="rounded-2xl border border-one-line p-4 transition-colors hover:border-one-line2">
            <div className="flex items-center justify-between gap-2">
              <Badge tone={annTone[a.category]}>{t(annLabel[a.category])}</Badge>
              <time dateTime={a.date} className="text-[12px] text-one-subtle">
                {fmt.date(a.date, { day: 'numeric', month: 'short' })}
              </time>
            </div>
            <h3 className="mt-2.5 text-[14.5px] font-semibold leading-snug text-one-fg">{a.title}</h3>
            <p className="t-small mt-1 line-clamp-2 text-one-muted">{a.body}</p>
            <Link to="/one/news" className="mt-2 inline-flex items-center gap-1 text-[13px] font-medium text-one-royal hover:underline">
              {t('common.readMore')} <ArrowRight size={13} aria-hidden />
            </Link>
          </article>
        ))}
      </CardBody>
    </Card>
  )
}

function CampusNews() {
  const { t, fmt } = useI18n()
  const [lead, ...rest] = useMemo(() => news.slice(0, 4), [])
  return (
    <section aria-labelledby="news-h" className="min-w-0 lg:col-span-2">
      <SectionTitle
        id="news-h"
        title={t('dashboard.campusNews')}
        action={
          <Link to="/one/news" className="text-[13px] font-medium text-one-royal hover:underline">
            {t('common.viewAll')}
          </Link>
        }
      />
      <div className="grid gap-5 md:grid-cols-5">
        <Link to={`/one/news/${lead.id}`} className="group relative block overflow-hidden rounded-3xl md:col-span-3">
          <Cover tone={lead.tone} icon={newsIcon[lead.category]} className="aspect-[4/3] h-full min-h-[300px] transition-transform duration-500 group-hover:scale-[1.02]" />
          <div aria-hidden className="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent" />
          <div className="absolute inset-x-0 bottom-0 p-5 text-white sm:p-6">
            <Badge tone="neutral" className="bg-white/90 text-one-ink ring-0">
              {t(`newsCategory.${lead.category}`)}
            </Badge>
            <h3 className="t-h2 mt-3 line-clamp-3 text-white">{lead.title}</h3>
            <p className="mt-2 line-clamp-2 text-[14px] text-white/80">{lead.excerpt}</p>
            <p className="mt-3 text-[12.5px] text-white/70">
              {fmt.date(lead.date)} · {t('news.readTime', { count: lead.readTime })}
            </p>
          </div>
        </Link>
        <div className="flex flex-col gap-3 md:col-span-2">
          {rest.map((a) => (
            <NewsCard key={a.id} article={a} layout="row" />
          ))}
        </div>
      </div>
    </section>
  )
}
