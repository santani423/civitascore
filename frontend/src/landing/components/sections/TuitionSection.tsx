import { useState } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, BadgeCheck, CalendarRange, GraduationCap, Info, MessageSquare, ScrollText, Stethoscope, type LucideIcon } from 'lucide-react'
import { PMB } from '@/pmb/routes'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { TUITION, type TuitionNoteKey, type TuitionProgram, type TuitionProgramKey, type TuitionStudy } from '../../data/tuition'
import { SECTION_IDS } from '../../paths'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { TabList, TabPanel } from '../ui/Tabs'
import { Reveal, SectionHeader } from '../ui/Primitives'

const TABS_ID = 'lp-tuition'

const PROGRAM_ICONS: Record<TuitionProgramKey, LucideIcon> = {
  profession: BadgeCheck,
  master: GraduationCap,
  doctoral: ScrollText,
  specialist: Stethoscope,
}

/** Footnote number in source order; the marker is that many asterisks (*, **, ***, ****), as in the printed table. */
const noteNumber = (note: TuitionNoteKey) => TUITION.notes.indexOf(note) + 1

/** Default study period: the first one without a condition attached (e.g. Magister → 4 semesters). */
const defaultDuration = (program: TuitionProgram) => Math.max(0, program.durations.findIndex((d) => !d.note))

/** Footnotes a program actually refers to, in source order. */
function programNotes(program: TuitionProgram): TuitionNoteKey[] {
  const used = new Set<TuitionNoteKey>(['extended'])
  if (program.hasMatriculation) used.add('matriculation')
  for (const d of program.durations) if (d.note) used.add(d.note)
  for (const s of program.studies) for (const t of s.totals) if (t.note) used.add(t.note)
  return TUITION.notes.filter((n) => used.has(n))
}

/**
 * Postgraduate tuition table (T.A. 2026/2027), one tab per program level.
 * Static UI over hardcoded JSON (data/tuition-postgraduate-2026-2027.json) —
 * a table on wide screens, one card per study program on phones.
 */
export function TuitionSection() {
  const { m } = useLandingI18n()
  const t = m.tuition
  const [programKey, setProgramKey] = useState<TuitionProgramKey>(TUITION.programs[0].key)
  const [durations, setDurations] = useState<Partial<Record<TuitionProgramKey, number>>>({})

  const program = TUITION.programs.find((p) => p.key === programKey) ?? TUITION.programs[0]
  const durationIndex = durations[program.key] ?? defaultDuration(program)
  const vars = { year: TUITION.academicYear, revision: TUITION.revision }

  return (
    <section id={SECTION_IDS.tuition} aria-labelledby="tuition-title" className="lp-section lp-warm">
      <div className="lp-container">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeader id="tuition-title" eyebrow={t.eyebrow} title={t.title} description={fill(t.description, vars)} />
          <Reveal className="shrink-0">
            <p className="inline-flex items-center gap-2 rounded-full border border-lp-line bg-lp-surface px-3.5 py-1.5 text-[0.8125rem] font-semibold text-lp-fg shadow-lp-sm">
              <CalendarRange size={15} aria-hidden className="text-lp-brand-text" />
              {fill(t.badge, vars)}
            </p>
          </Reveal>
        </div>

        <Reveal className="mt-8">
          <TabList
            items={TUITION.programs.map((p) => ({ key: p.key, label: t.programs[p.key], icon: PROGRAM_ICONS[p.key] }))}
            value={program.key}
            onChange={setProgramKey}
            label={t.programsLabel}
            idPrefix={TABS_ID}
            className="w-fit [mask-image:linear-gradient(to_right,#000_88%,transparent)] sm:[mask-image:none]"
          />
        </Reveal>

        <TabPanel idPrefix={TABS_ID} tabKey={program.key} className="mt-8">
          <div key={program.key} className="lp-tab-enter">
            <ProgramToolbar
              program={program}
              durationIndex={durationIndex}
              onDuration={(i) => setDurations((prev) => ({ ...prev, [program.key]: i }))}
            />
            <TuitionTable program={program} durationIndex={durationIndex} />
            <TuitionCards program={program} durationIndex={durationIndex} />
            <ProgramFooter program={program} />
          </div>
        </TabPanel>
      </div>
    </section>
  )
}

/** Summary chips + the study-period switch (only for programs with more than one period). */
function ProgramToolbar({ program, durationIndex, onDuration }: { program: TuitionProgram; durationIndex: number; onDuration: (i: number) => void }) {
  const { m, fmt } = useLandingI18n()
  const t = m.tuition
  const labelId = `${TABS_ID}-duration-${program.key}`
  // Alumni rates are a separate row of the same study program, so count names, not rows.
  const studyCount = new Set(program.studies.map((s) => s.name)).size
  const uktFrom = Math.min(...program.studies.map((s) => s.ukt))
  const chips = [
    fill(t.summary.studies, { n: studyCount }),
    fill(t.summary.duration, { n: program.durations.map((d) => d.semesters).join(' / ') }),
    fill(t.summary.uktFrom, { amount: fmt.currency(uktFrom) }),
  ]

  return (
    <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <ul className="flex flex-wrap gap-2">
        {chips.map((chip) => (
          <li key={chip} className="rounded-full border border-lp-line bg-lp-surface px-3 py-1 text-[0.8125rem] font-medium text-lp-muted">
            {chip}
          </li>
        ))}
      </ul>

      {program.durations.length > 1 && (
        <div className="flex flex-wrap items-center gap-3">
          <span id={labelId} className="text-[0.8438rem] font-medium text-lp-muted">
            {t.durationLabel}
          </span>
          <div role="radiogroup" aria-labelledby={labelId} className="inline-flex gap-1 rounded-full border border-lp-line bg-lp-sunken p-1">
            {program.durations.map((d, i) => {
              const selected = i === durationIndex
              return (
                <label
                  key={d.semesters}
                  className={cn(
                    'inline-flex h-9 cursor-pointer items-center rounded-full px-4 text-[0.8438rem] font-medium transition-[background-color,color,box-shadow] duration-200',
                    'has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-lp-ring',
                    selected ? 'bg-lp-surface text-lp-fg shadow-lp-sm ring-1 ring-lp-line' : 'text-lp-muted hover:text-lp-fg',
                  )}
                >
                  <input type="radio" name={labelId} checked={selected} onChange={() => onDuration(i)} className="sr-only" />
                  {fill(t.semesters, { n: d.semesters })}
                  {d.note && <NoteMark note={d.note} />}
                </label>
              )
            })}
          </div>
        </div>
      )}
    </div>
  )
}

/** Wide screens: one row per study program, the estimated totals highlighted on the right. */
function TuitionTable({ program, durationIndex }: { program: TuitionProgram; durationIndex: number }) {
  const { m } = useLandingI18n()
  const t = m.tuition
  const c = t.columns
  const duration = program.durations[durationIndex]
  const headRows = program.hasMatriculation ? 2 : 1
  const th = 'px-4 py-3 align-bottom font-semibold'
  const td = 'lp-tabular whitespace-nowrap px-4 py-3.5 text-right'
  const total = 'bg-lp-brand-soft/50 font-semibold text-lp-fg'

  return (
    <div className="hidden overflow-x-auto rounded-lp border border-lp-line bg-lp-surface shadow-lp-sm md:block">
      <table className="w-full min-w-[46rem] border-collapse text-left text-[0.875rem]">
        <caption className="sr-only">{fill(t.caption, { program: t.programs[program.key], year: TUITION.academicYear })}</caption>
        <thead className="border-b border-lp-line bg-lp-sunken text-[0.8125rem] text-lp-fg">
          <tr>
            <th scope="col" rowSpan={headRows} className={cn(th, 'pl-5')}>
              {c.study}
            </th>
            <th scope="col" rowSpan={headRows} className={cn(th, 'text-right')}>
              {c.spp}
              <ColumnHint>{c.sppFull}</ColumnHint>
            </th>
            <th scope="col" rowSpan={headRows} className={cn(th, 'text-right')}>
              {c.ukt}
              <NoteMark note="extended" />
              <ColumnHint>{c.uktFull}</ColumnHint>
            </th>
            {program.hasMatriculation && (
              <th scope="col" rowSpan={headRows} className={cn(th, 'text-right')}>
                {c.matriculation}
                <NoteMark note="matriculation" />
              </th>
            )}
            <th
              scope={program.hasMatriculation ? 'colgroup' : 'col'}
              colSpan={program.hasMatriculation ? 2 : 1}
              className={cn(th, 'border-l border-lp-line bg-lp-brand-soft text-lp-brand-text', program.hasMatriculation ? 'pb-2 text-center' : 'pr-5 text-right')}
            >
              {fill(c.total, { n: duration.semesters })}
              {duration.note && <NoteMark note={duration.note} />}
            </th>
          </tr>
          {program.hasMatriculation && (
            <tr className="text-[0.75rem]">
              <th scope="col" className="border-l border-t border-lp-line bg-lp-brand-soft px-4 py-2 text-right font-medium text-lp-muted">
                {c.base}
              </th>
              <th scope="col" className="border-t border-lp-line bg-lp-brand-soft py-2 pl-4 pr-5 text-right font-medium text-lp-muted">
                {c.withMatriculation}
              </th>
            </tr>
          )}
        </thead>
        <tbody className="divide-y divide-lp-line">
          {program.studies.map((study) => {
            const sum = study.totals[durationIndex]
            return (
              <tr key={`${study.name}-${study.alumni}`} className="transition-colors hover:bg-lp-sunken/60">
                <th scope="row" className="py-3.5 pl-5 pr-4 font-medium text-lp-fg">
                  <StudyName study={study} />
                </th>
                <td className={cn(td, 'text-lp-muted')}>
                  <Amount value={study.spp} />
                </td>
                <td className={cn(td, 'text-lp-muted')}>
                  <Amount value={study.ukt} />
                </td>
                {program.hasMatriculation && (
                  <td className={cn(td, 'text-lp-muted')}>
                    <Amount value={study.matriculation} />
                  </td>
                )}
                <td className={cn(td, total, 'border-l border-lp-line', !program.hasMatriculation && 'pr-5')}>
                  <Amount value={sum.base} note={sum.note} />
                </td>
                {program.hasMatriculation && (
                  <td className={cn(td, total, 'pr-5')}>
                    <Amount value={sum.withMatriculation} note={sum.note} />
                  </td>
                )}
              </tr>
            )
          })}
        </tbody>
      </table>
    </div>
  )
}

/** Phones: the same figures as a card per study program. */
function TuitionCards({ program, durationIndex }: { program: TuitionProgram; durationIndex: number }) {
  const { m } = useLandingI18n()
  const t = m.tuition
  const c = t.columns
  const duration = program.durations[durationIndex]

  return (
    <ul className="grid gap-4 sm:grid-cols-2 md:hidden">
      {program.studies.map((study) => {
        const sum = study.totals[durationIndex]
        return (
          <li key={`${study.name}-${study.alumni}`} className="flex">
            <article className="flex w-full flex-col rounded-lp border border-lp-line bg-lp-surface p-5 shadow-lp-sm">
              <h3 className="lp-h4 text-lp-fg">
                <StudyName study={study} />
              </h3>
              <dl className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-[0.8125rem]">
                <CardFigure label={c.spp} value={study.spp} />
                <CardFigure label={c.ukt} note="extended" value={study.ukt} />
                {program.hasMatriculation && <CardFigure label={c.matriculation} note="matriculation" value={study.matriculation} />}
              </dl>
              <div className="mt-4 rounded-lp-sm bg-lp-brand-soft px-4 py-3">
                <p className="text-[0.75rem] font-semibold text-lp-brand-text">
                  {fill(c.total, { n: duration.semesters })}
                  {duration.note && <NoteMark note={duration.note} />}
                </p>
                {program.hasMatriculation ? (
                  <dl className="mt-2 space-y-1.5">
                    {([
                      [c.base, sum.base],
                      [c.withMatriculation, sum.withMatriculation],
                    ] as const).map(([label, value]) => (
                      <div key={label} className="flex items-baseline justify-between gap-3">
                        <dt className="text-[0.8125rem] text-lp-muted">{label}</dt>
                        <dd className="lp-tabular text-right text-[0.9375rem] font-bold text-lp-fg">
                          <Amount value={value} note={sum.note} currency />
                        </dd>
                      </div>
                    ))}
                  </dl>
                ) : (
                  <p className="lp-tabular mt-1 text-[1.125rem] font-bold text-lp-fg">
                    <Amount value={sum.base} note={sum.note} currency />
                  </p>
                )}
              </div>
            </article>
          </li>
        )
      })}
    </ul>
  )
}

/** Footnotes + what the figures exclude, next to the admissions entry points. */
function ProgramFooter({ program }: { program: TuitionProgram }) {
  const { m } = useLandingI18n()
  const t = m.tuition
  const openContact = useLandingUi((s) => s.openContact)

  return (
    <div className="mt-6 grid gap-5 lg:grid-cols-12">
      <div className="rounded-lp border border-lp-line bg-lp-surface p-5 sm:p-6 lg:col-span-8">
        <div className="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
          <h3 className="lp-h4 inline-flex items-center gap-2 text-lp-fg">
            <Info size={17} aria-hidden className="text-lp-brand-text" />
            {t.notesTitle}
          </h3>
          <p className="text-[0.8125rem] text-lp-subtle">{t.currencyNote}</p>
        </div>
        <ul className="mt-4 space-y-2.5">
          {programNotes(program).map((note) => {
            const n = noteNumber(note)
            return (
              <li key={note} className="flex gap-3 text-[0.8438rem] leading-relaxed text-lp-muted">
                <span aria-hidden className="w-8 shrink-0 font-bold text-lp-brand-text">
                  {'*'.repeat(n)}
                </span>
                <span>
                  <span className="sr-only">{fill(t.noteRef, { n })}: </span>
                  {t.notes[note]}
                </span>
              </li>
            )
          })}
        </ul>
        <p className="mt-5 border-t border-lp-line pt-4 text-[0.8125rem] leading-relaxed text-lp-subtle">{t.exclusions}</p>
      </div>

      <div className="flex flex-col gap-6 rounded-lp border border-lp-brand/20 bg-lp-brand-soft p-5 sm:p-6 lg:col-span-4">
        <div>
          <span className="flex h-11 w-11 items-center justify-center rounded-lp bg-lp-brand text-lp-onbrand shadow-lp-sm">
            <GraduationCap size={20} aria-hidden />
          </span>
          <h3 className="lp-h3 mt-4 text-lp-fg">{t.ctaTitle}</h3>
          <p className="lp-small mt-2 text-lp-muted">{t.ctaBody}</p>
          <p className="mt-3 text-[0.75rem] leading-relaxed text-lp-subtle">{fill(t.source, { year: TUITION.academicYear, revision: TUITION.revision })}</p>
        </div>
        <div className="mt-auto flex flex-col gap-3">
          <Link to={PMB.register} className={buttonClass('primary', 'md', 'w-full')}>
            {t.register}
            <ArrowRight size={16} aria-hidden />
          </Link>
          <Button variant="secondary" onClick={openContact} className="w-full">
            <MessageSquare size={16} aria-hidden />
            {t.ask}
          </Button>
        </div>
      </div>
    </div>
  )
}

function StudyName({ study }: { study: TuitionStudy }) {
  const { m } = useLandingI18n()
  return (
    <span className="inline-flex flex-wrap items-center gap-x-2 gap-y-1">
      {study.name}
      {study.alumni && (
        <span className="rounded-full bg-lp-gold/10 px-2 py-0.5 text-[0.6875rem] font-semibold text-lp-gold-text ring-1 ring-inset ring-lp-gold/25">
          {m.tuition.alumni}
        </span>
      )}
    </span>
  )
}

function ColumnHint({ children }: { children: string }) {
  return <span className="mt-0.5 block text-[0.7188rem] font-normal leading-snug text-lp-subtle">{children}</span>
}

function CardFigure({ label, value, note }: { label: string; value: number | null; note?: TuitionNoteKey }) {
  return (
    <div>
      <dt className="text-lp-subtle">
        {label}
        {note && <NoteMark note={note} />}
      </dt>
      <dd className="lp-tabular mt-0.5 font-semibold text-lp-fg">
        <Amount value={value} currency />
      </dd>
    </div>
  )
}

/** A figure from the table; `null` is the source's "-" (not offered for that program). */
function Amount({ value, note, currency }: { value: number | null; note?: TuitionNoteKey | null; currency?: boolean }) {
  const { m, fmt } = useLandingI18n()
  if (value === null) {
    return (
      <>
        <span aria-hidden className="font-normal text-lp-subtle">
          —
        </span>
        <span className="sr-only">{m.tuition.notApplicable}</span>
      </>
    )
  }
  return (
    <>
      {currency ? fmt.currency(value) : fmt.number(value)}
      {note && <NoteMark note={note} />}
    </>
  )
}

/** Asterisk footnote marker; screen readers hear "catatan n" instead of the asterisks. */
function NoteMark({ note }: { note: TuitionNoteKey }) {
  const { m } = useLandingI18n()
  const n = noteNumber(note)
  return (
    <sup className="ml-0.5 font-bold text-lp-brand-text">
      <span aria-hidden>{'*'.repeat(n)}</span>
      <span className="sr-only"> ({fill(m.tuition.noteRef, { n })})</span>
    </sup>
  )
}
