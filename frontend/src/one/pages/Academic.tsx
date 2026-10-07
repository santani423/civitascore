import { useMemo, useState, type ReactNode } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import {
  BookOpen,
  CalendarDays,
  Clock,
  Download,
  FileArchive,
  FileText,
  GraduationCap,
  LayoutGrid,
  List,
  Mail,
  MapPin,
  MessagesSquare,
  Presentation,
  Send,
  ShieldCheck,
  Star,
  User,
  Video,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { student, lecturerById } from '../data/people'
import {
  allCourses,
  assignments,
  attendanceRate,
  ATTENDANCE_MIN,
  courseById,
  courses,
  discussions,
  exams,
  gradeComponents,
  materials,
  weeklyTopics,
  type Course,
} from '../data/academic'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge, StatusBadge } from '../components/ui/Badge'
import { Button } from '../components/ui/Button'
import { Async, CardGridSkeleton, EmptyState, ProfileSkeleton, TableSkeleton } from '../components/ui/Feedback'
import { Avatar, Cover, IconTile, MetaItem, PageHeader, Progress, ProgressRing, Stat } from '../components/ui/Display'
import { DataTable, type Column } from '../components/ui/DataTable'
import { SearchInput, Select, Textarea } from '../components/ui/Form'
import { Segmented, TabPanel, Tabs } from '../components/ui/Tabs'
import { useDue } from '../components/shared/bits'
import type { Tone } from '../data/campus'

export function useWeekday() {
  const { fmt } = useI18n()
  // 1 Jan 2024 was a Monday, so day N maps to 2024-01-N.
  return (day: number) => fmt.weekday(new Date(2024, 0, day))
}

export function slotLabel(c: Course, weekday: (d: number) => string) {
  return c.slots.map((s) => `${weekday(s.day)} ${s.start}–${s.end}`).join(', ') || '—'
}

const courseTones: Tone[] = ['navy', 'royal', 'teal', 'violet', 'gold', 'slate', 'rose', 'green']
export const courseTone = (id: string): Tone => courseTones[Math.abs(Array.from(id).reduce((a, c) => a + c.charCodeAt(0), 0)) % courseTones.length]

/* ======================= Academic overview ======================= */

export function AcademicPage() {
  const { t, fmt } = useI18n()
  const weekday = useWeekday()
  const navigate = useNavigate()
  const [semester, setSemester] = useState(String(student.semester))
  const [query, setQuery] = useState('')
  const q = useMockData(() => allCourses, [] as Course[])
  const advisor = lecturerById(student.advisorId)
  const pct = Math.round((student.creditsEarned / student.creditsRequired) * 100)
  const semesterCredits = courses.reduce((a, c) => a + c.credits, 0)

  const columns: Column<Course>[] = [
    { key: 'code', header: t('academic.courseCode'), render: (c) => <span className="font-mono text-[13px] text-one-muted">{c.code}</span>, sortValue: (c) => c.code },
    {
      key: 'name',
      header: t('academic.courseName'),
      render: (c) => <Link to={`/one/courses/${c.id}`} className="font-medium text-one-fg hover:text-one-royal">{c.name}</Link>,
      sortValue: (c) => c.name,
    },
    { key: 'lecturer', header: t('common.lecturer'), render: (c) => <span className="text-one-muted">{lecturerById(c.lecturerId)?.name}</span> },
    { key: 'credits', header: t('academic.credits'), render: (c) => c.credits, sortValue: (c) => c.credits, align: 'right' },
    { key: 'schedule', header: t('academic.schedule'), render: (c) => <span className="text-one-muted">{slotLabel(c, weekday)}</span>, hideOnMobile: true },
    { key: 'room', header: t('common.room'), render: (c) => c.slots[0]?.room ?? '—' },
    { key: 'status', header: t('common.status'), render: (c) => <StatusBadge status={c.status} /> },
  ]

  return (
    <>
      <PageHeader
        title={t('academic.title')}
        description={t('academic.subtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('academic.title') }]}
        actions={<Button variant="secondary" icon={CalendarDays} to="/one/schedule">{t('nav.schedule')}</Button>}
      />

      <Card as="section" aria-labelledby="ov-h" className="mb-6">
        <CardHeader id="ov-h" title={t('academic.overview')} />
        <CardBody>
          <div className="grid gap-6 lg:grid-cols-[auto_1fr] lg:items-center">
            <div className="flex items-center gap-5">
              <ProgressRing value={pct} size={128} stroke={11} tone="ok" label={`${t('academic.progress')}: ${pct}%`}>
                <span className="font-display text-[26px] font-bold text-one-fg">{pct}%</span>
                <span className="text-[11px] text-one-subtle">{t('dashboard.academicProgress')}</span>
              </ProgressRing>
              <div>
                <p className="text-[13px] text-one-subtle">{t('academic.progress')}</p>
                <p className="mt-1 font-display text-[18px] font-semibold text-one-fg">
                  {t('dashboard.creditsOf', { done: student.creditsEarned, total: student.creditsRequired })}
                </p>
                <p className="t-small mt-1 text-one-muted">
                  {t('academic.expectedGraduation')}: <span className="font-medium text-one-fg">{student.expectedGraduation}</span>
                </p>
              </div>
            </div>
            <dl className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              <KV label={t('dashboard.currentSemester')} value={t('common.semesterN', { n: student.semester })} />
              <KV label={t('dashboard.gpa')} value={fmt.number(student.gpa, 2)} />
              <KV label={t('academic.semesterCredits')} value={t('common.credits', { count: semesterCredits })} />
              <KV
                label={t('academic.standing')}
                value={
                  <span className="flex flex-wrap gap-1.5">
                    <Badge tone="ok" icon={ShieldCheck}>{t('academic.goodStanding')}</Badge>
                  </span>
                }
              />
              <KV label={t('academic.advisor')} value={<span className="flex items-center gap-2"><Avatar name={advisor?.name ?? ''} size="xs" /> <span className="truncate">{advisor?.name}</span></span>} className="col-span-2" />
            </dl>
          </div>
          <div className="mt-5 flex items-center gap-2 rounded-xl bg-one-gold/10 px-4 py-3 text-[13.5px] text-one-gold">
            <Star size={16} aria-hidden /> {t('academic.deansList')}
          </div>
        </CardBody>
      </Card>

      <Card as="section" aria-labelledby="cl-h">
        <CardHeader id="cl-h" title={t('academic.courseList')} />
        <div className="flex flex-col gap-3 px-5 pb-4 pt-4 sm:flex-row sm:px-6">
          <SearchInput value={query} onValueChange={setQuery} label={t('academic.searchCourses')} placeholder={t('academic.searchCourses')} className="flex-1" clearLabel={t('common.clearFilters')} />
          <div className="sm:w-48">
            <Select aria-label={t('common.selectSemester')} value={semester} onChange={(e) => setSemester(e.target.value)}>
              {[5, 4, 3, 2, 1].map((s) => (
                <option key={s} value={s}>{t('common.semesterN', { n: s })}</option>
              ))}
            </Select>
          </div>
        </div>
        <Async query={q} skeleton={<div className="px-5 pb-5"><TableSkeleton cols={6} /></div>}>
          {(all) => {
            const needle = query.toLowerCase()
            const rows = all.filter(
              (c) =>
                String(c.semester) === semester &&
                `${c.name} ${c.code} ${lecturerById(c.lecturerId)?.name}`.toLowerCase().includes(needle),
            )
            return (
              <DataTable
                caption={t('academic.courseList')}
                columns={columns}
                rows={rows}
                rowKey={(c) => c.id}
                onRowClick={(c) => navigate(`/one/courses/${c.id}`)}
                empty={<EmptyState icon={BookOpen} title={t('states.emptyCourses')} body={t('states.emptyCoursesBody')} />}
              />
            )
          }}
        </Async>
      </Card>
    </>
  )
}

function KV({ label, value, className }: { label: string; value: ReactNode; className?: string }) {
  return (
    <div className={cn('min-w-0 rounded-xl bg-one-sunken/60 px-3.5 py-3', className)}>
      <dt className="text-[12px] text-one-subtle">{label}</dt>
      <dd className="mt-1 truncate text-[14.5px] font-semibold text-one-fg">{value}</dd>
    </div>
  )
}

/* ======================= Courses (catalog) ======================= */

export function CoursesPage() {
  const { t } = useI18n()
  const weekday = useWeekday()
  const navigate = useNavigate()
  const [view, setView] = useState<'grid' | 'table'>('grid')
  const q = useMockData(() => courses, [] as Course[])

  return (
    <>
      <PageHeader
        title={t('academic.coursesTitle')}
        description={t('academic.coursesSubtitle')}
        crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.courses') }]}
        actions={
          <Segmented
            label={t('schedule.viewMode')}
            value={view}
            onChange={setView}
            items={[
              { value: 'grid', label: t('academic.gridView'), icon: LayoutGrid },
              { value: 'table', label: t('academic.tableView'), icon: List },
            ]}
          />
        }
      />
      <Async
        query={q}
        skeleton={<CardGridSkeleton count={6} />}
        isEmpty={(d) => d.length === 0}
        empty={<Card><EmptyState icon={BookOpen} title={t('states.emptyCourses')} body={t('states.emptyCoursesBody')} /></Card>}
      >
        {(list) =>
          view === 'grid' ? (
            <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {list.map((c) => (
                <li key={c.id}>
                  <CourseCard course={c} weekday={weekday} />
                </li>
              ))}
            </ul>
          ) : (
            <Card>
              <DataTable
                caption={t('academic.coursesTitle')}
                rows={list}
                rowKey={(c) => c.id}
                onRowClick={(c) => navigate(`/one/courses/${c.id}`)}
                columns={[
                  { key: 'name', header: t('academic.courseName'), render: (c) => <span className="font-medium">{c.name}</span>, sortValue: (c) => c.name },
                  { key: 'code', header: t('academic.courseCode'), render: (c) => c.code, sortValue: (c) => c.code },
                  { key: 'lecturer', header: t('common.lecturer'), render: (c) => lecturerById(c.lecturerId)?.name },
                  { key: 'credits', header: t('academic.credits'), render: (c) => c.credits, align: 'right', sortValue: (c) => c.credits },
                  { key: 'att', header: t('course.attendanceRate'), render: (c) => `${attendanceRate(c.attendance)}%`, align: 'right', sortValue: (c) => attendanceRate(c.attendance) },
                  { key: 'score', header: t('course.currentScore'), render: (c) => c.score, align: 'right', sortValue: (c) => c.score },
                ]}
              />
            </Card>
          )
        }
      </Async>
    </>
  )
}

function CourseCard({ course, weekday }: { course: Course; weekday: (d: number) => string }) {
  const { t } = useI18n()
  const lecturer = lecturerById(course.lecturerId)
  const rate = attendanceRate(course.attendance)
  return (
    <Card as="article" interactive className="group relative flex h-full flex-col overflow-hidden">
      <Cover tone={courseTone(course.id)} icon={BookOpen} className="h-24">
        <span className="absolute left-4 top-4 rounded-lg bg-white/15 px-2 py-1 font-mono text-[12px] font-semibold text-white ring-1 ring-inset ring-white/20 backdrop-blur">
          {course.code}
        </span>
      </Cover>
      <div className="flex flex-1 flex-col p-5">
        <h3 className="text-[16px] font-semibold leading-snug text-one-fg">
          <Link to={`/one/courses/${course.id}`} className="after:absolute after:inset-0 group-hover:text-one-royal">
            {course.name}
          </Link>
        </h3>
        <div className="mt-3 flex items-center gap-2">
          <Avatar name={lecturer?.name ?? ''} size="xs" />
          <span className="truncate text-[13px] text-one-muted">{lecturer?.name}</span>
        </div>
        <div className="mb-5 mt-3 space-y-1.5">
          <MetaItem icon={Clock}>{slotLabel(course, weekday)}</MetaItem>
          <MetaItem icon={MapPin}>{course.slots.map((s) => s.room).join(' · ')}</MetaItem>
        </div>
        <div className="mt-auto grid grid-cols-2 gap-4 border-t border-one-line pt-4">
          <div>
            <p className="text-[12px] text-one-subtle">{t('course.attendanceRate')}</p>
            <p className="mt-0.5 text-[14px] font-semibold text-one-fg tabular">{rate}%</p>
            <Progress value={rate} tone={rate < ATTENDANCE_MIN ? 'bad' : 'teal'} label={t('course.attendanceRate')} className="mt-1.5" />
          </div>
          <div>
            <p className="text-[12px] text-one-subtle">{t('course.currentScore')}</p>
            <p className="mt-0.5 text-[14px] font-semibold text-one-fg tabular">{course.score}</p>
            <Progress value={course.score} tone="royal" label={t('course.currentScore')} className="mt-1.5" />
          </div>
        </div>
      </div>
    </Card>
  )
}

/* ======================= Course detail ======================= */

type CourseTab = 'overview' | 'materials' | 'assignments' | 'exams' | 'attendance' | 'grades' | 'discussion'

const materialIcon: Record<string, typeof FileText> = { PDF: FileText, PPTX: Presentation, ZIP: FileArchive, Video }

export function CourseDetailPage() {
  const { id = '' } = useParams()
  const { t } = useI18n()
  const weekday = useWeekday()
  const [tab, setTab] = useState<CourseTab>('overview')
  const course = courseById(id)
  const q = useMockData(() => course ?? null, null)

  if (!course) {
    return <Card><EmptyState icon={BookOpen} title={t('course.notFound')} action={<Button to="/one/courses" variant="secondary">{t('common.back')}</Button>} /></Card>
  }
  const lecturer = lecturerById(course.lecturerId)
  const courseAssignments = assignments.filter((a) => a.courseId === course.id)
  const courseExams = exams.filter((e) => e.courseId === course.id)
  const courseMaterials = materials.filter((m) => m.courseId === course.id)

  return (
    <Async query={q} skeleton={<ProfileSkeleton />}>
      {() => (
        <>
          <PageHeader
            title={course.name}
            crumbs={[{ label: t('nav.courses'), to: '/one/courses' }, { label: course.code }]}
          />
          <Card className="mb-6 overflow-hidden">
            <Cover tone={courseTone(course.id)} icon={GraduationCap} className="h-28 sm:h-32" />
            <div className="grid gap-4 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
              <HeaderMeta icon={BookOpen} label={t('academic.courseCode')} value={<span className="font-mono">{course.code}</span>} />
              <HeaderMeta icon={User} label={t('common.lecturer')} value={lecturer?.name} />
              <HeaderMeta icon={GraduationCap} label={t('academic.credits')} value={t('common.credits', { count: course.credits })} />
              <HeaderMeta icon={Clock} label={t('academic.schedule')} value={slotLabel(course, weekday)} />
            </div>
          </Card>

          <Tabs
            id="course"
            label={course.name}
            value={tab}
            onChange={setTab}
            className="mb-6"
            items={(['overview', 'materials', 'assignments', 'exams', 'attendance', 'grades', 'discussion'] as CourseTab[]).map((v) => ({
              value: v,
              label: t(`course.tabs.${v}`),
              count: v === 'materials' ? courseMaterials.length : v === 'assignments' ? courseAssignments.length : undefined,
            }))}
          />

          <TabPanel id="course" value={tab} key={tab}>
            {tab === 'overview' && <OverviewTab course={course} />}
            {tab === 'materials' && <MaterialsTab items={courseMaterials} />}
            {tab === 'assignments' && <CourseAssignments courseId={course.id} />}
            {tab === 'exams' && <CourseExams courseId={course.id} empty={courseExams.length === 0} />}
            {tab === 'attendance' && <AttendanceTab course={course} />}
            {tab === 'grades' && <GradesTab course={course} />}
            {tab === 'discussion' && <DiscussionTab courseId={course.id} />}
          </TabPanel>
        </>
      )}
    </Async>
  )
}

function HeaderMeta({ icon: Icon, label, value }: { icon: typeof BookOpen; label: string; value: ReactNode }) {
  return (
    <div className="flex items-start gap-3">
      <IconTile icon={Icon} tone="royal" size="sm" />
      <div className="min-w-0">
        <p className="text-[12px] text-one-subtle">{label}</p>
        <p className="mt-0.5 text-[14px] font-medium text-one-fg">{value}</p>
      </div>
    </div>
  )
}

function OverviewTab({ course }: { course: Course }) {
  const { t } = useI18n()
  const lecturer = lecturerById(course.lecturerId)
  const topics = weeklyTopics[course.id] ?? course.outcomes.map((o) => o)
  return (
    <div className="grid gap-6 lg:grid-cols-3">
      <div className="space-y-6 lg:col-span-2">
        <Card padded>
          <h2 className="t-h3 text-one-fg">{t('course.about')}</h2>
          <p className="t-body mt-2 text-one-muted">{course.description}</p>
          <h3 className="t-h3 mt-6 text-one-fg">{t('course.outcomes')}</h3>
          <ul className="mt-3 space-y-2">
            {course.outcomes.map((o) => (
              <li key={o} className="flex gap-3 text-[14.5px] text-one-muted">
                <span aria-hidden className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-one-royal" />
                {o}
              </li>
            ))}
          </ul>
        </Card>
        <Card padded>
          <h2 className="t-h3 text-one-fg">{t('course.weeklyPlan')}</h2>
          <ol className="mt-4 space-y-2">
            {topics.map((topic, i) => (
              <li key={topic} className={cn('flex items-center gap-4 rounded-xl px-3 py-2.5', i < 7 ? 'bg-one-sunken/50' : 'border border-dashed border-one-line')}>
                <span className="w-16 shrink-0 text-[12px] font-semibold text-one-subtle">{t('course.week', { n: i + 1 })}</span>
                <span className={cn('text-[14px]', i < 7 ? 'text-one-fg' : 'text-one-muted')}>{topic}</span>
                {i === 6 && <Badge tone="royal" className="ml-auto">{t('status.active')}</Badge>}
              </li>
            ))}
          </ol>
        </Card>
      </div>
      <div className="space-y-6">
        <Card padded>
          <p className="t-caption text-one-subtle">{t('common.lecturer')}</p>
          <div className="mt-3 flex items-center gap-3">
            <Avatar name={lecturer?.name ?? ''} size="lg" />
            <div className="min-w-0">
              <p className="font-semibold text-one-fg">{lecturer?.name}</p>
              <p className="text-[13px] text-one-subtle">{lecturer?.title}</p>
            </div>
          </div>
          <p className="t-small mt-3 text-one-muted">{lecturer?.expertise}</p>
          <Button variant="secondary" size="sm" icon={Mail} className="mt-4" block onClick={() => window.open(`mailto:${lecturer?.email}`)}>
            {lecturer?.email}
          </Button>
        </Card>
        <Card padded>
          <p className="t-caption text-one-subtle">{t('course.gradeComponents')}</p>
          <ul className="mt-3 space-y-3">
            {gradeComponents.map((g) => (
              <li key={g.key}>
                <div className="flex justify-between text-[13.5px]">
                  <span className="text-one-muted">{g.label}</span>
                  <span className="font-semibold text-one-fg tabular">{g.weight}%</span>
                </div>
                <Progress value={g.weight} max={40} label={g.label} className="mt-1.5" />
              </li>
            ))}
          </ul>
        </Card>
      </div>
    </div>
  )
}

function MaterialsTab({ items }: { items: typeof materials }) {
  const { t } = useI18n()
  const toast = useToast()
  if (items.length === 0) return <Card><EmptyState icon={FileText} title={t('states.emptyDocuments')} /></Card>
  return (
    <Card>
      <ul className="divide-y divide-one-line">
        {items.map((m) => {
          const Icon = materialIcon[m.type] ?? FileText
          return (
            <li key={m.id} className="flex items-center gap-4 px-5 py-4 sm:px-6">
              <IconTile icon={Icon} tone={m.type === 'Video' ? 'bad' : m.type === 'ZIP' ? 'gold' : 'royal'} />
              <div className="min-w-0 flex-1">
                <p className="truncate text-[14.5px] font-medium text-one-fg">{m.title}</p>
                <p className="text-[12.5px] text-one-subtle">{m.type} · {m.size}</p>
              </div>
              <Button variant="ghost" size="sm" icon={Download} onClick={() => toast(`${t('common.download')}: ${m.title}`, 'info')} aria-label={`${t('common.download')} ${m.title}`}>
                <span className="hidden sm:inline">{t('common.download')}</span>
              </Button>
            </li>
          )
        })}
      </ul>
    </Card>
  )
}

export function CourseAssignments({ courseId }: { courseId: string }) {
  const { t, fmt } = useI18n()
  const due = useDue()
  const submitted = useApp((s) => s.submitted)
  const list = assignments.filter((a) => a.courseId === courseId)
  if (list.length === 0) return <Card><EmptyState title={t('states.emptyAssignments')} body={t('states.emptyAssignmentsBody')} /></Card>
  return (
    <Card>
      <ul className="divide-y divide-one-line">
        {list.map((a) => {
          const status = submitted[a.id] && a.status !== 'graded' ? 'submitted' : a.status
          const d = due(a.due)
          return (
            <li key={a.id} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:px-6">
              <div className="min-w-0 flex-1">
                <p className="text-[14.5px] font-medium text-one-fg">{a.title}</p>
                <p className="text-[12.5px] text-one-subtle">{t('common.deadline')}: {fmt.date(a.due, { day: 'numeric', month: 'short' })} · {fmt.time(a.due)}</p>
              </div>
              <div className="flex items-center gap-2">
                {status !== 'graded' && status !== 'submitted' && <Badge tone={d.tone}>{d.label}</Badge>}
                {a.score !== undefined && <Badge tone="ok">{a.score}/100</Badge>}
                <StatusBadge status={status} />
              </div>
            </li>
          )
        })}
      </ul>
      <div className="border-t border-one-line px-5 py-3 sm:px-6">
        <Link to="/one/assignments" className="text-[13px] font-medium text-one-royal hover:underline">{t('common.viewAll')}</Link>
      </div>
    </Card>
  )
}

function CourseExams({ courseId, empty }: { courseId: string; empty: boolean }) {
  const { t, fmt } = useI18n()
  if (empty) return <Card><EmptyState icon={CalendarDays} title={t('states.emptyExams')} body={t('states.emptyExamsBody')} /></Card>
  return (
    <div className="grid gap-4 sm:grid-cols-2">
      {exams
        .filter((e) => e.courseId === courseId)
        .map((e) => (
          <Card key={e.id} padded>
            <Badge tone="bad">{t(`exams.types.${e.type}`)}</Badge>
            <p className="mt-3 font-display text-[18px] font-semibold text-one-fg">{fmt.date(e.start, { weekday: 'long', day: 'numeric', month: 'long' })}</p>
            <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1">
              <MetaItem icon={Clock}>{fmt.time(e.start)} · {t('common.minutes', { count: e.duration })}</MetaItem>
              <MetaItem icon={MapPin}>{e.room}</MetaItem>
            </div>
          </Card>
        ))}
    </div>
  )
}

function AttendanceTab({ course }: { course: Course }) {
  const { t } = useI18n()
  const a = course.attendance
  const rate = attendanceRate(a)
  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
      <Stat label={t('course.attendanceRate')} value={`${rate}%`} tone={rate < ATTENDANCE_MIN ? 'bad' : 'ok'} className="lg:col-span-1">
        <Progress value={rate} tone={rate < ATTENDANCE_MIN ? 'bad' : 'ok'} label={t('course.attendanceRate')} className="mt-3" />
      </Stat>
      {(['present', 'late', 'excused', 'absent'] as const).map((k) => (
        <Stat key={k} label={t(`status.${k}`)} value={a[k]} hint={t('attendance.sessionsAttended', { a: a[k], b: a.total })} />
      ))}
    </div>
  )
}

function GradesTab({ course }: { course: Course }) {
  const { t } = useI18n()
  const scores: Record<string, number | null> = { assignments: course.score + 2, quiz: course.score - 3, midterm: null, final: null }
  return (
    <Card>
      <DataTable
        caption={t('course.gradeComponents')}
        rows={gradeComponents}
        rowKey={(g) => g.key}
        columns={[
          { key: 'c', header: t('course.component'), render: (g) => <span className="font-medium">{g.label}</span> },
          { key: 'w', header: t('course.weight'), render: (g) => `${g.weight}%`, align: 'right' },
          { key: 's', header: t('grades.score'), render: (g) => (scores[g.key] === null ? <StatusBadge status="scheduled" /> : Math.min(100, scores[g.key] ?? 0)), align: 'right' },
        ]}
        footer={
          <p className="text-[14px] text-one-fg">
            {t('course.currentScore')}: <span className="font-display text-[18px] font-bold">{course.score}</span> <Badge tone="ok">{course.grade}</Badge>
          </p>
        }
      />
    </Card>
  )
}

function DiscussionTab({ courseId }: { courseId: string }) {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const posts = useApp((s) => s.posts[courseId])
  const addPost = useApp((s) => s.addPost)
  const [body, setBody] = useState('')
  const list = useMemo(
    () => [
      ...(posts ?? []).map((p) => ({ id: p.id, author: student.name, at: p.at, body: p.body, replies: 0 })),
      ...discussions.filter((d) => d.courseId === courseId),
    ],
    [posts, courseId],
  )

  return (
    <div className="space-y-4">
      <Card padded>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            if (!body.trim()) return
            addPost(courseId, body.trim())
            setBody('')
            toast(t('course.postedToast'))
          }}
          className="flex gap-3"
        >
          <Avatar name={student.name} />
          <div className="flex-1 space-y-3">
            <Textarea aria-label={t('course.writePost')} placeholder={t('course.writePost')} value={body} onChange={(e) => setBody(e.target.value)} className="min-h-[80px]" />
            <div className="flex justify-end">
              <Button type="submit" icon={Send} disabled={!body.trim()}>{t('course.post')}</Button>
            </div>
          </div>
        </form>
      </Card>
      {list.length === 0 ? (
        <Card><EmptyState icon={MessagesSquare} title={t('states.emptyDiscussion')} body={t('states.emptyDiscussionBody')} /></Card>
      ) : (
        list.map((p) => (
          <Card key={p.id} as="article" padded className="flex gap-3">
            <Avatar name={p.author} />
            <div className="min-w-0 flex-1">
              <p className="text-[14px]">
                <span className="font-semibold text-one-fg">{p.author}</span>{' '}
                <span className="text-one-subtle">· {fmt.date(p.at, { day: 'numeric', month: 'short' })} {fmt.time(p.at)}</span>
              </p>
              <p className="t-body mt-1 text-one-muted">{p.body}</p>
              <p className="mt-2 text-[12.5px] font-medium text-one-royal">{t('course.replies', { count: p.replies })}</p>
            </div>
          </Card>
        ))
      )}
    </div>
  )
}

