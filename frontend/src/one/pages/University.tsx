import { useState, type ReactNode } from 'react'
import { Link, useParams } from 'react-router-dom'
import {
  ArrowRight,
  Award,
  BookOpen,
  Briefcase,
  Building2,
  CalendarDays,
  CircleCheck,
  Clock,
  Earth,
  FileText,
  FlaskConical,
  GraduationCap,
  Handshake,
  Landmark,
  Lightbulb,
  MapPin,
  Plane,
  Quote,
  Rocket,
  ScrollText,
  Send,
  Sparkles,
  Table2,
  ChartColumn,
  Users,
  UserCheck,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../i18n'
import { useMockData } from '../hooks/useMockData'
import { useApp } from '../store/app'
import { useToast } from '../store/toast'
import { daysUntil } from '../lib/dates'
import { lecturers, lecturerById } from '../data/people'
import { events, news } from '../data/campus'
import { scholarships, type Scholarship, type ScholarshipTag } from '../data/services'
import {
  faculties,
  facultyById,
  intlEvents,
  intlPrograms,
  isCurriculum,
  partners,
  programById,
  programs,
  publications,
  researchCategories,
  researchCenters,
  researchProjects,
  researchStats,
  researchTrend,
  universityStats,
  type Faculty,
  type Level,
  type Program,
  type Region,
} from '../data/university'
import { Card, CardBody, CardHeader } from '../components/ui/Card'
import { Badge } from '../components/ui/Badge'
import { Button, IconButton } from '../components/ui/Button'
import { Async, CardGridSkeleton, EmptyState, StatsSkeleton } from '../components/ui/Feedback'
import { Avatar, Cover, FilterChips, IconTile, MetaItem, PageHeader, Progress, Stat } from '../components/ui/Display'
import { Field, Input, SearchInput, Select } from '../components/ui/Form'
import { TabPanel, Tabs } from '../components/ui/Tabs'
import { Modal } from '../components/ui/Overlay'
import { Bars } from '../components/charts/Charts'
import { NewsCard, SectionTitle, useDue } from '../components/shared/bits'

const levels: Level[] = ['undergraduate', 'professional', 'master', 'doctoral', 'specialist']

/* ============================ Admission ============================ */

export function AdmissionPage() {
  const { t, fmt } = useI18n()
  const [level, setLevel] = useState<'all' | Level>('all')
  const [query, setQuery] = useState('')
  const [formOpen, setFormOpen] = useState(false)
  const list = programs.filter((p) => (level === 'all' || p.level === level) && `${p.name} ${p.degree}`.toLowerCase().includes(query.toLowerCase()))

  return (
    <div className="space-y-12">
      <section className="relative -mx-4 -mt-6 overflow-hidden bg-one-hero px-6 py-14 text-white sm:mx-0 sm:mt-0 sm:rounded-3xl sm:px-10 lg:px-14 lg:py-20">
        <div aria-hidden className="one-cover-pattern absolute inset-0 opacity-30 [mask-image:linear-gradient(to_left,black,transparent)]" />
        <div aria-hidden className="absolute -right-32 -top-32 h-[420px] w-[420px] rounded-full border-[48px] border-white/[0.05]" />
        <div aria-hidden className="absolute -bottom-40 right-40 h-80 w-80 rounded-full bg-one-royal/30 blur-3xl" />
        <div className="relative grid gap-10 lg:grid-cols-[1.3fr_1fr] lg:items-center">
          <div>
            <p className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-[13px] font-medium ring-1 ring-inset ring-white/15">
              <Sparkles size={14} aria-hidden className="text-one-spark" /> {t('admission.eyebrow')}
            </p>
            <h1 className="t-display mt-5 text-white">{t('admission.heroTitle')}</h1>
            <p className="mt-4 max-w-xl text-[17px] leading-relaxed text-white/80">{t('admission.heroBody')}</p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Button variant="inverse" size="lg" icon={Send} onClick={() => setFormOpen(true)}>{t('admission.apply')}</Button>
              <a href="#programs" className="inline-flex h-12 items-center gap-2 rounded-xl px-5 text-[15px] font-medium text-white ring-1 ring-inset ring-white/30 hover:bg-white/10">
                {t('admission.explore')} <ArrowRight size={18} aria-hidden />
              </a>
            </div>
          </div>
          <dl className="grid grid-cols-2 gap-3">
            {[
              { k: t('admission.stats.programs'), v: universityStats.programs },
              { k: t('admission.stats.students'), v: fmt.compact(universityStats.students) },
              { k: t('admission.stats.alumni'), v: fmt.compact(universityStats.alumni) },
              { k: t('admission.stats.partners'), v: `${universityStats.partners}+` },
            ].map((s) => (
              <div key={s.k} className="rounded-2xl bg-white/[0.07] p-5 ring-1 ring-inset ring-white/10 backdrop-blur">
                <dd className="font-display text-[30px] font-bold leading-none">{s.v}</dd>
                <dt className="mt-2 text-[13px] text-white/70">{s.k}</dt>
              </div>
            ))}
          </dl>
        </div>
      </section>

      <section aria-labelledby="steps-h">
        <SectionTitle id="steps-h" title={t('admission.stepsTitle')} />
        <ol className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {(['register', 'documents', 'test', 'result'] as const).map((s, i) => (
            <li key={s} className="relative rounded-2xl border border-one-line bg-one-card p-5 shadow-soft">
              <span className="font-display text-[40px] font-extrabold leading-none text-one-royal/15">0{i + 1}</span>
              <h3 className="t-h3 mt-2 text-one-fg">{t(`admission.steps.${s}.title`)}</h3>
              <p className="t-small mt-1 text-one-muted">{t(`admission.steps.${s}.desc`)}</p>
            </li>
          ))}
        </ol>
      </section>

      <section id="programs" aria-labelledby="prog-h" className="scroll-mt-24">
        <SectionTitle id="prog-h" title={t('admission.programsTitle')} />
        <div className="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <FilterChips
            label={t('admission.degree')}
            value={level}
            onChange={setLevel}
            options={[{ value: 'all', label: t('common.all') }, ...levels.map((l) => ({ value: l, label: t(`admission.levels.${l}`), count: programs.filter((p) => p.level === l).length }))]}
          />
          <SearchInput value={query} onValueChange={setQuery} label={t('admission.searchPrograms')} placeholder={t('admission.searchPrograms')} className="lg:w-72" clearLabel={t('common.clearFilters')} />
        </div>
        {list.length === 0 ? (
          <Card><EmptyState icon={GraduationCap} title={t('states.emptyCourses')} body={t('states.emptyCoursesBody')} /></Card>
        ) : (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {list.map((p) => (
              <li key={p.id}>
                <ProgramCard program={p} onApply={() => setFormOpen(true)} />
              </li>
            ))}
          </ul>
        )}
      </section>

      <ApplyModal open={formOpen} onClose={() => setFormOpen(false)} />
    </div>
  )
}

function ProgramCard({ program, onApply }: { program: Program; onApply?: () => void }) {
  const { t } = useI18n()
  const faculty = facultyById(program.facultyId)
  return (
    <Card as="article" interactive className="flex h-full flex-col overflow-hidden">
      <Cover tone={faculty?.tone ?? 'navy'} icon={GraduationCap} className="h-24">
        <span className="absolute left-4 top-4">
          <Badge tone="neutral" className="bg-white/90 text-one-ink ring-0">{t(`admission.levels.${program.level}`)}</Badge>
        </span>
      </Cover>
      <div className="flex flex-1 flex-col p-5">
        <p className="text-[12.5px] text-one-subtle">{faculty?.name}</p>
        <h3 className="mt-1 text-[16.5px] font-semibold text-one-fg">{program.name}</h3>
        <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1">
          <MetaItem icon={ScrollText}>{program.degree}</MetaItem>
          <MetaItem icon={Clock}>{t('admission.duration', { count: program.years })}</MetaItem>
          <MetaItem icon={Award}>{program.accreditation}</MetaItem>
        </div>
        <p className="t-small mt-3 line-clamp-3 text-one-muted">{program.description}</p>
        <div className="mt-auto flex gap-2 pt-5">
          <Button variant="secondary" size="sm" className="flex-1" to={`/one/programs/${program.id}`}>{t('admission.explore')}</Button>
          {onApply && <Button size="sm" className="flex-1" onClick={onApply}>{t('admission.apply')}</Button>}
        </div>
      </div>
    </Card>
  )
}

function ApplyModal({ open, onClose }: { open: boolean; onClose: () => void }) {
  const { t } = useI18n()
  const toast = useToast()
  const [form, setForm] = useState({ name: '', email: '', phone: '', program: '' })
  const [errors, setErrors] = useState<Partial<Record<keyof typeof form, string>>>({})
  const set = (k: keyof typeof form) => (e: { target: { value: string } }) => setForm((f) => ({ ...f, [k]: e.target.value }))

  const submit = () => {
    const e: typeof errors = {}
    if (form.name.trim().length < 3) e.name = t('admission.errors.name')
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) e.email = t('admission.errors.email')
    if (!/^\+?[\d\s-]{9,}$/.test(form.phone)) e.phone = t('admission.errors.phone')
    if (!form.program) e.program = t('admission.errors.program')
    setErrors(e)
    if (Object.keys(e).length) return
    toast(t('admission.submittedToast'))
    setForm({ name: '', email: '', phone: '', program: '' })
    onClose()
  }

  if (!open) return null
  return (
    <ModalLite title={t('admission.formTitle')} onClose={onClose} onSubmit={submit}>
      <div className="grid gap-4 sm:grid-cols-2">
        <Field label={t('admission.fullName')} error={errors.name} required className="sm:col-span-2">
          <Input value={form.name} onChange={set('name')} autoComplete="name" />
        </Field>
        <Field label={t('common.email')} error={errors.email} required>
          <Input type="email" value={form.email} onChange={set('email')} autoComplete="email" />
        </Field>
        <Field label={t('common.phone')} error={errors.phone} required>
          <Input type="tel" value={form.phone} onChange={set('phone')} autoComplete="tel" placeholder="+62" />
        </Field>
        <Field label={t('admission.program')} error={errors.program} required className="sm:col-span-2">
          <Select value={form.program} onChange={set('program')}>
            <option value="">{t('admission.choose')}</option>
            {levels.map((l) => (
              <optgroup key={l} label={t(`admission.levels.${l}`)}>
                {programs.filter((p) => p.level === l).map((p) => <option key={p.id} value={p.id}>{p.name} ({p.degree})</option>)}
              </optgroup>
            ))}
          </Select>
        </Field>
      </div>
    </ModalLite>
  )
}

function ModalLite({ title, onClose, onSubmit, children }: { title: string; onClose: () => void; onSubmit: () => void; children: ReactNode }) {
  const { t } = useI18n()
  return (
    <Modal
      open
      onClose={onClose}
      title={title}
      footer={
        <>
          <Button variant="secondary" onClick={onClose}>{t('common.cancel')}</Button>
          <Button icon={Send} onClick={onSubmit}>{t('common.submit')}</Button>
        </>
      }
    >
      <form noValidate onSubmit={(e) => { e.preventDefault(); onSubmit() }}>
        {children}
        <button type="submit" className="hidden" tabIndex={-1} aria-hidden />
      </form>
    </Modal>
  )
}

/* ============================ Faculties ============================ */

export function FacultiesPage() {
  const { t, fmt } = useI18n()
  const q = useMockData(() => faculties, [] as Faculty[])
  return (
    <>
      <PageHeader title={t('faculties.title')} description={t('faculties.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('faculties.title') }]} />
      <Async query={q} skeleton={<CardGridSkeleton count={8} />} isEmpty={(d) => d.length === 0} empty={<Card><EmptyState icon={Landmark} title={t('states.emptyGeneric')} /></Card>}>
        {(list) => (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            {list.map((f) => (
              <li key={f.id}>
                <Card as="article" interactive className="group relative flex h-full flex-col overflow-hidden">
                  <Cover tone={f.tone} icon={Landmark} className="aspect-[16/10]">
                    <span className="absolute bottom-3 left-4 font-display text-[28px] font-extrabold tracking-tight text-white/90">{f.short}</span>
                  </Cover>
                  <div className="flex flex-1 flex-col p-5">
                    <h2 className="text-[16px] font-semibold leading-snug text-one-fg">
                      <Link to={`/one/faculties/${f.id}`} className="after:absolute after:inset-0 group-hover:text-one-royal">{f.name}</Link>
                    </h2>
                    <p className="t-small mt-2 line-clamp-3 text-one-muted">{f.description}</p>
                    <div className="mt-auto flex items-center justify-between pt-4 text-[13px]">
                      <span className="font-medium text-one-royal">{t('faculties.programsCount', { count: programs.filter((p) => p.facultyId === f.id).length })}</span>
                      <span className="text-one-subtle">{fmt.compact(f.students)} {t('faculties.students').toLowerCase()}</span>
                    </div>
                  </div>
                </Card>
              </li>
            ))}
          </ul>
        )}
      </Async>
    </>
  )
}

type FTab = 'overview' | 'programs' | 'departments' | 'facilities' | 'members' | 'news' | 'events'

export function FacultyDetailPage() {
  const { id = '' } = useParams()
  const { t, fmt } = useI18n()
  const [tab, setTab] = useState<FTab>('overview')
  const f = facultyById(id)
  if (!f) return <Card><EmptyState icon={Landmark} title={t('faculties.notFound')} action={<Button to="/one/faculties" variant="secondary">{t('common.back')}</Button>} /></Card>
  const facultyPrograms = programs.filter((p) => p.facultyId === f.id)
  const members = lecturers.filter((l) => l.facultyId === f.id)

  return (
    <>
      <PageHeader title={f.name} crumbs={[{ label: t('faculties.title'), to: '/one/faculties' }, { label: f.short }]} />
      <Card className="mb-6 overflow-hidden">
        <Cover tone={f.tone} icon={Landmark} className="h-36 sm:h-44" />
        <dl className="grid grid-cols-2 gap-4 p-5 sm:p-6 lg:grid-cols-5">
          {[
            { k: t('faculties.dean'), v: f.dean },
            { k: t('faculties.established'), v: f.established },
            { k: t('faculties.accreditation'), v: f.accreditation },
            { k: t('faculties.students'), v: fmt.number(f.students) },
            { k: t('faculties.lecturers'), v: f.lecturers },
          ].map((m) => (
            <div key={m.k} className="min-w-0">
              <dt className="text-[12px] text-one-subtle">{m.k}</dt>
              <dd className="mt-0.5 text-[14.5px] font-semibold text-one-fg">{m.v}</dd>
            </div>
          ))}
        </dl>
      </Card>
      <Tabs
        id="fac"
        label={f.name}
        value={tab}
        onChange={setTab}
        className="mb-6"
        items={(['overview', 'programs', 'departments', 'facilities', 'members', 'news', 'events'] as FTab[]).map((v) => ({ value: v, label: t(`faculties.tabs.${v}`), count: v === 'programs' ? facultyPrograms.length : undefined }))}
      />
      <TabPanel id="fac" value={tab} key={tab}>
        {tab === 'overview' && (
          <Card padded className="max-w-3xl">
            <Quote size={28} aria-hidden className="text-one-royal/30" />
            <p className="mt-2 text-[17px] leading-relaxed text-one-fg">{f.description}</p>
            <div className="mt-5 flex items-center gap-3">
              <Avatar name={f.dean} />
              <div>
                <p className="font-semibold text-one-fg">{f.dean}</p>
                <p className="text-[13px] text-one-subtle">{t('faculties.dean')}</p>
              </div>
            </div>
          </Card>
        )}
        {tab === 'programs' && (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {facultyPrograms.map((p) => <li key={p.id}><ProgramCard program={p} /></li>)}
          </ul>
        )}
        {tab === 'departments' && <SimpleGrid items={f.departments} icon={Building2} />}
        {tab === 'facilities' && <SimpleGrid items={f.facilities} icon={FlaskConical} />}
        {tab === 'members' &&
          (members.length ? (
            <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              {members.map((l) => (
                <li key={l.id}>
                  <Card className="flex items-center gap-4 p-5">
                    <Avatar name={l.name} size="lg" />
                    <div className="min-w-0">
                      <p className="font-semibold text-one-fg">{l.name}</p>
                      <p className="text-[13px] text-one-subtle">{l.title}</p>
                      <p className="t-small mt-1 truncate text-one-muted">{l.expertise}</p>
                    </div>
                  </Card>
                </li>
              ))}
            </ul>
          ) : (
            <Card><EmptyState icon={Users} title={t('states.emptyGeneric')} /></Card>
          ))}
        {tab === 'news' && (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {news.slice(0, 3).map((n) => <li key={n.id}><NewsCard article={n} /></li>)}
          </ul>
        )}
        {tab === 'events' && (
          <Card>
            <ul className="divide-y divide-one-line">
              {events.slice(0, 4).map((e) => (
                <li key={e.id} className="flex items-center justify-between gap-4 p-4 sm:px-6">
                  <div className="min-w-0">
                    <p className="font-medium text-one-fg">{e.title}</p>
                    <MetaItem icon={CalendarDays}>{fmt.date(e.date, { weekday: 'short', day: 'numeric', month: 'short' })} · {e.location}</MetaItem>
                  </div>
                  <Button size="sm" variant="secondary" to={`/one/campus/events?e=${e.id}`}>{t('events.viewEvent')}</Button>
                </li>
              ))}
            </ul>
          </Card>
        )}
      </TabPanel>
    </>
  )
}

function SimpleGrid({ items, icon }: { items: string[]; icon: typeof Building2 }) {
  return (
    <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((d) => (
        <li key={d}>
          <Card className="flex items-center gap-3 p-4">
            <IconTile icon={icon} tone="royal" />
            <span className="font-medium text-one-fg">{d}</span>
          </Card>
        </li>
      ))}
    </ul>
  )
}

type PTab = 'overview' | 'curriculum' | 'careers' | 'facilities' | 'lecturers' | 'requirements'

export function ProgramDetailPage() {
  const { id = '' } = useParams()
  const { t } = useI18n()
  const [tab, setTab] = useState<PTab>('overview')
  const p = programById(id)
  if (!p) return <Card><EmptyState icon={GraduationCap} title={t('program.notFound')} action={<Button to="/one/faculties" variant="secondary">{t('common.back')}</Button>} /></Card>
  const f = facultyById(p.facultyId)!
  const curriculum = p.id === 'information-systems' ? isCurriculum : isCurriculum.slice(0, Math.min(8, p.years * 2)).map((s) => ({ ...s, courses: s.courses.map((_, i) => `${p.name} ${s.semester}0${i + 1}`) }))
  const progLecturers = (p.lecturerIds.length ? p.lecturerIds : lecturers.filter((l) => l.facultyId === p.facultyId).map((l) => l.id)).map((x) => lecturerById(x)!).filter(Boolean)

  return (
    <>
      <PageHeader
        eyebrow={`${t(`admission.levels.${p.level}`)} · ${p.degree}`}
        title={p.name}
        description={p.description}
        crumbs={[{ label: t('faculties.title'), to: '/one/faculties' }, { label: f.short, to: `/one/faculties/${f.id}` }, { label: p.name }]}
        actions={<Button icon={Send} to="/one/admission">{t('admission.apply')}</Button>}
      />
      <div className="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <Stat label={t('admission.degree')} value={p.degree} icon={ScrollText} tone="royal" />
        <Stat label={t('exams.duration')} value={t('admission.duration', { count: p.years })} icon={Clock} tone="teal" />
        <Stat label={t('program.totalCredits')} value={p.credits} icon={BookOpen} tone="gold" />
        <Stat label={t('faculties.accreditation')} value={p.accreditation} icon={Award} tone="ok" />
      </div>
      <Tabs id="prog" label={p.name} value={tab} onChange={setTab} className="mb-6" items={(['overview', 'curriculum', 'careers', 'facilities', 'lecturers', 'requirements'] as PTab[]).map((v) => ({ value: v, label: t(`program.tabs.${v}`) }))} />
      <TabPanel id="prog" value={tab} key={tab}>
        {tab === 'overview' && (
          <Card padded className="max-w-3xl">
            <p className="text-[16px] leading-relaxed text-one-fg">{p.description}</p>
            <p className="t-body mt-3 text-one-muted">{f.description}</p>
          </Card>
        )}
        {tab === 'curriculum' && (
          <ol className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {curriculum.map((s) => (
              <li key={s.semester}>
                <Card className="h-full p-5">
                  <p className="t-caption text-one-royal">{t('program.semesterN', { n: s.semester })}</p>
                  <ul className="mt-3 space-y-2">
                    {s.courses.map((c) => (
                      <li key={c} className="flex gap-2 text-[14px] text-one-fg">
                        <span aria-hidden className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-one-line2" /> {c}
                      </li>
                    ))}
                  </ul>
                </Card>
              </li>
            ))}
          </ol>
        )}
        {tab === 'careers' && <SimpleGrid items={p.careers} icon={Briefcase} />}
        {tab === 'facilities' && <SimpleGrid items={f.facilities} icon={FlaskConical} />}
        {tab === 'lecturers' && (
          <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            {progLecturers.map((l) => (
              <li key={l.id}>
                <Card className="flex items-center gap-4 p-5">
                  <Avatar name={l.name} size="lg" />
                  <div className="min-w-0">
                    <p className="font-semibold text-one-fg">{l.name}</p>
                    <p className="t-small truncate text-one-muted">{l.expertise}</p>
                  </div>
                </Card>
              </li>
            ))}
          </ul>
        )}
        {tab === 'requirements' && (
          <Card padded className="max-w-2xl">
            <ul className="space-y-3">
              {p.requirements.map((r) => (
                <li key={r} className="flex items-start gap-3 text-[15px] text-one-fg">
                  <CircleCheck size={18} aria-hidden className="mt-0.5 shrink-0 text-one-ok" /> {r}
                </li>
              ))}
            </ul>
            <Button className="mt-6" icon={Send} to="/one/admission">{t('admission.apply')}</Button>
          </Card>
        )}
      </TabPanel>
    </>
  )
}

/* ============================ Research ============================ */

export function ResearchPage() {
  const { t, fmt } = useI18n()
  const [asTable, setAsTable] = useState(false)
  const q = useMockData(() => researchStats, null as typeof researchStats | null)
  return (
    <>
      <PageHeader title={t('research.title')} description={t('research.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.research') }]} />
      <Async query={q} skeleton={<StatsSkeleton />} isEmpty={(d) => !d} empty={<Card><EmptyState icon={FlaskConical} title={t('states.emptyGeneric')} /></Card>}>
        {(s) => (
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
              <Stat label={t('research.publications')} value={fmt.number(s!.publications)} icon={FileText} tone="royal" hint={`+${researchTrend[researchTrend.length - 1].value} · ${researchTrend[researchTrend.length - 1].year}`} />
              <Stat label={t('research.projects')} value={s!.projects} icon={FlaskConical} tone="teal" />
              <Stat label={t('research.citations')} value={fmt.compact(s!.citations)} icon={Quote} tone="gold" />
              <Stat label={t('research.researchers')} value={s!.researchers} icon={Users} tone="ok" />
            </div>

            <div className="grid gap-6 lg:grid-cols-5">
              <Card as="section" aria-labelledby="rc-h" className="lg:col-span-3">
                <CardHeader
                  id="rc-h"
                  title={t('research.categories')}
                  description={t('research.categoriesDesc')}
                  action={<IconButton icon={asTable ? ChartColumn : Table2} label={asTable ? t('grades.chartView') : t('grades.tableView')} size="sm" variant="secondary" onClick={() => setAsTable((v) => !v)} />}
                />
                <CardBody>
                  {asTable ? (
                    <table className="w-full text-[14px]">
                      <caption className="sr-only">{t('research.categories')}</caption>
                      <tbody>{researchCategories.map((r) => <tr key={r.field} className="border-b border-one-line last:border-0"><th scope="row" className="py-2.5 text-left font-normal">{r.field}</th><td className="py-2.5 text-right font-medium tabular">{r.value}</td></tr>)}</tbody>
                    </table>
                  ) : (
                    <Bars data={researchCategories} x="field" y="value" layout="rows" seriesLabel={t('research.publications')} height={280} categoryWidth={160} ariaLabel={researchCategories.map((r) => `${r.field} ${r.value}`).join(', ')} />
                  )}
                </CardBody>
              </Card>
              <Card as="section" aria-labelledby="ap-h" className="lg:col-span-2">
                <CardHeader id="ap-h" title={t('research.activeProjects')} />
                <CardBody>
                  <ul className="space-y-4">
                    {researchProjects.map((p) => (
                      <li key={p.id}>
                        <p className="text-[14px] font-medium text-one-fg">{p.title}</p>
                        <p className="text-[12.5px] text-one-subtle">{t('research.lead')}: {p.lead} · {t('research.funding')}: {p.funding}</p>
                        <div className="mt-2 flex items-center gap-3">
                          <Progress value={p.progress} label={`${t('research.progress')} ${p.progress}%`} className="flex-1" />
                          <span className="w-9 text-right text-[12.5px] font-medium tabular text-one-muted">{p.progress}%</span>
                        </div>
                      </li>
                    ))}
                  </ul>
                </CardBody>
              </Card>
            </div>

            <section aria-labelledby="cen-h">
              <SectionTitle id="cen-h" title={t('research.centers')} />
              <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                {researchCenters.map((c) => (
                  <li key={c.id}>
                    <Card className="h-full overflow-hidden">
                      <Cover tone={c.tone} icon={FlaskConical} className="h-20" />
                      <div className="p-4">
                        <p className="font-semibold text-one-fg">{c.name}</p>
                        <p className="t-small mt-1 text-one-muted">{c.focus}</p>
                      </div>
                    </Card>
                  </li>
                ))}
              </ul>
            </section>

            <div className="grid gap-6 lg:grid-cols-3">
              <Card as="section" aria-labelledby="pub-h" className="lg:col-span-2">
                <CardHeader id="pub-h" title={t('research.recentPublications')} />
                <CardBody>
                  <ul className="divide-y divide-one-line">
                    {publications.map((p) => (
                      <li key={p.id} className="py-3.5 first:pt-0">
                        <p className="text-[14.5px] font-medium leading-snug text-one-fg">{p.title}</p>
                        <p className="mt-1 text-[12.5px] text-one-subtle">{p.authors} · <span className="italic">{p.venue}</span> · {p.year}</p>
                        <Badge tone="gold" className="mt-2">{t('research.citations')}: {p.citations}</Badge>
                      </li>
                    ))}
                  </ul>
                </CardBody>
              </Card>
              <div className="space-y-4">
                {[
                  { icon: Lightbulb, title: t('research.innovation'), k: t('research.patents'), v: 46, tone: 'gold' as const },
                  { icon: Rocket, title: t('research.innovation'), k: t('research.startups'), v: 23, tone: 'royal' as const },
                  { icon: Handshake, title: t('research.collaboration'), k: t('research.industryPartners'), v: 118, tone: 'teal' as const },
                ].map((x) => (
                  <Card key={x.k} className="flex items-center gap-4 p-5">
                    <IconTile icon={x.icon} tone={x.tone} size="lg" />
                    <div>
                      <p className="t-caption text-one-subtle">{x.title}</p>
                      <p className="font-display text-[24px] font-bold text-one-fg">{x.v}</p>
                      <p className="text-[13px] text-one-muted">{x.k}</p>
                    </div>
                  </Card>
                ))}
              </div>
            </div>
          </div>
        )}
      </Async>
    </>
  )
}

/* ============================ International ============================ */

const regions: Region[] = ['asia', 'europe', 'oceania', 'americas']

export function InternationalPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const [region, setRegion] = useState<'all' | Region>('all')
  const countries = new Set(partners.map((p) => p.country)).size
  const list = partners.filter((p) => region === 'all' || p.region === region)

  return (
    <>
      <PageHeader title={t('international.title')} description={t('international.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('nav.international') }]} actions={<Button icon={Plane} onClick={() => toast(t('international.exchangeToast'))}>{t('international.applyExchange')}</Button>} />

      <section className="relative mb-8 overflow-hidden rounded-3xl bg-one-hero p-6 text-white shadow-lift sm:p-8">
        <WorldDots />
        <div className="relative grid gap-6 sm:grid-cols-3">
          {[
            { k: t('international.partners'), v: t('international.partnerCount', { count: universityStats.partners }) },
            { k: t('international.region'), v: t('international.countries', { count: countries * 3 }) },
            { k: t('international.exchange'), v: '42' },
          ].map((s) => (
            <div key={s.k}>
              <p className="t-caption text-white/60">{s.k}</p>
              <p className="mt-1 font-display text-[26px] font-bold">{s.v}</p>
            </div>
          ))}
        </div>
      </section>

      <section aria-labelledby="ip-h" className="mb-10">
        <SectionTitle id="ip-h" title={t('international.programs')} />
        <ul className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          {intlPrograms.map((p) => (
            <li key={p.id}>
              <Card as="article" className="h-full overflow-hidden">
                <Cover tone={p.tone} icon={Earth} className="h-24" />
                <div className="p-5">
                  <h3 className="font-semibold text-one-fg">{p.name}</h3>
                  <p className="t-small mt-1 text-one-muted">{p.desc}</p>
                  <MetaItem icon={Clock}>{t('international.duration')}: {p.duration}</MetaItem>
                </div>
              </Card>
            </li>
          ))}
        </ul>
      </section>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card as="section" aria-labelledby="pu-h" className="lg:col-span-2">
          <CardHeader id="pu-h" title={t('international.partners')} />
          <div className="px-5 pt-4 sm:px-6">
            <FilterChips label={t('international.region')} value={region} onChange={setRegion} options={[{ value: 'all', label: t('common.all') }, ...regions.map((r) => ({ value: r, label: t(`international.regions.${r}`), count: partners.filter((p) => p.region === r).length }))]} />
          </div>
          <CardBody>
            <ul className="grid gap-2 sm:grid-cols-2">
              {list.map((p) => (
                <li key={p.id} className="flex items-center gap-3 rounded-xl border border-one-line p-3">
                  <Avatar name={p.name} size="sm" />
                  <div className="min-w-0">
                    <p className="truncate text-[14px] font-medium text-one-fg">{p.name}</p>
                    <p className="text-[12.5px] text-one-subtle">{p.country} · {t(`international.regions.${p.region}`)}</p>
                  </div>
                </li>
              ))}
            </ul>
          </CardBody>
        </Card>
        <div className="space-y-6">
          <Card as="section" aria-labelledby="ie-h">
            <CardHeader id="ie-h" title={t('international.events')} />
            <CardBody>
              <ul className="space-y-3">
                {intlEvents.map((e) => (
                  <li key={e.id} className="flex gap-3">
                    <div className="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl bg-one-royal/10 text-one-royal">
                      <span className="text-[10px] font-semibold uppercase">{fmt.date(e.date, { month: 'short' })}</span>
                      <span className="font-display text-[17px] font-bold leading-none">{new Date(e.date).getDate()}</span>
                    </div>
                    <div className="min-w-0">
                      <p className="text-[14px] font-medium text-one-fg">{e.title}</p>
                      <MetaItem icon={MapPin}>{e.location}</MetaItem>
                    </div>
                  </li>
                ))}
              </ul>
            </CardBody>
          </Card>
          <Card className="p-5">
            <IconTile icon={Award} tone="gold" />
            <h3 className="t-h3 mt-3 text-one-fg">{t('international.scholarships')}</h3>
            <p className="t-small mt-1 text-one-muted">{t('international.opportunities')}</p>
            <Button variant="secondary" className="mt-4" block to="/one/scholarships">{t('international.learnMore')}</Button>
          </Card>
        </div>
      </div>
    </>
  )
}

/** Decorative dotted globe-grid (optional "world map" element) — pure SVG, no data encoded. */
function WorldDots() {
  const dots: Array<[number, number]> = []
  for (let y = 0; y < 14; y++) for (let x = 0; x < 48; x++) if (Math.sin(x * 0.35 + y * 0.6) + Math.cos(y * 0.5 - x * 0.12) > 0.4) dots.push([x, y])
  return (
    <svg aria-hidden className="absolute inset-0 h-full w-full opacity-25" viewBox="0 0 480 140" preserveAspectRatio="xMidYMid slice">
      {dots.map(([x, y]) => <circle key={`${x}-${y}`} cx={x * 10 + 5} cy={y * 10 + 5} r={1.6} fill="white" />)}
      {[[120, 60], [250, 40], [380, 90], [330, 50]].map(([x, y]) => <circle key={`${x}${y}`} cx={x} cy={y} r={4} className="fill-one-spark" />)}
    </svg>
  )
}

/* ============================ Scholarships ============================ */

const schTags: ScholarshipTag[] = ['undergraduate', 'master', 'doctoral', 'international', 'merit', 'financialAid']

export function ScholarshipsPage() {
  const { t, fmt } = useI18n()
  const toast = useToast()
  const due = useDue()
  const applied = useApp((s) => s.applied)
  const add = useApp((s) => s.add)
  const [tag, setTag] = useState<'all' | ScholarshipTag>('all')
  const [query, setQuery] = useState('')
  const q = useMockData(() => scholarships, [] as Scholarship[])
  const list = q.data.filter((s) => (tag === 'all' || s.tags.includes(tag)) && `${s.name} ${s.provider}`.toLowerCase().includes(query.toLowerCase()))

  return (
    <>
      <PageHeader title={t('scholarships.title')} description={t('scholarships.subtitle')} crumbs={[{ label: t('nav.dashboard'), to: '/one' }, { label: t('scholarships.title') }]} />
      <div className="mb-6 space-y-3">
        <SearchInput value={query} onValueChange={setQuery} label={t('scholarships.searchPlaceholder')} placeholder={t('scholarships.searchPlaceholder')} className="max-w-md" clearLabel={t('common.clearFilters')} />
        <FilterChips label={t('common.filter')} value={tag} onChange={setTag} options={[{ value: 'all', label: t('common.all') }, ...schTags.map((s) => ({ value: s, label: t(`scholarships.filters.${s}`) }))]} />
      </div>
      <Async
        query={q}
        skeleton={<CardGridSkeleton media={false} />}
        isEmpty={() => list.length === 0}
        empty={<Card><EmptyState icon={Award} title={t('states.emptyScholarships')} body={t('states.emptyScholarshipsBody')} action={<Button variant="secondary" onClick={() => { setTag('all'); setQuery('') }}>{t('common.clearFilters')}</Button>} /></Card>}
      >
        {() => (
          <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {list.map((s) => {
              const days = daysUntil(s.deadline)
              const isApplied = applied.includes(s.id)
              const status = isApplied ? 'submitted' : days < 0 ? 'closed' : days <= 7 ? 'closingSoon' : 'open'
              const d = due(s.deadline)
              return (
                <li key={s.id}>
                  <Card as="article" className="flex h-full flex-col p-5">
                    <div className="flex items-start justify-between gap-3">
                      <Cover tone={s.tone} icon={Award} className="h-12 w-12 shrink-0 rounded-xl" />
                      <Badge tone={status === 'open' ? 'ok' : status === 'closingSoon' ? 'warn' : status === 'submitted' ? 'teal' : 'neutral'} dot>
                        {t(status === 'submitted' ? 'common.applied' : `status.${status}`)}
                      </Badge>
                    </div>
                    <h2 className="mt-4 text-[16px] font-semibold text-one-fg">{s.name}</h2>
                    <p className="text-[13px] text-one-subtle">{t('scholarships.provider')}: {s.provider}</p>
                    <dl className="mt-4 grid grid-cols-2 gap-3 rounded-xl bg-one-sunken/60 p-3 text-[13px]">
                      <div>
                        <dt className="text-one-subtle">{t('scholarships.amount')}</dt>
                        <dd className="font-semibold text-one-fg">{s.amount}</dd>
                      </div>
                      <div>
                        <dt className="text-one-subtle">{t('common.deadline')}</dt>
                        <dd className={cn('font-semibold', days < 0 ? 'text-one-subtle' : 'text-one-fg')}>{fmt.date(s.deadline, { day: 'numeric', month: 'short' })}{days >= 0 && <span className="ml-1 font-normal text-one-subtle">· {d.label}</span>}</dd>
                      </div>
                    </dl>
                    <p className="t-small mt-3 text-one-muted"><span className="font-medium text-one-fg">{t('scholarships.eligibility')}:</span> {s.eligibility}</p>
                    <ul className="mt-3 flex flex-wrap gap-1.5">
                      {s.tags.map((tg) => <li key={tg}><Badge>{t(`scholarships.filters.${tg}`)}</Badge></li>)}
                    </ul>
                    <div className="mt-auto pt-5">
                      <Button
                        block
                        disabled={status === 'closed' || isApplied}
                        variant={isApplied ? 'secondary' : 'primary'}
                        icon={isApplied ? UserCheck : Send}
                        onClick={() => {
                          add('applied', s.id)
                          toast(t('scholarships.appliedToast', { name: s.name }))
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
    </>
  )
}

