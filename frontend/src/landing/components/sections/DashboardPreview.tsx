import {
  BarChart3,
  Bell,
  BookOpen,
  Briefcase,
  CalendarCheck2,
  CheckCircle2,
  ClipboardCheck,
  FileCheck2,
  GraduationCap,
  LayoutDashboard,
  Megaphone,
  Search,
  Settings,
  Users,
  Wallet,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { CALENDAR_EVENTS, KPI, STUDENT_DAY, WEEKLY_ACTIVITY } from '../../data/demo'
import { termLabel, upcomingEvents } from '../../lib/terms'
import { Avatar, KpiTile, MockChip, MockPanel, MockWindow, PhoneFrame } from '../mock/Frame'
import { Legend, LineChart } from '../mock/Charts'
import { MiniCalendar } from '../mock/MiniCalendar'

const sidebar = [
  { key: 'overview', icon: LayoutDashboard },
  { key: 'academic', icon: GraduationCap },
  { key: 'students', icon: Users },
  { key: 'people', icon: Briefcase },
  { key: 'finance', icon: Wallet },
  { key: 'reports', icon: BarChart3 },
  { key: 'settings', icon: Settings },
] as const

/** The hero's product visual: a realistic institution overview, rendered as one labelled image. */
export function DashboardPreview() {
  const { m, fmt } = useLandingI18n()
  const d = m.dashboard
  const today = new Date()
  const weeks = WEEKLY_ACTIVITY.attendance.map((_, i) => i + 1)
  const agenda = upcomingEvents(
    CALENDAR_EVENTS.filter((e) => e.key === 'krs' || e.key === 'midterm' || e.key === 'meeting'),
    3,
    today,
  )
  const agendaLabel = { krs: d.agenda.krs, midterm: d.agenda.midterm, meeting: d.agenda.meeting } as Record<string, string>
  const monthMarks = CALENDAR_EVENTS.map((e) => ({ ...e, label: m.demo.academic.events[e.key] }))
  const trend = (v: number) => ({ up: v >= 0, text: fmt.percent(Math.abs(v)) })

  return (
    <div role="img" aria-label={`${m.common.previewLabel}. ${m.common.illustrativeNote}`} className="relative">
      {/* Floating notification */}
      <div className="lp-float absolute -left-10 bottom-32 z-10 hidden w-[17rem] xl:block">
        <div className="flex items-start gap-3 rounded-lp border border-lp-line bg-lp-elev p-3.5 shadow-lp-lg">
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-lp-ok/10 text-lp-ok">
            <CheckCircle2 size={18} />
          </span>
          <div className="min-w-0">
            <p className="text-[13px] font-semibold text-lp-fg">{d.toastTitle}</p>
            <p className="mt-0.5 text-[12px] leading-snug text-lp-muted">{d.toastBody}</p>
            <p className="mt-1.5 text-[11px] text-lp-subtle">{fill(d.timeAgo.minutes, { n: 1 })}</p>
          </div>
        </div>
      </div>

      {/* Floating phone */}
      <div className="lp-float-delayed absolute -bottom-56 -right-8 z-10 hidden lg:block">
        <PhoneFrame>
          <div className="px-3.5 pb-4">
            <p className="text-[11px] text-lp-subtle">{termLabel(m)}</p>
            <p className="mt-0.5 font-display text-[16px] font-bold text-lp-fg">{d.phoneGreeting}</p>
            <p className="mt-4 text-[11px] font-semibold uppercase tracking-wider text-lp-subtle">{d.phoneToday}</p>
            <div className="mt-2 space-y-2">
              {STUDENT_DAY.map((s, i) => (
                <div key={s.course} className={cn('rounded-xl border p-2.5', i === 0 ? 'border-lp-brand/30 bg-lp-brand/5' : 'border-lp-line bg-lp-surface')}>
                  <div className="flex items-center justify-between gap-2">
                    <span className="text-[10.5px] text-lp-subtle lp-tabular">
                      {s.start}–{s.end}
                    </span>
                    <MockChip tone={i === 0 ? 'brand' : 'muted'}>{i === 0 ? d.phoneNow : d.phoneNext}</MockChip>
                  </div>
                  <p className="mt-1 text-[12.5px] font-semibold leading-tight text-lp-fg">{m.demo.courses[s.course]}</p>
                  <p className="mt-0.5 text-[10.5px] text-lp-subtle">{s.room}</p>
                </div>
              ))}
            </div>
            <div className="mt-3 flex items-center gap-2 rounded-xl bg-lp-sunken p-2.5">
              <Megaphone size={14} className="shrink-0 text-lp-gold-text" />
              <p className="text-[11px] leading-snug text-lp-muted">{m.demo.admin.announcements.midterm}</p>
            </div>
          </div>
        </PhoneFrame>
      </div>

      <MockWindow title={`Civitas One Atma · ${d.windowTitle}`}>
        <div className="flex">
          <aside className="hidden w-48 shrink-0 border-r border-lp-line bg-lp-surface px-3 py-4 md:block lg:w-52">
            <ul className="space-y-0.5">
              {sidebar.map(({ key, icon: Icon }, i) => (
                <li
                  key={key}
                  className={cn(
                    'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[12.5px]',
                    i === 0 ? 'bg-lp-brand/10 font-semibold text-lp-brand-text' : 'text-lp-muted',
                  )}
                >
                  <Icon size={15} />
                  <span className="truncate">{d.nav[key]}</span>
                </li>
              ))}
            </ul>
          </aside>

          <div className="min-w-0 flex-1 p-3.5 sm:p-5">
            <div className="flex items-center gap-3">
              <div className="min-w-0 flex-1">
                <p className="truncate font-display text-[15px] font-bold text-lp-fg sm:text-[17px]">{d.windowTitle}</p>
                <p className="truncate text-[11.5px] text-lp-subtle">{termLabel(m)}</p>
              </div>
              <div className="hidden h-9 w-64 items-center gap-2 rounded-full border border-lp-line bg-lp-surface px-3 text-[12px] text-lp-subtle lg:flex">
                <Search size={14} />
                <span className="truncate">{d.search}</span>
              </div>
              <span className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-lp-line bg-lp-surface text-lp-muted">
                <Bell size={15} />
                <span className="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-lp-brand" />
              </span>
              <Avatar name="Biro Akademik" size={34} />
            </div>

            <div className="mt-4 grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-4">
              <KpiTile label={d.kpi.students} value={fmt.number(KPI.activeStudents)} trend={trend(KPI.activeStudentsDelta)} trendLabel={d.vsLastTerm} icon={Users} />
              <KpiTile label={d.kpi.lecturers} value={fmt.number(KPI.lecturers)} trend={trend(KPI.lecturersDelta)} trendLabel={d.vsLastTerm} icon={Briefcase} />
              <KpiTile label={d.kpi.classes} value={fmt.number(KPI.runningClasses)} trend={trend(KPI.runningClassesDelta)} trendLabel={d.vsLastTerm} icon={BookOpen} />
              <KpiTile label={d.kpi.attendance} value={fmt.percent(KPI.attendance)} trend={trend(KPI.attendanceDelta)} trendLabel={d.vsLastTerm} icon={CalendarCheck2} />
            </div>

            <div className="mt-3 grid gap-3 lg:grid-cols-3">
              <MockPanel
                className="lg:col-span-2"
                title={d.chartTitle}
                action={
                  <Legend
                    className="hidden sm:flex"
                    items={[
                      { key: 'a', label: d.series.attendance, slot: 1 },
                      { key: 's', label: d.series.submissions, slot: 2 },
                    ]}
                  />
                }
              >
                <p className="-mt-2 mb-2 text-[11px] text-lp-subtle">{d.chartSubtitle}</p>
                <LineChart
                  interactive={false}
                  height={262}
                  labels={weeks.map((n) => fill(d.weekShort, { n }))}
                  series={[
                    { key: 'a', label: d.series.attendance, values: WEEKLY_ACTIVITY.attendance, slot: 1 },
                    { key: 's', label: d.series.submissions, values: WEEKLY_ACTIVITY.submissions, slot: 2 },
                  ]}
                  max={50000}
                  ticks={[0, 25000, 50000]}
                  formatTick={fmt.compact}
                  formatValue={fmt.number}
                />
              </MockPanel>

              <MockPanel title={d.calendarTitle} action={<span className="text-[11px] text-lp-subtle">{fmt.month(today)}</span>}>
                <div className="hidden sm:block">
                  <MiniCalendar month={today} marks={monthMarks} today={today.getDate()} compact />
                </div>
                <p className="mb-2 text-[11px] font-semibold uppercase tracking-wider text-lp-subtle sm:mt-4">{d.agendaTitle}</p>
                <ul className="space-y-2">
                  {agenda.map((ev) => (
                    <li key={`${ev.key}-${ev.from.toISOString()}`} className="flex items-center gap-2.5">
                      <span className="flex w-9 shrink-0 flex-col items-center rounded-md border border-lp-line py-0.5">
                        <span className="text-[9px] uppercase text-lp-subtle">{fmt.date(ev.from, { month: 'short' })}</span>
                        <span className="lp-tabular text-[13px] font-bold leading-none text-lp-fg">{ev.from.getDate()}</span>
                      </span>
                      <span className="truncate text-[12px] text-lp-muted">{agendaLabel[ev.key]}</span>
                    </li>
                  ))}
                </ul>
              </MockPanel>
            </div>

            <MockPanel className="mt-3 hidden md:block" title={d.notificationsTitle}>
              <ul className="grid gap-2 lg:grid-cols-3">
                {[
                  { icon: ClipboardCheck, text: d.notifications.krs, time: fill(d.timeAgo.minutes, { n: 5 }), tone: 'text-lp-brand-text bg-lp-brand/10' },
                  { icon: FileCheck2, text: d.notifications.grades, time: fill(d.timeAgo.minutes, { n: 32 }), tone: 'text-lp-ok bg-lp-ok/10' },
                  { icon: Megaphone, text: d.notifications.announcement, time: fill(d.timeAgo.hours, { n: 2 }), tone: 'text-lp-gold-text bg-lp-gold/15' },
                ].map(({ icon: Icon, text, time, tone }) => (
                  <li key={text} className="flex items-start gap-2.5 rounded-lg bg-lp-sunken/70 p-2.5">
                    <span className={cn('flex h-7 w-7 shrink-0 items-center justify-center rounded-md', tone)}>
                      <Icon size={14} />
                    </span>
                    <div className="min-w-0">
                      <p className="line-clamp-2 text-[11.5px] leading-snug text-lp-fg">{text}</p>
                      <p className="mt-0.5 text-[10.5px] text-lp-subtle">{time}</p>
                    </div>
                  </li>
                ))}
              </ul>
            </MockPanel>
          </div>
        </div>
      </MockWindow>
    </div>
  )
}
