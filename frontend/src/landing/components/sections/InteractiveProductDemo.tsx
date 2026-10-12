import { useState } from 'react'
import {
  Award,
  BarChart3,
  BookOpenCheck,
  CalendarCheck2,
  CalendarClock,
  ClipboardCheck,
  Download,
  FileSpreadsheet,
  FileText,
  GraduationCap,
  Mail,
  Megaphone,
  Timer,
  TrendingUp,
  UserPlus,
  Users,
  UserX,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import {
  ANNOUNCEMENTS,
  APPROVALS,
  CALENDAR_EVENTS,
  FACULTY_STUDENTS,
  GPA_TREND,
  KPI,
  REGISTRATION,
  REPORTS,
  TODAY_SCHEDULE,
  type ApprovalType,
  type LectureStatus,
} from '../../data/demo'
import { daysFromToday, recentTermLabels, termLabel } from '../../lib/terms'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader } from '../ui/Primitives'
import { TabList, TabPanel, type TabItem } from '../ui/Tabs'
import { Avatar, KpiTile, MockChip, MockPanel, MockWindow } from '../mock/Frame'
import { BarList, Donut, Legend, LineChart } from '../mock/Charts'
import { MiniCalendar } from '../mock/MiniCalendar'

type DemoTab = 'academic' | 'students' | 'admin' | 'analytics'
const TAB_PREFIX = 'lp-demo'

export function InteractiveProductDemo() {
  const { m } = useLandingI18n()
  const [tab, setTab] = useState<DemoTab>('academic')
  const tabs: TabItem<DemoTab>[] = [
    { key: 'academic', label: m.demo.tabs.academic, icon: GraduationCap },
    { key: 'students', label: m.demo.tabs.students, icon: Users },
    { key: 'admin', label: m.demo.tabs.admin, icon: ClipboardCheck },
    { key: 'analytics', label: m.demo.tabs.analytics, icon: BarChart3 },
  ]

  return (
    <section id={SECTION_IDS.demo} aria-labelledby="demo-title" className="lp-section relative overflow-hidden">
      <div aria-hidden className="lp-dots pointer-events-none absolute inset-x-0 top-0 h-80 opacity-40 [mask-image:linear-gradient(to_bottom,black,transparent)]" />
      <div className="lp-container relative">
        <SectionHeader id="demo-title" eyebrow={m.demo.eyebrow} title={m.demo.title} description={m.demo.description} align="center" />

        <Reveal className="mt-10 flex justify-center">
          <TabList items={tabs} value={tab} onChange={setTab} label={m.demo.tabsLabel} idPrefix={TAB_PREFIX} />
        </Reveal>

        <Reveal delay={100} className="mt-8">
          <MockWindow title={`Civitas One Atma · ${m.demo.tabs[tab]}`} bodyClassName="p-3 sm:p-5 lg:min-h-[29rem]">
            <TabPanel idPrefix={TAB_PREFIX} tabKey={tab}>
              <div key={tab} className="lp-tab-enter">
                {tab === 'academic' && <AcademicView />}
                {tab === 'students' && <StudentsView />}
                {tab === 'admin' && <AdminView />}
                {tab === 'analytics' && <AnalyticsView />}
              </div>
            </TabPanel>
          </MockWindow>
        </Reveal>
      </div>
    </section>
  )
}

const statusTone: Record<LectureStatus, 'ok' | 'brand' | 'muted'> = { done: 'muted', live: 'ok', upcoming: 'brand' }

function AcademicView() {
  const { m, fmt } = useLandingI18n()
  const a = m.demo.academic
  const today = new Date()
  const todayDay = today.getDate()
  const marks = CALENDAR_EVENTS.map((e) => ({ ...e, label: a.events[e.key] }))
  const firstUpcoming = CALENDAR_EVENTS.find((e) => e.to >= todayDay)
  const [selected, setSelected] = useState<number>(firstUpcoming ? Math.max(firstUpcoming.from, todayDay) : todayDay)
  const dayEvents = CALENDAR_EVENTS.filter((e) => selected >= e.from && selected <= e.to)
  const statusLabel: Record<LectureStatus, string> = { done: a.statusDone, live: a.statusLive, upcoming: a.statusUpcoming }

  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
      <MockPanel
        title={a.scheduleTitle}
        action={<span className="truncate text-[11.5px] text-lp-subtle">{fmt.date(today, { weekday: 'long', day: 'numeric', month: 'long' })}</span>}
      >
        {/* Table (sm and up) */}
        <div className="-mx-4 hidden overflow-x-auto sm:block">
          <table className="w-full min-w-[620px] text-left">
            <thead>
              <tr className="border-y border-lp-line bg-lp-sunken/60 text-[11px] uppercase tracking-wider text-lp-subtle">
                <th scope="col" className="px-4 py-2 font-semibold">{a.columns.time}</th>
                <th scope="col" className="px-2 py-2 font-semibold">{a.columns.course}</th>
                <th scope="col" className="px-2 py-2 font-semibold">{a.columns.room}</th>
                <th scope="col" className="px-2 py-2 font-semibold">{a.columns.lecturer}</th>
                <th scope="col" className="px-4 py-2 text-right font-semibold">{a.columns.status}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-lp-line">
              {TODAY_SCHEDULE.map((row) => (
                <tr key={row.start + row.course} className={cn('text-[12.5px] transition-colors hover:bg-lp-sunken/50', row.status === 'done' && 'text-lp-subtle')}>
                  <td className="lp-tabular whitespace-nowrap px-4 py-3 text-lp-muted">
                    {row.start}–{row.end}
                  </td>
                  <td className="px-2 py-3">
                    <p className="font-medium text-lp-fg">{m.demo.courses[row.course]}</p>
                    <p className="text-[11px] text-lp-subtle">
                      {a.columns.class} {row.section}
                    </p>
                  </td>
                  <td className="lp-tabular whitespace-nowrap px-2 py-3 text-lp-muted">{row.room}</td>
                  <td className="px-2 py-3">
                    <span className="flex items-center gap-2 text-lp-muted">
                      <Avatar name={row.lecturer} size={22} />
                      <span className="truncate">{row.lecturer}</span>
                    </span>
                  </td>
                  <td className="px-4 py-3 text-right">
                    <MockChip tone={statusTone[row.status]}>{statusLabel[row.status]}</MockChip>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        {/* Cards (phones) */}
        <ul className="space-y-2 sm:hidden">
          {TODAY_SCHEDULE.map((row) => (
            <li key={row.start + row.course} className="rounded-lg border border-lp-line p-3">
              <div className="flex items-center justify-between gap-2">
                <span className="lp-tabular text-[12px] text-lp-subtle">
                  {row.start}–{row.end} · {row.room}
                </span>
                <MockChip tone={statusTone[row.status]}>{statusLabel[row.status]}</MockChip>
              </div>
              <p className="mt-1.5 text-[14px] font-medium text-lp-fg">{m.demo.courses[row.course]}</p>
              <p className="mt-0.5 text-[12px] text-lp-muted">
                {row.section} · {row.lecturer}
              </p>
            </li>
          ))}
        </ul>
      </MockPanel>

      <MockPanel title={a.calendarTitle} action={<span className="text-[11.5px] text-lp-subtle">{fmt.month(today)}</span>}>
        <p className="-mt-1.5 mb-3 text-[11.5px] text-lp-subtle">{a.calendarHint}</p>
        <MiniCalendar month={today} marks={marks} today={todayDay} selected={selected} onSelect={setSelected} />
        <div className="mt-4 border-t border-lp-line pt-3" aria-live="polite">
          <p className="text-[12px] font-semibold text-lp-fg">{fmt.date(new Date(today.getFullYear(), today.getMonth(), selected), { weekday: 'long', day: 'numeric', month: 'long' })}</p>
          {dayEvents.length ? (
            <ul className="mt-2 space-y-1.5">
              {dayEvents.map((e) => (
                <li key={e.key} className="flex items-center gap-2 text-[12.5px] text-lp-muted">
                  <span className={cn('h-2 w-2 rounded-full', e.tone === 'brand' && 'bg-lp-brand', e.tone === 'gold' && 'bg-lp-gold', e.tone === 'info' && 'bg-lp-info')} />
                  {a.events[e.key]}
                </li>
              ))}
            </ul>
          ) : (
            <p className="mt-2 text-[12.5px] text-lp-subtle">{a.noEvents}</p>
          )}
        </div>
      </MockPanel>
    </div>
  )
}

function StudentsView() {
  const { m, fmt } = useLandingI18n()
  const s = m.demo.students
  return (
    <div className="space-y-4">
      <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <KpiTile label={s.kpi.active} value={fmt.number(KPI.activeStudents)} trend={{ up: true, text: fmt.percent(KPI.activeStudentsDelta) }} trendLabel={m.dashboard.vsLastTerm} icon={Users} />
        <KpiTile label={s.kpi.newcomers} value={fmt.number(KPI.newStudents)} icon={UserPlus} />
        <KpiTile label={s.kpi.leave} value={fmt.number(KPI.onLeave)} icon={UserX} />
        <KpiTile label={s.kpi.gpa} value={fmt.number(KPI.averageGpa, 2)} icon={TrendingUp} />
      </div>
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
        <MockPanel title={s.byFacultyTitle} action={<span className="text-[11.5px] text-lp-subtle">{termLabel(m)}</span>}>
          <BarList
            items={FACULTY_STUDENTS.map((f) => ({ key: f.key, label: m.demo.faculties[f.key], value: f.value }))}
            format={(v) => fmt.number(v)}
          />
        </MockPanel>
        <MockPanel title={s.registrationTitle}>
          <div className="pt-1">
            <Donut
              items={[
                { key: 'done', label: s.registration.done, value: REGISTRATION.done, slot: 1 },
                { key: 'progress', label: s.registration.progress, value: REGISTRATION.progress, slot: 2 },
                { key: 'pending', label: s.registration.pending, value: REGISTRATION.pending, slot: 3 },
              ]}
              format={(v) => fmt.percent(v, 0)}
            />
          </div>
          <p className="mt-4 text-[11.5px] text-lp-subtle">{fill(s.studentsUnit, { n: fmt.number(KPI.activeStudents) })}</p>
        </MockPanel>
      </div>
    </div>
  )
}

const approvalIcon: Record<ApprovalType, typeof Award> = {
  leave: CalendarClock,
  krsChange: BookOpenCheck,
  recommendation: FileText,
  scholarship: Award,
}

function AdminView() {
  const { m, fmt } = useLandingI18n()
  const a = m.demo.admin
  const age = (days: number) => (days === 0 ? a.today : fill(a.daysAgo, { n: days }))
  return (
    <div className="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)]">
      <MockPanel title={a.approvalsTitle} action={<MockChip tone="brand">{fill(a.approvalsCount, { n: APPROVALS.length })}</MockChip>}>
        <ul className="divide-y divide-lp-line">
          {APPROVALS.map((item) => {
            const Icon = approvalIcon[item.type]
            return (
              <li key={item.nim} className="flex items-center gap-3 py-3 first:pt-1 last:pb-1">
                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-lp-sunken text-lp-brand-text">
                  <Icon size={16} aria-hidden />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="truncate text-[13px] font-medium text-lp-fg">{a.types[item.type]}</p>
                  <p className="lp-tabular truncate text-[11.5px] text-lp-subtle">
                    {item.name} · {item.nim}
                  </p>
                </div>
                <div className="hidden shrink-0 flex-col items-end gap-1 sm:flex">
                  <span className="text-[11px] text-lp-muted">{fill(a.step, { step: a.steps[item.step] })}</span>
                  <span className="flex gap-1" aria-hidden>
                    {Array.from({ length: item.total }, (_, i) => (
                      <span key={i} className={cn('h-1.5 w-5 rounded-full', i < item.position ? 'bg-lp-viz1' : 'bg-lp-line')} />
                    ))}
                  </span>
                </div>
                <span className="w-14 shrink-0 text-right text-[11px] text-lp-subtle">{age(item.days)}</span>
              </li>
            )
          })}
        </ul>
      </MockPanel>

      <div className="space-y-4">
        <MockPanel title={a.announcementsTitle}>
          <ul className="space-y-3">
            {ANNOUNCEMENTS.map((item) => (
              <li key={item.key} className="flex items-start gap-3">
                <span className="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-lp-gold/15 text-lp-gold-text">
                  <Megaphone size={15} aria-hidden />
                </span>
                <div className="min-w-0">
                  <p className="text-[12.5px] font-medium leading-snug text-lp-fg">{a.announcements[item.key]}</p>
                  <p className="mt-1 flex items-center gap-2 text-[11px] text-lp-subtle">
                    <MockChip tone={item.audience === 'students' ? 'info' : item.audience === 'lecturers' ? 'gold' : 'muted'}>{a.audience[item.audience]}</MockChip>
                    {fmt.date(daysFromToday(-item.daysAgo), { day: 'numeric', month: 'short' })}
                  </p>
                </div>
              </li>
            ))}
          </ul>
        </MockPanel>
        <div className="grid grid-cols-2 gap-3">
          <KpiTile label={a.statsLetters} value={fmt.number(KPI.lettersThisMonth)} icon={Mail} size="sm" />
          <KpiTile label={a.statsDuration} value={fill(a.durationValue, { n: fmt.number(KPI.processingDays, 1) })} icon={Timer} size="sm" />
        </div>
      </div>
    </div>
  )
}

function AnalyticsView() {
  const { m, fmt } = useLandingI18n()
  const a = m.demo.analytics
  const [view, setView] = useState<'chart' | 'table'>('chart')
  const labels = recentTermLabels(GPA_TREND.ips.length)
  const series = [
    { key: 'ips', label: a.series.ips, values: GPA_TREND.ips, slot: 1 as const },
    { key: 'ipk', label: a.series.ipk, values: GPA_TREND.ipk, slot: 3 as const },
  ]

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <KpiTile label={a.kpi.attendance} value={fmt.percent(KPI.attendance)} icon={CalendarCheck2} />
        <KpiTile label={a.kpi.gradeTimeliness} value={fmt.percent(KPI.gradeTimeliness)} icon={ClipboardCheck} />
        <KpiTile label={a.kpi.goodStanding} value={fmt.percent(KPI.goodStanding)} icon={GraduationCap} />
      </div>
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <MockPanel
          title={a.chartTitle}
          action={
            <div role="group" aria-label={m.common.viewToggle} className="inline-flex shrink-0 rounded-full border border-lp-line bg-lp-sunken p-0.5">
              {(['chart', 'table'] as const).map((v) => (
                <button
                  key={v}
                  type="button"
                  aria-pressed={view === v}
                  onClick={() => setView(v)}
                  className={cn(
                    'rounded-full px-3 py-1 text-[11.5px] font-semibold transition-colors',
                    view === v ? 'bg-lp-surface text-lp-fg shadow-lp-sm' : 'text-lp-muted hover:text-lp-fg',
                  )}
                >
                  {v === 'chart' ? m.common.viewChart : m.common.viewTable}
                </button>
              ))}
            </div>
          }
        >
          {view === 'chart' ? (
            <>
              <Legend className="mb-3" items={series} />
              <LineChart
                series={series}
                labels={labels}
                height={220}
                min={3.1}
                max={3.45}
                ticks={[3.1, 3.2, 3.3, 3.4]}
                area={false}
                formatTick={(v) => fmt.number(v, 1)}
                formatValue={(v) => fmt.number(v, 2)}
                ariaLabel={a.chartTitle}
              />
            </>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-[12.5px]">
                <thead>
                  <tr className="border-b border-lp-line text-[11px] uppercase tracking-wider text-lp-subtle">
                    <th scope="col" className="py-2 pr-3 font-semibold">{a.semesterColumn}</th>
                    <th scope="col" className="py-2 pr-3 text-right font-semibold">{a.series.ips}</th>
                    <th scope="col" className="py-2 text-right font-semibold">{a.series.ipk}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-lp-line">
                  {labels.map((label, i) => (
                    <tr key={label}>
                      <th scope="row" className="lp-tabular py-2.5 pr-3 font-medium text-lp-muted">{label}</th>
                      <td className="lp-tabular py-2.5 pr-3 text-right font-semibold text-lp-fg">{fmt.number(GPA_TREND.ips[i], 2)}</td>
                      <td className="lp-tabular py-2.5 text-right font-semibold text-lp-fg">{fmt.number(GPA_TREND.ipk[i], 2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </MockPanel>

        <MockPanel title={a.reportsTitle}>
          <ul className="space-y-2">
            {REPORTS.map((r) => {
              const Icon = r.format === 'PDF' ? FileText : FileSpreadsheet
              return (
                <li key={r.key} className="flex items-center gap-3 rounded-lg border border-lp-line px-3 py-2.5">
                  <Icon size={17} aria-hidden className={r.format === 'PDF' ? 'shrink-0 text-lp-brand-text' : 'shrink-0 text-lp-ok'} />
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[12.5px] font-medium text-lp-fg">{a.reports[r.key]}</p>
                    <p className="text-[11px] text-lp-subtle">{fill(a.generated, { date: fmt.date(daysFromToday(-r.daysAgo), { day: 'numeric', month: 'short' }) })}</p>
                  </div>
                  <MockChip tone="muted">{r.format}</MockChip>
                  <Download size={15} aria-hidden className="shrink-0 text-lp-subtle" />
                </li>
              )
            })}
          </ul>
        </MockPanel>
      </div>
    </div>
  )
}
