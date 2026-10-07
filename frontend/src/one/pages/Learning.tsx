import { useMemo, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  CalendarDays,
  ChartLine,
  CircleCheck,
  ClipboardList,
  Clock,
  Download,
  FileCheck,
  FileUp,
  MapPin,
  MessageSquareText,
  Table2,
  TrendingUp,
  Layers,
  Award,
  Upload,
  User,
  ChevronLeft,
  ChevronRight,
  Armchair,
  UserCheck,
  TriangleAlert,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { student, lecturerById } from '../data/people'
import {
  assignments,
  attendanceRate,
  ATTENDANCE_MIN,
  allCourses,
  courseById,
  courses,
  exams,
  gpaHistory,
  recentSessions,
  type Assignment,
  type AssignmentStatus,
  type Course,
  type Exam,
} from '../data/academic'
import { buildAgenda } from '../lib/agenda'
import { daysUntil, monthGrid, sameDay, weekDays } from '../lib/dates'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Button, IconButton } from '../components/ui/Button'
import { Async, CardGridSkeleton, EmptyState, ListSkeleton, StatsSkeleton, TableSkeleton } from '../components/ui/Feedback'
import { MetaItem, PageHeader, Progress, ProgressRing, Stat } from '../components/ui/Display'
import { Segmented, TabPanel, Tabs } from '../components/ui/Tabs'
import { Modal } from '../components/ui/Overlay'
import { Field, Select, Textarea } from '../components/ui/Form'
import { DataTable } from '../components/ui/DataTable'
import { DayView, KindLegend, MonthView, WeekView } from '../components/ui/Calendar'
import { Bars, TrendLine } from '../components/charts/Charts'
import { useDue } from '../components/shared/bits'

/* ============================ Schedule ============================ */

type View = 'daily' | 'weekly' | 'monthly'

export function SchedulePage() {
  const { t, fmt } = useI18n()
  const [view, setView] = useState<View>('weekly')
  const [cursor, setCursor] = useState(() => new Date())
  const [selected, setSelected] = useState(() => new Date())
  const q = useMockData(() => true, false, 350)

  const range = useMemo(() => {
    if (view === 'daily') return { days: [cursor], from: cursor, to: cursor }
    if (view === 'weekly') {
      const days = weekDays(cursor)
      return { days, from: days[0], to: days[6] }
    }
    const days = monthGrid(cursor)
    return { days, from: days[0], to: days[41] }
  }, [view, cursor])

  const items = useMemo(() => (q.data ? buildAgenda(range.from, range.to) : []), [range, q.data])

  const shift = (dir: 1 | -1) => {
    const d = new Date(cursor)
    if (view === 'daily') d.setDate(d.getDate() + dir)
    if (view === 'weekly') d.setDate(d.getDate() + dir * 7)
    if (view === 'monthly') d.setMonth(d.getMonth() + dir, 1)
    setCursor(d)
    setSelected(d)
  }

  const title =
    view === 'daily'
      ? fmt.date(cursor, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
      : view === 'weekly'
        ? `${fmt.date(range.from, { day: 'numeric', month: 'short' })} – ${fmt.date(range.to, { day: 'numeric', month: 'short', year: 'numeric' })}`
        : fmt.month(cursor)

  const selectedItems = items.filter((i) => sameDay(i.start, selected))

  return (
    <>
      <PageHeader
        title={t('schedule.title')}
        description={t('schedule.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('schedule.title') }]}
        actions={
          <Segmented
            label={t('schedule.viewMode')}
            value={view}
            onChange={setView}
            items={[
              { value: 'daily', label: t('schedule.daily') },
              { value: 'weekly', label: t('schedule.weekly') },
              { value: 'monthly', label: t('schedule.monthly') },
            ]}
          />
        }
      />
      <Card>
        <div className="flex flex-col gap-4 border-b border-one-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <div className="flex items-center gap-2">
            <IconButton icon={ChevronLeft} label={t('common.previous')} variant="secondary" size="sm" onClick={() => shift(-1)} />
            <IconButton icon={ChevronRight} label={t('common.next')} variant="secondary" size="sm" onClick={() => shift(1)} />
            <Button
              variant="secondary"
              size="sm"
              onClick={() => {
                setCursor(new Date())
                setSelected(new Date())
              }}
            >
              {t('schedule.today')}
            </Button>
            <h2 className="t-h3 ml-2 text-one-fg" aria-live="polite">
              {title}
            </h2>
          </div>
          <KindLegend />
        </div>
        <CardBody className="pt-5">
          <Async query={q} skeleton={<ListSkeleton rows={6} />}>
            {() => (
              <>
                {view === 'monthly' && (
                  <div className="grid gap-6 xl:grid-cols-[1fr_320px]">
                    <MonthView month={cursor} days={range.days} items={items} selected={selected} onSelect={setSelected} />
                    <div>
                      <h3 className="t-h3 mb-3 text-one-fg">{fmt.date(selected, { weekday: 'long', day: 'numeric', month: 'long' })}</h3>
                      {selectedItems.length ? (
                        <DayView items={selectedItems} />
                      ) : (
                        <EmptyState compact icon={CalendarDays} title={t('schedule.noEvents')} />
                      )}
                    </div>
                  </div>
                )}
                {view === 'weekly' && <WeekView days={range.days} items={items} />}
                {view === 'daily' &&
                  (items.length ? (
                    <DayView items={items} />
                  ) : (
                    <EmptyState
                      icon={CalendarDays}
                      title={t('states.emptySchedule')}
                      body={t('states.emptyScheduleBody')}
                      action={<Button variant="secondary" to="/one/campus/events">{t('states.browseEvents')}</Button>}
                    />
                  ))}
              </>
            )}
          </Async>
        </CardBody>
      </Card>
    </>
  )
}

/* ============================ Assignments ============================ */

type ATab = 'all' | AssignmentStatus

export function AssignmentsPage() {
  const { t } = useI18n()
  const submitted = useApp((s) => s.submitted)
  const [tab, setTab] = useState<ATab>('all')
  const [submitting, setSubmitting] = useState<Assignment | null>(null)
  const [feedback, setFeedback] = useState<Assignment | null>(null)
  const q = useMockData(() => assignments, [] as Assignment[])

  const withStatus = q.data.map((a) => ({ ...a, status: submitted[a.id] && a.status !== 'graded' ? ('submitted' as const) : a.status, progress: submitted[a.id] ? 100 : a.progress }))
  const counts = (s: ATab) => (s === 'all' ? withStatus.length : withStatus.filter((a) => a.status === s).length)
  const list = withStatus.filter((a) => tab === 'all' || a.status === tab)
  const tabs: ATab[] = ['all', 'upcoming', 'inProgress', 'submitted', 'graded', 'overdue']

  return (
    <>
      <PageHeader
        title={t('assignments.title')}
        description={t('assignments.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('assignments.title') }]}
      />
      <Tabs
        id="asg"
        label={t('assignments.title')}
        value={tab}
        onChange={setTab}
        className="mb-6"
        items={tabs.map((s) => ({ value: s, label: s === 'all' ? t('common.all') : t(`status.${s}`), count: q.status === 'ready' ? counts(s) : undefined }))}
      />
      <TabPanel id="asg" value={tab}>
        <Async
          query={q}
          skeleton={<CardGridSkeleton media={false} />}
          isEmpty={() => list.length === 0}
          empty={
            <Card>
              <EmptyState icon={ClipboardList} title={t('states.emptyAssignments')} body={t('states.emptyAssignmentsBody')} action={<Button variant="secondary" to="/one/schedule">{t('dashboard.fullSchedule')}</Button>} />
            </Card>
          }
        >
          {() => (
            <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
              {list.map((a) => (
                <li key={a.id}>
                  <AssignmentCard a={a} onSubmit={() => setSubmitting(a)} onFeedback={() => setFeedback(a)} />
                </li>
              ))}
            </ul>
          )}
        </Async>
      </TabPanel>
      <SubmitModal assignment={submitting} onClose={() => setSubmitting(null)} />
      <Modal open={!!feedback} onClose={() => setFeedback(null)} title={t('assignments.feedback')} description={feedback?.title} size="sm" footer={<Button onClick={() => setFeedback(null)}>{t('common.close')}</Button>}>
        {feedback && (
          <div className="flex items-start gap-4">
            <ProgressRing value={feedback.score ?? 0} size={72} stroke={7} tone="ok" label={`${t('assignments.score')}: ${feedback.score}`}>
              <span className="font-display text-[18px] font-bold text-one-fg">{feedback.score}</span>
            </ProgressRing>
            <p className="t-body text-one-muted">{feedback.feedback}</p>
          </div>
        )}
      </Modal>
    </>
  )
}

function AssignmentCard({ a, onSubmit, onFeedback }: { a: Assignment; onSubmit: () => void; onFeedback: () => void }) {
  const { t, fmt } = useI18n()
  const due = useDue()
  const course = courseById(a.courseId)
  const d = due(a.due)
  const done = a.status === 'submitted' || a.status === 'graded'
  return (
    <Card as="article" className={cn('flex h-full flex-col p-5', a.status === 'overdue' && 'border-one-bad/30')}>
      <div className="flex items-start justify-between gap-3">
        <Link to={`/one/courses/${a.courseId}`} className="min-w-0 text-[12.5px] font-medium text-one-royal hover:underline">
          <span className="font-mono">{course?.code}</span> · {course?.name}
        </Link>
        <StatusBadge status={a.status} />
      </div>
      <h3 className="mt-2 text-[16px] font-semibold leading-snug text-one-fg">{a.title}</h3>
      <div className="mt-3 flex flex-wrap items-center gap-2">
        <MetaItem icon={Clock}>
          {fmt.date(a.due, { weekday: 'short', day: 'numeric', month: 'short' })} · {fmt.time(a.due)}
        </MetaItem>
        {!done && <Badge tone={d.tone}>{d.label}</Badge>}
      </div>
      <div className="mt-4">
        <div className="mb-1.5 flex justify-between text-[12.5px]">
          <span className="text-one-subtle">{t('assignments.progress')}</span>
          <span className="font-medium text-one-fg tabular">{a.progress}%</span>
        </div>
        <Progress value={a.progress} tone={a.status === 'overdue' ? 'bad' : done ? 'ok' : 'royal'} label={t('assignments.progress')} />
      </div>
      <div className="mt-auto pt-5">
        {a.status === 'graded' ? (
          <Button variant="secondary" block icon={MessageSquareText} onClick={onFeedback}>
            {t('assignments.viewFeedback')} · {a.score}/100
          </Button>
        ) : a.status === 'submitted' ? (
          <Button variant="secondary" block icon={CircleCheck} disabled>
            {t('status.submitted')}
          </Button>
        ) : (
          <Button block icon={Upload} onClick={onSubmit}>
            {t('assignments.submit')}
          </Button>
        )}
      </div>
    </Card>
  )
}

function SubmitModal({ assignment, onClose }: { assignment: Assignment | null; onClose: () => void }) {
  const { t } = useI18n()
  const toast = useToast()
  const submit = useApp((s) => s.submit)
  const [file, setFile] = useState<File | null>(null)
  const [notes, setNotes] = useState('')
  const [error, setError] = useState<string>()
  const [loading, setLoading] = useState(false)
  const inputRef = useRef<HTMLInputElement>(null)
  const [drag, setDrag] = useState(false)

  const reset = () => {
    setFile(null)
    setNotes('')
    setError(undefined)
    setLoading(false)
    onClose()
  }

  return (
    <Modal
      open={!!assignment}
      onClose={reset}
      title={t('assignments.submitTitle')}
      description={assignment?.title}
      footer={
        <>
          <Button variant="secondary" onClick={reset}>{t('common.cancel')}</Button>
          <Button
            icon={FileUp}
            loading={loading}
            onClick={() => {
              if (!file) {
                setError(t('assignments.fileRequired'))
                return
              }
              setLoading(true)
              window.setTimeout(() => {
                if (assignment) submit(assignment.id)
                toast(t('assignments.submittedToast'))
                reset()
              }, 700)
            }}
          >
            {t('common.submit')}
          </Button>
        </>
      }
    >
      <div className="space-y-5">
        <div>
          <p className="mb-1.5 text-[13px] font-medium text-one-fg">
            {t('assignments.uploadFile')} <span className="text-one-bad" aria-hidden>*</span>
          </p>
          <button
            type="button"
            onClick={() => inputRef.current?.click()}
            onDragOver={(e) => {
              e.preventDefault()
              setDrag(true)
            }}
            onDragLeave={() => setDrag(false)}
            onDrop={(e) => {
              e.preventDefault()
              setDrag(false)
              const f = e.dataTransfer.files[0]
              if (f) {
                setFile(f)
                setError(undefined)
              }
            }}
            aria-describedby="upload-hint"
            className={cn(
              'flex w-full flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed px-6 py-8 text-center transition-colors',
              drag ? 'border-one-royal bg-one-royal/5' : error ? 'border-one-bad/50 bg-one-bad/5' : 'border-one-line hover:border-one-line2 hover:bg-one-sunken/50',
            )}
          >
            <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-one-royal/10 text-one-royal">
              <Upload size={20} aria-hidden />
            </span>
            <span className="text-[14px] font-medium text-one-fg">{file ? t('assignments.chosenFile', { name: file.name }) : t('assignments.dropHere')}</span>
            <span id="upload-hint" className="text-[12.5px] text-one-subtle">{t('assignments.fileHint')}</span>
          </button>
          <input
            ref={inputRef}
            type="file"
            className="sr-only"
            tabIndex={-1}
            accept=".pdf,.docx,.zip"
            onChange={(e) => {
              setFile(e.target.files?.[0] ?? null)
              setError(undefined)
            }}
          />
          {error && <p role="alert" className="mt-1.5 text-[12.5px] font-medium text-one-bad">{error}</p>}
        </div>
        <Field label={t('assignments.notes')}>
          <Textarea placeholder={t('assignments.notesPlaceholder')} value={notes} onChange={(e) => setNotes(e.target.value)} />
        </Field>
      </div>
    </Modal>
  )
}

/* ============================ Exams ============================ */

export function ExamsPage() {
  const { t, fmt } = useI18n()
  const q = useMockData(() => exams, [] as Exam[])
  const [checked, setChecked] = useState<string[]>([])

  return (
    <>
      <PageHeader title={t('exams.title')} description={t('exams.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('exams.title') }]} />
      <Async
        query={q}
        skeleton={<CardGridSkeleton media={false} />}
        isEmpty={(d) => d.filter((e) => daysUntil(e.start) >= 0).length === 0}
        empty={<Card><EmptyState icon={FileCheck} title={t('states.emptyExams')} body={t('states.emptyExamsBody')} /></Card>}
      >
        {(all) => {
          const upcoming = all.filter((e) => daysUntil(e.start) >= 0).sort((a, b) => a.start.localeCompare(b.start))
          const past = all.filter((e) => daysUntil(e.start) < 0)
          const next = upcoming[0]
          const nextCourse = courseById(next.courseId)
          return (
            <div className="space-y-8">
              <section aria-labelledby="next-exam" className="grid gap-4 lg:grid-cols-3">
                <div className="relative overflow-hidden rounded-3xl bg-one-hero p-6 text-white shadow-lift sm:p-8 lg:col-span-2">
                  <div aria-hidden className="one-cover-pattern absolute inset-0 opacity-30" />
                  <div className="relative">
                    <p className="t-caption text-one-spark">{t('exams.nextExam')}</p>
                    <h2 id="next-exam" className="t-h1 mt-2 text-white">{nextCourse?.name}</h2>
                    <p className="mt-1 text-white/75">{t(`exams.types.${next.type}`)} · {nextCourse?.code}</p>
                    <p className="mt-6 font-display text-[22px] font-bold sm:text-[26px]">
                      <Countdown iso={next.start} />
                    </p>
                    <div className="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-[14px] text-white/85">
                      <span className="inline-flex items-center gap-1.5"><CalendarDays size={15} aria-hidden /> {fmt.date(next.start, { weekday: 'long', day: 'numeric', month: 'long' })}</span>
                      <span className="inline-flex items-center gap-1.5"><Clock size={15} aria-hidden /> {fmt.time(next.start)} · {t('common.minutes', { count: next.duration })}</span>
                      <span className="inline-flex items-center gap-1.5"><MapPin size={15} aria-hidden /> {next.room}</span>
                      <span className="inline-flex items-center gap-1.5"><Armchair size={15} aria-hidden /> {t('exams.seat')} {next.seat}</span>
                    </div>
                  </div>
                </div>
                <Card padded>
                  <h3 className="t-h3 text-one-fg">{t('exams.checklist')}</h3>
                  <ul className="mt-4 space-y-2">
                    {(['card', 'early', 'device'] as const).map((k) => {
                      const on = checked.includes(k)
                      return (
                        <li key={k}>
                          <label className="flex cursor-pointer items-center gap-3 rounded-xl border border-one-line px-3.5 py-3 transition-colors hover:bg-one-sunken/50">
                            <input
                              type="checkbox"
                              checked={on}
                              onChange={() => setChecked((c) => (on ? c.filter((x) => x !== k) : [...c, k]))}
                              className="h-4 w-4 rounded accent-[rgb(var(--one-royal))]"
                            />
                            <span className={cn('text-[14px]', on ? 'text-one-subtle line-through' : 'text-one-fg')}>{t(`exams.checklistItems.${k}`)}</span>
                          </label>
                        </li>
                      )
                    })}
                  </ul>
                  <Progress value={checked.length} max={3} tone="ok" label={t('exams.checklist')} className="mt-4" />
                </Card>
              </section>

              <section aria-labelledby="up-h">
                <h2 id="up-h" className="t-h2 mb-4 text-one-fg">{t('exams.upcoming')}</h2>
                <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                  {upcoming.map((e) => (
                    <li key={e.id}>
                      <ExamCard exam={e} />
                    </li>
                  ))}
                </ul>
              </section>

              {past.length > 0 && (
                <Card as="section" aria-labelledby="past-h">
                  <CardHeader id="past-h" title={t('exams.past')} />
                  <div className="mt-4">
                    <DataTable
                      caption={t('exams.past')}
                      rows={past}
                      rowKey={(e) => e.id}
                      columns={[
                        { key: 'c', header: t('grades.course'), render: (e) => <span className="font-medium">{courseById(e.courseId)?.name}</span> },
                        { key: 'type', header: t('exams.type'), render: (e) => t(`exams.types.${e.type}`) },
                        { key: 'd', header: t('common.date'), render: (e) => fmt.date(e.start) },
                        { key: 's', header: t('grades.score'), render: (e) => e.score ?? '—', align: 'right' },
                      ]}
                    />
                  </div>
                </Card>
              )}
            </div>
          )
        }}
      </Async>
    </>
  )
}

function Countdown({ iso }: { iso: string }) {
  const { t } = useI18n()
  const d = daysUntil(iso)
  if (d === 0) return <>{t('exams.startsToday')}</>
  if (d === 1) return <>{t('exams.startsTomorrow')}</>
  return <>{t('exams.startsIn', { count: d })}</>
}

function ExamCard({ exam }: { exam: Exam }) {
  const { t, fmt } = useI18n()
  const course = courseById(exam.courseId)
  const lecturer = course && lecturerById(course.lecturerId)
  const d = daysUntil(exam.start)
  return (
    <Card as="article" className="flex h-full flex-col p-5">
      <div className="flex items-center justify-between gap-2">
        <Badge tone={exam.type === 'quiz' ? 'sky' : exam.type === 'practical' ? 'teal' : 'bad'}>{t(`exams.types.${exam.type}`)}</Badge>
        <Badge tone={d <= 1 ? 'bad' : d <= 3 ? 'warn' : 'neutral'} icon={Clock}>
          {d === 0 ? t('common.today') : d === 1 ? t('common.tomorrow') : t('common.inDays', { count: d })}
        </Badge>
      </div>
      <h3 className="mt-3 text-[16px] font-semibold text-one-fg">{course?.name}</h3>
      <p className="text-[12.5px] text-one-subtle"><Countdown iso={exam.start} /></p>
      <dl className="mt-4 grid grid-cols-2 gap-3 border-t border-one-line pt-4 text-[13px]">
        <ExamMeta icon={CalendarDays} label={t('common.date')} value={fmt.date(exam.start, { weekday: 'short', day: 'numeric', month: 'short' })} />
        <ExamMeta icon={Clock} label={t('common.time')} value={`${fmt.time(exam.start)} · ${exam.duration}′`} />
        <ExamMeta icon={MapPin} label={t('common.room')} value={exam.room} />
        <ExamMeta icon={Armchair} label={t('exams.seat')} value={exam.seat} />
        <ExamMeta icon={User} label={t('common.lecturer')} value={lecturer?.name ?? ''} wide />
      </dl>
    </Card>
  )
}

function ExamMeta({ icon: Icon, label, value, wide }: { icon: typeof Clock; label: string; value: string; wide?: boolean }) {
  return (
    <div className={cn('min-w-0', wide && 'col-span-2')}>
      <dt className="flex items-center gap-1.5 text-[11.5px] text-one-subtle"><Icon size={12} aria-hidden /> {label}</dt>
      <dd className="mt-0.5 truncate font-medium text-one-fg">{value}</dd>
    </div>
  )
}

/* ============================ Grades ============================ */

export function GradesPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const [semester, setSemester] = useState('4')
  const [trendAsTable, setTrendAsTable] = useState(false)
  const q = useMockData(() => allCourses, [] as Course[])
  const lastIps = gpaHistory[gpaHistory.length - 1].gpa
  const pct = Math.round((student.creditsEarned / student.creditsRequired) * 100)
  const trend = gpaHistory.map((g) => ({ label: t('grades.semesterShort', { n: g.semester }), gpa: g.gpa }))
  const perf = courses.map((c) => ({ code: c.code, score: c.score }))

  return (
    <>
      <PageHeader
        title={t('grades.title')}
        description={t('grades.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('grades.title') }]}
        actions={<Button variant="secondary" icon={Download} onClick={() => toast(t('grades.transcriptToast'), 'info')}>{t('grades.downloadTranscript')}</Button>}
      />
      <Async query={q} skeleton={<div className="space-y-6"><StatsSkeleton /><TableSkeleton /></div>} isEmpty={(d) => d.length === 0} empty={<Card><EmptyState icon={Award} title={t('states.emptyGrades')} body={t('states.emptyGradesBody')} /></Card>}>
        {(all) => {
          const rows = all.filter((c) => String(c.semester) === semester)
          return (
            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <Stat label={t('grades.cumulativeGpa')} value={fmt.number(student.gpa, 2)} icon={TrendingUp} tone="royal" hint="/ 4.00" />
                <Stat label={t('grades.semesterGpa')} value={fmt.number(lastIps, 2)} icon={ChartLine} tone="teal" hint={t('grades.semesterShort', { n: 4 })} />
                <Stat label={t('dashboard.totalCredits')} value={student.creditsEarned} icon={Layers} tone="gold" hint={t('dashboard.creditsOf', { done: student.creditsEarned, total: student.creditsRequired })} />
                <Stat label={t('dashboard.academicProgress')} value={`${pct}%`} icon={Award} tone="ok">
                  <Progress value={pct} tone="ok" label={t('dashboard.academicProgress')} className="mt-3" />
                </Stat>
              </div>

              <div className="grid gap-6 lg:grid-cols-2">
                <Card as="section" aria-labelledby="trend-h">
                  <CardHeader
                    id="trend-h"
                    title={t('grades.gpaTrend')}
                    description={t('grades.gpaTrendDesc')}
                    action={
                      <IconButton
                        icon={trendAsTable ? ChartLine : Table2}
                        label={trendAsTable ? t('grades.chartView') : t('grades.tableView')}
                        size="sm"
                        variant="secondary"
                        onClick={() => setTrendAsTable((v) => !v)}
                      />
                    }
                  />
                  <CardBody>
                    {trendAsTable ? (
                      <table className="w-full text-[14px]">
                        <caption className="sr-only">{t('grades.gpaTrend')}</caption>
                        <thead><tr className="border-b border-one-line text-left text-[12px] uppercase text-one-subtle"><th className="py-2 font-semibold">{t('profile.semester')}</th><th className="py-2 text-right font-semibold">{t('grades.semesterGpa')}</th></tr></thead>
                        <tbody>{trend.map((r) => <tr key={r.label} className="border-b border-one-line last:border-0"><td className="py-2.5">{r.label}</td><td className="py-2.5 text-right font-medium tabular">{fmt.number(r.gpa, 2)}</td></tr>)}</tbody>
                      </table>
                    ) : (
                      <TrendLine data={trend} x="label" y="gpa" seriesLabel={t('grades.semesterGpa')} domain={[3, 4]} format={(v) => fmt.number(v, 2)} ariaLabel={`${t('grades.gpaTrend')}: ${trend.map((r) => `${r.label} ${r.gpa}`).join(', ')}`} />
                    )}
                  </CardBody>
                </Card>
                <Card as="section" aria-labelledby="perf-h">
                  <CardHeader id="perf-h" title={t('grades.semesterPerformance')} description={t('grades.semesterPerformanceDesc')} />
                  <CardBody>
                    <Bars data={perf} x="code" y="score" seriesLabel={t('grades.score')} domain={[0, 100]} ariaLabel={`${t('grades.semesterPerformance')}: ${perf.map((p) => `${p.code} ${p.score}`).join(', ')}`} />
                  </CardBody>
                </Card>
              </div>

              <Card as="section" aria-labelledby="gt-h">
                <CardHeader
                  id="gt-h"
                  title={t('grades.gradeTable')}
                  action={
                    <div className="w-44">
                      <Select aria-label={t('common.selectSemester')} value={semester} onChange={(e) => setSemester(e.target.value)}>
                        {[5, 4, 3, 2, 1].map((s) => <option key={s} value={s}>{t('common.semesterN', { n: s })}</option>)}
                      </Select>
                    </div>
                  }
                />
                <div className="mt-4">
                  <DataTable
                    caption={t('grades.gradeTable')}
                    rows={rows}
                    rowKey={(c) => c.id}
                    initialSort={{ key: 'score', dir: 'desc' }}
                    empty={<EmptyState compact title={t('states.emptyGrades')} body={t('states.emptyGradesBody')} />}
                    columns={[
                      { key: 'course', header: t('grades.course'), render: (c) => <span><span className="font-medium">{c.name}</span> <span className="ml-1 font-mono text-[12px] text-one-subtle">{c.code}</span></span>, sortValue: (c) => c.name },
                      { key: 'credits', header: t('academic.credits'), render: (c) => c.credits, align: 'right', sortValue: (c) => c.credits },
                      { key: 'grade', header: t('grades.grade'), render: (c) => <Badge tone={c.grade.startsWith('A') ? 'ok' : 'royal'}>{c.grade}</Badge>, align: 'center', sortValue: (c) => c.score },
                      { key: 'score', header: t('grades.score'), render: (c) => c.score, align: 'right', sortValue: (c) => c.score },
                      { key: 'status', header: t('common.status'), render: (c) => <StatusBadge status={c.status === 'active' ? 'inProgress' : 'passed'} /> },
                    ]}
                  />
                </div>
              </Card>
            </div>
          )
        }}
      </Async>
    </>
  )
}

/* ============================ Attendance ============================ */

export function AttendancePage() {
  const { t, fmt } = useI18n()
  const q = useMockData(() => courses, [] as Course[])
  return (
    <>
      <PageHeader title={t('attendance.title')} description={t('attendance.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('attendance.title') }]} />
      <Async query={q} skeleton={<div className="space-y-6"><StatsSkeleton /><ListSkeleton /></div>} isEmpty={(d) => d.length === 0} empty={<Card><EmptyState icon={UserCheck} title={t('states.emptyAttendance')} body={t('states.emptyAttendanceBody')} /></Card>}>
        {(list) => {
          const total = list.reduce(
            (acc, c) => ({
              present: acc.present + c.attendance.present,
              late: acc.late + c.attendance.late,
              excused: acc.excused + c.attendance.excused,
              absent: acc.absent + c.attendance.absent,
              total: acc.total + c.attendance.total,
            }),
            { present: 0, late: 0, excused: 0, absent: 0, total: 0 },
          )
          const overall = attendanceRate(total)
          return (
            <div className="space-y-6">
              <div className="grid gap-4 lg:grid-cols-[minmax(280px,1fr)_2fr]">
                <Card padded className="flex items-center gap-5">
                  <ProgressRing value={overall} size={120} stroke={11} tone={overall < ATTENDANCE_MIN ? 'bad' : 'ok'} label={`${t('attendance.overall')}: ${overall}%`}>
                    <span className="font-display text-[26px] font-bold text-one-fg">{overall}%</span>
                  </ProgressRing>
                  <div>
                    <p className="t-h3 text-one-fg">{t('attendance.overall')}</p>
                    <p className="t-small mt-1 text-one-subtle">{t('attendance.sessionsAttended', { a: total.present + total.late, b: total.total })}</p>
                    <p className="t-small mt-2 text-one-muted">{t('attendance.minRequired', { pct: ATTENDANCE_MIN })}</p>
                  </div>
                </Card>
                <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                  <Stat label={t('status.present')} value={total.present} icon={CircleCheck} tone="ok" />
                  <Stat label={t('status.late')} value={total.late} icon={Clock} tone="warn" />
                  <Stat label={t('status.excused')} value={total.excused} icon={FileCheck} tone="sky" />
                  <Stat label={t('status.absent')} value={total.absent} icon={TriangleAlert} tone="bad" />
                </div>
              </div>

              <div className="grid gap-6 lg:grid-cols-3">
                <Card as="section" aria-labelledby="bc-h" className="lg:col-span-2">
                  <CardHeader id="bc-h" title={t('attendance.byCourse')} description={t('attendance.minRequired', { pct: ATTENDANCE_MIN })} />
                  <CardBody>
                    <ul className="space-y-4">
                      {list.map((c) => {
                        const rate = attendanceRate(c.attendance)
                        const risk = rate < ATTENDANCE_MIN
                        return (
                          <li key={c.id}>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                              <Link to={`/one/courses/${c.id}`} className="min-w-0 truncate text-[14px] font-medium text-one-fg hover:text-one-royal">
                                {c.name}
                              </Link>
                              <span className="flex items-center gap-2">
                                <span className="text-[12.5px] text-one-subtle tabular">{t('attendance.sessionsAttended', { a: c.attendance.present + c.attendance.late, b: c.attendance.total })}</span>
                                <Badge tone={risk ? 'bad' : 'ok'} dot>{risk ? t('attendance.atRisk') : t('attendance.onTrack')}</Badge>
                                <span className="w-10 text-right text-[14px] font-semibold tabular text-one-fg">{rate}%</span>
                              </span>
                            </div>
                            <div className="relative mt-2">
                              <Progress value={rate} tone={risk ? 'bad' : 'ok'} label={`${c.name}: ${rate}%`} size="md" />
                              <span aria-hidden className="absolute -top-0.5 h-3.5 w-0.5 rounded bg-one-fg/40" style={{ left: `${ATTENDANCE_MIN}%` }} />
                            </div>
                          </li>
                        )
                      })}
                    </ul>
                  </CardBody>
                </Card>
                <Card as="section" aria-labelledby="rs-h">
                  <CardHeader id="rs-h" title={t('attendance.recent')} />
                  <CardBody>
                    <ul className="divide-y divide-one-line">
                      {recentSessions.map((s, i) => (
                        <li key={i} className="flex items-center justify-between gap-3 py-3">
                          <div className="min-w-0">
                            <p className="truncate text-[14px] font-medium text-one-fg">{courseById(s.courseId)?.name}</p>
                            <p className="text-[12.5px] text-one-subtle">{fmt.date(s.date, { weekday: 'short', day: 'numeric', month: 'short' })} · {fmt.time(s.date)}</p>
                          </div>
                          <StatusBadge status={s.status} />
                        </li>
                      ))}
                    </ul>
                  </CardBody>
                </Card>
              </div>
            </div>
          )
        }}
      </Async>
    </>
  )
}
