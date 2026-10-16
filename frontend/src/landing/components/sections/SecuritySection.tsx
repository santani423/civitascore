import { Database, Eye, GitBranch, Info, KeyRound, Minus, Pencil, ScrollText, ShieldCheck, UserRound } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader } from '../ui/Primitives'

type Sec = Messages['security']
type Level = keyof Sec['levels']
type Role = keyof Sec['roles']
type Resource = keyof Sec['resources']

const PRINCIPLES: Array<{ key: keyof Sec['principles']; icon: typeof Eye }> = [
  { key: 'rbac', icon: KeyRound },
  { key: 'consistency', icon: Database },
  { key: 'privacy', icon: UserRound },
  { key: 'audit', icon: ScrollText },
  { key: 'structured', icon: GitBranch },
]

const RESOURCES: Resource[] = ['grades', 'krs', 'reports', 'audit']

/** Example only — real permissions are configured per institution. */
const MATRIX: Record<Role, Record<Resource, Level>> = {
  rector: { grades: 'view', krs: 'view', reports: 'view', audit: 'view' },
  dean: { grades: 'view', krs: 'view', reports: 'view', audit: 'none' },
  lecturer: { grades: 'manage', krs: 'view', reports: 'none', audit: 'none' },
  academic: { grades: 'view', krs: 'manage', reports: 'view', audit: 'none' },
  student: { grades: 'own', krs: 'own', reports: 'none', audit: 'none' },
}

const levelStyle: Record<Level, { icon: typeof Eye; className: string }> = {
  manage: { icon: Pencil, className: 'bg-lp-band-fg text-lp-band' },
  view: { icon: Eye, className: 'border border-lp-band-fg/30 text-lp-band-fg' },
  own: { icon: UserRound, className: 'border border-dashed border-lp-gold/60 text-lp-gold' },
  none: { icon: Minus, className: 'text-lp-band-muted/60' },
}

const AUDIT: Array<{ time: string; key: keyof Sec['auditEntries'] }> = [
  { time: '10:42', key: 'krs' },
  { time: '10:31', key: 'grades' },
  { time: '09:58', key: 'roles' },
]

export function SecuritySection() {
  const { m } = useLandingI18n()
  const s = m.security
  const levels = Object.keys(levelStyle) as Level[]

  return (
    <section id={SECTION_IDS.governance} aria-labelledby="governance-title" className="lp-section lp-on-band relative overflow-hidden bg-lp-band text-lp-band-fg">
      <div aria-hidden className="lp-band-glow pointer-events-none absolute inset-0" />
      <div className="lp-container relative grid gap-14 lg:grid-cols-12 lg:gap-12">
        <div className="lg:col-span-5">
          <SectionHeader id="governance-title" eyebrow={s.eyebrow} title={s.title} description={s.description} tone="band" />
          <ul className="mt-10 space-y-6">
            {PRINCIPLES.map(({ key, icon: Icon }, i) => (
              <li key={key}>
                <Reveal delay={i * 60} className="flex gap-4">
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lp border border-lp-band-line bg-lp-band-elev text-lp-gold">
                    <Icon size={18} aria-hidden />
                  </span>
                  <div>
                    <h3 className="lp-h4 text-lp-band-fg">{s.principles[key].title}</h3>
                    <p className="lp-small mt-1 text-lp-band-muted">{s.principles[key].body}</p>
                  </div>
                </Reveal>
              </li>
            ))}
          </ul>
        </div>

        <div className="space-y-5 lg:col-span-7 lg:pt-4">
          <Reveal className="rounded-lp-xl border border-lp-band-line bg-lp-band-elev p-5 shadow-lp-xl sm:p-6">
            <div className="mb-4 flex items-center justify-between gap-3">
              <p className="flex items-center gap-2 text-[14px] font-semibold text-lp-band-fg">
                <ShieldCheck size={17} aria-hidden className="text-lp-gold" />
                {s.matrixTitle}
              </p>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full min-w-[300px] border-separate border-spacing-0 text-left">
                <thead>
                  <tr>
                    <th scope="col" className="border-b border-lp-band-line pb-2.5 pr-2 text-[11px] font-semibold uppercase tracking-wider text-lp-band-muted">
                      {s.matrixRole}
                    </th>
                    {RESOURCES.map((r) => (
                      <th key={r} scope="col" className="border-b border-lp-band-line px-1 pb-2.5 text-center text-[11px] font-semibold uppercase tracking-wider text-lp-band-muted">
                        {s.resources[r]}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {(Object.keys(MATRIX) as Role[]).map((role) => (
                    <tr key={role}>
                      <th scope="row" className="border-b border-lp-band-line/60 py-2.5 pr-2 text-[13px] font-medium text-lp-band-fg">
                        {s.roles[role]}
                      </th>
                      {RESOURCES.map((r) => {
                        const level = MATRIX[role][r]
                        const { icon: Icon, className } = levelStyle[level]
                        return (
                          <td key={r} className="border-b border-lp-band-line/60 px-1 py-2.5 text-center">
                            <span title={s.levels[level]} className={cn('inline-flex h-7 w-7 items-center justify-center rounded-full', className)}>
                              <Icon size={13} aria-hidden />
                              <span className="sr-only">{s.levels[level]}</span>
                            </span>
                          </td>
                        )
                      })}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-2" aria-hidden>
              {levels.map((level) => {
                const { icon: Icon, className } = levelStyle[level]
                return (
                  <li key={level} className="flex items-center gap-1.5 text-[11.5px] text-lp-band-muted">
                    <span className={cn('inline-flex h-5 w-5 items-center justify-center rounded-full', className)}>
                      <Icon size={10} />
                    </span>
                    {s.levels[level]}
                  </li>
                )
              })}
            </ul>
          </Reveal>

          <Reveal delay={100} className="rounded-lp-xl border border-lp-band-line bg-lp-band-elev p-5 sm:p-6">
            <p className="mb-3 flex items-center gap-2 text-[14px] font-semibold text-lp-band-fg">
              <ScrollText size={17} aria-hidden className="text-lp-gold" />
              {s.auditTitle}
            </p>
            <ol className="relative space-y-3 border-l border-lp-band-line pl-5">
              {AUDIT.map((entry) => (
                <li key={entry.key} className="relative">
                  <span aria-hidden className="absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-lp-band-elev bg-lp-gold" />
                  <p className="text-[13px] text-lp-band-fg">{s.auditEntries[entry.key]}</p>
                  <p className="lp-tabular mt-0.5 text-[11.5px] text-lp-band-muted">{entry.time}</p>
                </li>
              ))}
            </ol>
          </Reveal>

          <Reveal delay={160}>
            <p className="flex items-start gap-2.5 text-[13px] leading-relaxed text-lp-band-muted">
              <Info size={16} aria-hidden className="mt-0.5 shrink-0" />
              {s.note} {m.common.illustrativeNote}
            </p>
          </Reveal>
        </div>
      </div>
    </section>
  )
}
