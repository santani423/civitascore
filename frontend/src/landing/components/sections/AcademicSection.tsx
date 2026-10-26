import { Link } from 'react-router-dom'
import { ArrowRight, CalendarRange, Check, FileBadge2, NotebookPen, Presentation, type LucideIcon } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { useAppEntry } from '../../hooks/useAppEntry'
import { SECTION_IDS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'

type StageKey = keyof Messages['academic']['stages']
type RoleKey = keyof Messages['academic']['roles']

/** Four stages of the academic cycle; every point is a working feature (see data/catalog.ts). */
const STAGES: Array<{ key: StageKey; icon: LucideIcon; roles: RoleKey[] }> = [
  { key: 'planning', icon: CalendarRange, roles: ['students', 'academicOffice'] },
  { key: 'lectures', icon: Presentation, roles: ['lecturers', 'students'] },
  { key: 'assessment', icon: NotebookPen, roles: ['lecturers', 'students'] },
  { key: 'results', icon: FileBadge2, roles: ['students', 'academicOffice'] },
]

/** Academic section, laid out like the reference's "Program Studi" block: copy on the left, cards on the right. */
export function AcademicSection() {
  const { m } = useLandingI18n()
  const entry = useAppEntry()
  const a = m.academic

  return (
    <section id={SECTION_IDS.academic} aria-labelledby="academic-title" className="lp-section lp-warm">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-10">
        <Reveal className="lg:col-span-4 lg:pt-2">
          <p className="lp-eyebrow text-lp-brand-text">{a.eyebrow}</p>
          <h2 id="academic-title" className="lp-h2 mt-3 text-lp-fg">
            {a.title}
          </h2>
          <p className="lp-body mt-4 text-lp-muted">{a.description}</p>
          <Link to={entry.to} className={buttonClass('primary', 'md', 'mt-8')}>
            {entry.authenticated ? m.nav.openDashboard : a.cta}
            <ArrowRight size={16} aria-hidden />
          </Link>
          <p className="mt-4 max-w-xs text-[13px] leading-relaxed text-lp-subtle">{a.signInNote}</p>
        </Reveal>

        <ol className="grid gap-5 sm:grid-cols-2 lg:col-span-8">
          {STAGES.map(({ key, icon: Icon, roles }, i) => {
            const stage = a.stages[key]
            return (
              <li key={key} className="flex">
                <Reveal delay={i * 70} className="flex w-full">
                  <article className="flex w-full flex-col overflow-hidden rounded-lp border border-lp-line bg-lp-surface shadow-lp-sm">
                    <div className="relative flex items-center justify-between overflow-hidden bg-gradient-to-br from-lp-brand-soft to-lp-surface px-6 py-5">
                      <span aria-hidden className="absolute -right-8 -top-12 h-32 w-32 rounded-full border-[14px] border-lp-accent/10" />
                      <span className="flex h-12 w-12 items-center justify-center rounded-lp bg-lp-brand text-lp-onbrand shadow-lp-sm">
                        <Icon size={22} aria-hidden />
                      </span>
                      <span className="lp-tabular relative font-display text-[2.25rem] font-extrabold leading-none text-lp-accent/25" aria-hidden>
                        {String(i + 1).padStart(2, '0')}
                      </span>
                    </div>
                    <div className="flex flex-1 flex-col px-6 pb-6 pt-4">
                      <p className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{fill(a.stepLabel, { n: i + 1 })}</p>
                      <h3 className="lp-h3 mt-1 text-lp-fg">{stage.title}</h3>
                      <ul className="mt-4 flex-1 space-y-2.5">
                        {(['a', 'b', 'c'] as const).map((p) => (
                          <li key={p} className="flex items-start gap-2.5 text-[14px] leading-snug text-lp-muted">
                            <Check size={16} aria-hidden className="mt-px shrink-0 text-lp-brand-text" />
                            {stage.points[p]}
                          </li>
                        ))}
                      </ul>
                      <p className="mt-5 flex flex-wrap items-center gap-1.5 border-t border-lp-line pt-4 text-[12px]">
                        <span className="font-medium text-lp-subtle">{a.forLabel}:</span>
                        {roles.map((r) => (
                          <span key={r} className="rounded-full bg-lp-sunken px-2.5 py-0.5 font-medium text-lp-muted">
                            {a.roles[r]}
                          </span>
                        ))}
                      </p>
                    </div>
                  </article>
                </Reveal>
              </li>
            )
          })}
        </ol>
      </div>
    </section>
  )
}
