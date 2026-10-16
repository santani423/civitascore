import { useState } from 'react'
import { Briefcase, CalendarCheck2, Check, CheckCircle2, ClipboardList, FileBarChart, GraduationCap, Landmark, Megaphone, Presentation, Users } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { ENROLLMENT_TREND, GRADING_PROGRESS, KPI, STAFF_QUEUE, STUDENT_DAY, TODAY_SCHEDULE } from '../../data/demo'
import { recentTermLabels, termLabel } from '../../lib/terms'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader, IllustrativeChip } from '../ui/Primitives'
import { TabList, TabPanel, type TabItem } from '../ui/Tabs'
import { KpiTile, MockChip, MockPanel, PhoneFrame, Progress } from '../mock/Frame'
import { Columns } from '../mock/Charts'

type Persona = keyof Messages['personas']['items']
const TAB_PREFIX = 'lp-persona'

export function UserExperienceSection() {
  const { m } = useLandingI18n()
  const [persona, setPersona] = useState<Persona>('leaders')
  const p = m.personas
  const tabs: TabItem<Persona>[] = [
    { key: 'leaders', label: p.items.leaders.name, icon: Landmark },
    { key: 'lecturers', label: p.items.lecturers.name, icon: Presentation },
    { key: 'staff', label: p.items.staff.name, icon: Briefcase },
    { key: 'students', label: p.items.students.name, icon: GraduationCap },
  ]
  const item = p.items[persona]

  return (
    <section id={SECTION_IDS.personas} aria-labelledby="personas-title" className="lp-section border-t border-lp-line bg-lp-surface">
      <div className="lp-container">
        <SectionHeader id="personas-title" eyebrow={p.eyebrow} title={p.title} description={p.description} />

        <Reveal className="mt-10">
          <TabList items={tabs} value={persona} onChange={setPersona} label={p.tabsLabel} idPrefix={TAB_PREFIX} variant="underline" />
        </Reveal>

        <TabPanel idPrefix={TAB_PREFIX} tabKey={persona} className="mt-10">
          <div key={persona} className="lp-tab-enter grid items-center gap-10 lg:grid-cols-12 lg:gap-14">
            <div className="lg:col-span-5">
              <h3 className="lp-h2 text-[clamp(1.5rem,1.2rem+1.2vw,2.125rem)] text-lp-fg">{item.title}</h3>
              <p className="lp-lead mt-4 text-lp-muted">{item.body}</p>
              <ul className="mt-7 space-y-3.5">
                {[item.points.a, item.points.b, item.points.c].map((point) => (
                  <li key={point} className="flex items-start gap-3">
                    <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-lp-brand/10 text-lp-brand-text">
                      <Check size={13} strokeWidth={3} aria-hidden />
                    </span>
                    <span className="lp-body text-lp-fg">{point}</span>
                  </li>
                ))}
              </ul>
            </div>
            <div className="lg:col-span-7" aria-hidden>
              <div className="relative rounded-lp-xl border border-lp-line bg-lp-canvas p-4 sm:p-6">
                <IllustrativeChip className="absolute right-4 top-4 bg-lp-canvas" />
                {persona === 'leaders' && <LeadersMock />}
                {persona === 'lecturers' && <LecturersMock />}
                {persona === 'staff' && <StaffMock />}
                {persona === 'students' && <StudentsMock />}
              </div>
            </div>
          </div>
        </TabPanel>
      </div>
    </section>
  )
}

function LeadersMock() {
  const { m, fmt } = useLandingI18n()
  const labels = recentTermLabels(ENROLLMENT_TREND.length)
  return (
    <div className="space-y-3 pt-8 sm:pt-6">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <KpiTile label={m.dashboard.kpi.students} value={fmt.number(KPI.activeStudents)} icon={Users} />
        <KpiTile label={m.dashboard.kpi.attendance} value={fmt.percent(KPI.attendance)} icon={CalendarCheck2} />
        <KpiTile label={m.personas.mock.reportsReady} value={fmt.number(KPI.reportsReady)} icon={FileBarChart} />
      </div>
      <MockPanel title={m.personas.mock.enrollmentTrend}>
        <Columns
          className="pt-5"
          height={110}
          highlightLast
          items={ENROLLMENT_TREND.map((v, i) => ({ key: labels[i], label: labels[i], value: v }))}
          format={(v) => fmt.number(v)}
        />
      </MockPanel>
    </div>
  )
}

function LecturersMock() {
  const { m, fmt } = useLandingI18n()
  const mock = m.personas.mock
  const classes = [TODAY_SCHEDULE[1], TODAY_SCHEDULE[0]]
  const pct = (GRADING_PROGRESS.done / GRADING_PROGRESS.total) * 100
  return (
    <div className="grid gap-3 pt-8 sm:grid-cols-2 sm:pt-6">
      <MockPanel title={mock.classesToday} className="sm:col-span-2">
        <ul className="grid gap-2 sm:grid-cols-2">
          {classes.map((c, i) => (
            <li key={c.course} className="rounded-lg border border-lp-line p-3">
              <div className="flex items-center justify-between gap-2">
                <span className="lp-tabular text-[11.5px] text-lp-subtle">
                  {c.start}–{c.end}
                </span>
                <MockChip tone={i === 0 ? 'ok' : 'muted'}>{i === 0 ? m.demo.academic.statusLive : m.demo.academic.statusDone}</MockChip>
              </div>
              <p className="mt-1.5 text-[13.5px] font-semibold text-lp-fg">{m.demo.courses[c.course]}</p>
              <p className="mt-0.5 text-[11.5px] text-lp-muted">
                {c.section} · {c.room} · {fill(mock.meeting, { n: GRADING_PROGRESS.meeting })}
              </p>
            </li>
          ))}
        </ul>
      </MockPanel>
      <MockPanel title={mock.gradingProgress}>
        <p className="lp-tabular font-display text-[24px] font-bold text-lp-fg">{fmt.percent(pct, 0)}</p>
        <Progress value={pct} className="mt-2" />
        <p className="mt-2 text-[11.5px] text-lp-subtle">{fill(mock.gradingCount, { done: GRADING_PROGRESS.done, total: GRADING_PROGRESS.total })}</p>
      </MockPanel>
      <MockPanel title={m.dashboard.notificationsTitle}>
        <p className="flex items-start gap-2 text-[12px] leading-snug text-lp-muted">
          <ClipboardList size={15} className="mt-0.5 shrink-0 text-lp-brand-text" />
          {m.dashboard.notifications.krs}
        </p>
      </MockPanel>
    </div>
  )
}

function StaffMock() {
  const { m, fmt } = useLandingI18n()
  const mock = m.personas.mock
  const max = Math.max(...STAFF_QUEUE.map((q) => q.count))
  return (
    <div className="space-y-3 pt-8 sm:pt-6">
      <MockPanel title={mock.queueTitle} action={<span className="text-[11.5px] text-lp-subtle">{termLabel(m)}</span>}>
        <ul className="space-y-4">
          {STAFF_QUEUE.map((q, i) => (
            <li key={q.key}>
              <div className="flex items-center justify-between gap-3">
                <span className="text-[13px] font-medium text-lp-fg">{mock.queue[q.key]}</span>
                <span className="lp-tabular text-[13px] font-semibold text-lp-fg">{fmt.number(q.count)}</span>
              </div>
              <Progress value={(q.count / max) * 100} className="mt-2" tone={i === 0 ? 'brand' : 'gold'} />
            </li>
          ))}
        </ul>
      </MockPanel>
      <div className="grid grid-cols-2 gap-3">
        <KpiTile label={m.demo.admin.statsLetters} value={fmt.number(KPI.lettersThisMonth)} size="sm" />
        <KpiTile label={m.demo.admin.statsDuration} value={fill(m.demo.admin.durationValue, { n: fmt.number(KPI.processingDays, 1) })} size="sm" />
      </div>
    </div>
  )
}

function StudentsMock() {
  const { m } = useLandingI18n()
  const d = m.dashboard
  const mock = m.personas.mock
  return (
    <div className="flex justify-center py-4">
      <PhoneFrame className="w-[248px] shadow-lp-lg">
        <div className="px-3.5 pb-5">
          <p className="text-[11px] text-lp-subtle">{termLabel(m)}</p>
          <p className="mt-0.5 font-display text-[16px] font-bold text-lp-fg">{d.phoneGreeting}</p>
          <div className="mt-3 flex items-center justify-between rounded-xl bg-lp-ok/10 px-3 py-2">
            <span className="text-[11.5px] font-medium text-lp-fg">{mock.krsStatus}</span>
            <span className="flex items-center gap-1 text-[11px] font-semibold text-lp-ok">
              <CheckCircle2 size={13} />
              {mock.approved}
            </span>
          </div>
          <p className="mt-4 text-[11px] font-semibold uppercase tracking-wider text-lp-subtle">{d.phoneToday}</p>
          <div className="mt-2 space-y-2">
            {STUDENT_DAY.map((s, i) => (
              <div key={s.course} className="rounded-xl border border-lp-line bg-lp-surface p-2.5">
                <div className="flex items-center justify-between gap-2">
                  <span className="lp-tabular text-[10.5px] text-lp-subtle">
                    {s.start}–{s.end}
                  </span>
                  <MockChip tone={i === 0 ? 'brand' : 'muted'}>{i === 0 ? d.phoneNow : d.phoneNext}</MockChip>
                </div>
                <p className="mt-1 text-[12.5px] font-semibold leading-tight text-lp-fg">{m.demo.courses[s.course]}</p>
                <p className="mt-0.5 text-[10.5px] text-lp-subtle">{s.room}</p>
              </div>
            ))}
          </div>
          <p className="mt-4 text-[11px] font-semibold uppercase tracking-wider text-lp-subtle">{mock.latestAnnouncement}</p>
          <div className="mt-2 flex items-start gap-2 rounded-xl bg-lp-sunken p-2.5">
            <Megaphone size={14} className="mt-0.5 shrink-0 text-lp-gold-text" />
            <p className="text-[11px] leading-snug text-lp-muted">{m.demo.admin.announcements.midterm}</p>
          </div>
        </div>
      </PhoneFrame>
    </div>
  )
}
