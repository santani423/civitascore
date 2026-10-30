import { useState } from 'react'
import { ArrowUpRight, CalendarRange, Info, Wallet } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { UNDERGRADUATE_TUITION, type UndergraduateProgram, type UndergraduateProgramKey } from '../../data/tuitionUndergraduate'
import { SECTION_IDS } from '../../paths'
import { TabList, TabPanel } from '../ui/Tabs'
import { Reveal, SectionHeader } from '../ui/Primitives'
import { ColumnHint, TuitionCta } from './TuitionSection'

const TABS_ID = 'lp-undergraduate-tuition'
const MYATMA_URL = 'https://myatma.atmajaya.ac.id'

/**
 * Undergraduate (S1) tuition table (2027/2028), one tab per special program.
 * Static UI over hardcoded JSON (data/tuition-undergraduate-2027-2028.json) —
 * a table on wide screens, one card per study program on phones.
 */
export function UndergraduateTuitionSection() {
  const { m } = useLandingI18n()
  const t = m.undergraduateTuition
  const { academicYear: year, programs } = UNDERGRADUATE_TUITION
  const [programKey, setProgramKey] = useState<UndergraduateProgramKey>(programs[0].key)
  const program = programs.find((p) => p.key === programKey) ?? programs[0]

  return (
    <section id={SECTION_IDS.undergraduateTuition} aria-labelledby="undergraduate-tuition-title" className="lp-section lp-warm">
      <div className="lp-container">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeader id="undergraduate-tuition-title" eyebrow={m.tuition.eyebrow} title={t.title} description={fill(t.description, { year, n: programs.length })} />
          <Reveal className="shrink-0">
            <p className="inline-flex items-center gap-2 rounded-full border border-lp-line bg-lp-surface px-3.5 py-1.5 text-[0.8125rem] font-semibold text-lp-fg shadow-lp-sm">
              <CalendarRange size={15} aria-hidden className="text-lp-brand-text" />
              {fill(t.badge, { year })}
            </p>
          </Reveal>
        </div>

        <Reveal className="mt-8">
          {/* Seven programs: wrapped rather than scrolled, so none of them is hidden off-screen. */}
          <TabList
            items={programs.map((p) => ({ key: p.key, label: t.programs[p.key] }))}
            value={program.key}
            onChange={setProgramKey}
            label={t.programsLabel}
            idPrefix={TABS_ID}
            className="w-fit flex-wrap rounded-[1.5rem]"
          />
        </Reveal>

        <TabPanel idPrefix={TABS_ID} tabKey={program.key} className="mt-8">
          <div key={program.key} className="lp-tab-enter">
            <ProgramSummary program={program} />
            <TuitionTable program={program} />
            <TuitionCards program={program} />
            <div className="mt-3 flex flex-wrap justify-between gap-x-6 gap-y-1 text-[0.8125rem] text-lp-subtle">
              <p>{t.regularSppNote}</p>
              <p>{m.tuition.currencyNote}</p>
            </div>
            <div className="mt-6 grid gap-5 lg:grid-cols-12">
              <div className="rounded-lp border border-lp-line bg-lp-surface p-5 sm:p-6 lg:col-span-8">
                <InitialPayment program={program} />
                <Notes />
              </div>
              {/* The notes run much taller than the card: it follows the reader instead of stretching. */}
              <TuitionCta title={t.ctaTitle} body={t.ctaBody} source={fill(t.source, { year })} className="lg:sticky lg:top-24 lg:self-start" />
            </div>
          </div>
        </TabPanel>
      </div>
    </section>
  )
}

function ProgramSummary({ program }: { program: UndergraduateProgram }) {
  const { m, fmt } = useLandingI18n()
  const { studies } = UNDERGRADUATE_TUITION
  const sppFrom = Math.min(...studies.map((s) => s.specialSpp[program.key]))
  const initialPaymentFrom = Math.min(...Object.values(program.initialPayment))
  const chips = [
    fill(m.tuition.summary.studies, { n: studies.length }),
    fill(m.undergraduateTuition.summary.sppFrom, { amount: fmt.currency(sppFrom) }),
    fill(m.undergraduateTuition.summary.initialPaymentFrom, { amount: fmt.currency(initialPaymentFrom) }),
  ]

  return (
    <ul className="mb-6 flex flex-wrap gap-2">
      {chips.map((chip) => (
        <li key={chip} className="rounded-full border border-lp-line bg-lp-surface px-3 py-1 text-[0.8125rem] font-medium text-lp-muted">
          {chip}
        </li>
      ))}
    </ul>
  )
}

/** Wide screens: one row per study program, the program's special SPP highlighted. */
function TuitionTable({ program }: { program: UndergraduateProgram }) {
  const { m, fmt } = useLandingI18n()
  const t = m.undergraduateTuition
  const name = t.programs[program.key]
  const th = 'px-4 py-3 text-right align-bottom font-semibold'
  const td = 'lp-tabular whitespace-nowrap px-4 py-3.5 text-right'

  return (
    <div className="hidden overflow-x-auto rounded-lp border border-lp-line bg-lp-surface shadow-lp-sm md:block">
      <table className="w-full min-w-[40rem] border-collapse text-left text-[0.875rem]">
        <caption className="sr-only">{fill(t.caption, { program: name, year: UNDERGRADUATE_TUITION.academicYear })}</caption>
        <thead className="border-b border-lp-line bg-lp-sunken text-[0.8125rem] text-lp-fg">
          <tr>
            <th scope="col" className="py-3 pl-5 pr-4 align-bottom font-semibold">
              {m.tuition.columns.study}
            </th>
            <th scope="col" className={th}>
              {t.columns.regularSpp}
            </th>
            <th scope="col" className={cn(th, 'border-x border-lp-line bg-lp-brand-soft text-lp-brand-text')}>
              {fill(t.columns.specialSpp, { program: name })}
              <ColumnHint>{m.tuition.columns.sppFull}</ColumnHint>
            </th>
            <th scope="col" className={cn(th, 'pr-5')}>
              {t.columns.semesterFee}
            </th>
          </tr>
        </thead>
        <tbody className="divide-y divide-lp-line">
          {UNDERGRADUATE_TUITION.studies.map((study) => (
            <tr key={study.name} className="transition-colors hover:bg-lp-sunken/60">
              <th scope="row" className="py-3.5 pl-5 pr-4 font-medium text-lp-fg">
                {study.name}
              </th>
              <td className={cn(td, 'text-lp-muted')}>
                <s>{fmt.number(study.regularSpp)}</s>
              </td>
              <td className={cn(td, 'border-x border-lp-line bg-lp-brand-soft/50 font-semibold text-lp-fg')}>{fmt.number(study.specialSpp[program.key])}</td>
              <td className={cn(td, 'pr-5 text-lp-muted')}>{fmt.number(study.semesterFee)}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}

/** Phones: the same figures as a card per study program — label and amount on one line, since there are twenty of them. */
function TuitionCards({ program }: { program: UndergraduateProgram }) {
  const { m, fmt } = useLandingI18n()
  const t = m.undergraduateTuition
  const row = 'flex items-baseline justify-between gap-4'

  return (
    <ul className="grid gap-3 sm:grid-cols-2 md:hidden">
      {UNDERGRADUATE_TUITION.studies.map((study) => (
        <li key={study.name} className="flex">
          <article className="w-full rounded-lp border border-lp-line bg-lp-surface p-4 shadow-lp-sm">
            <h3 className="lp-h4 text-lp-fg">{study.name}</h3>
            <dl className="mt-3 space-y-1.5 text-[0.8125rem]">
              <div className={row}>
                <dt className="text-lp-subtle">{t.columns.regularSpp}</dt>
                <dd className="lp-tabular whitespace-nowrap text-lp-muted">
                  <s>{fmt.currency(study.regularSpp)}</s>
                </dd>
              </div>
              <div className={cn(row, 'rounded-lp-sm bg-lp-brand-soft px-3 py-2')}>
                <dt className="font-semibold text-lp-brand-text">{fill(t.columns.specialSpp, { program: t.programs[program.key] })}</dt>
                <dd className="lp-tabular whitespace-nowrap text-[0.9375rem] font-bold text-lp-fg">{fmt.currency(study.specialSpp[program.key])}</dd>
              </div>
              <div className={row}>
                <dt className="text-lp-subtle">{t.columns.semesterFee}</dt>
                <dd className="lp-tabular whitespace-nowrap font-semibold text-lp-fg">{fmt.currency(study.semesterFee)}</dd>
              </div>
            </dl>
          </article>
        </li>
      ))}
    </ul>
  )
}

/** The first payment differs per program and category; study programs aren't mapped to a category because the source only names the categories. */
function InitialPayment({ program }: { program: UndergraduateProgram }) {
  const { m, fmt } = useLandingI18n()
  const t = m.undergraduateTuition.initialPayment
  const [before, after] = t.installments.split('{link}')
  const categories = ['general', 'medicine', 'education'] as const

  return (
    <>
      <h3 className="lp-h4 inline-flex items-center gap-2 text-lp-fg">
        <Wallet size={17} aria-hidden className="text-lp-brand-text" />
        {t.title}
      </h3>
      <dl className="mt-4 grid gap-3 sm:grid-cols-3">
        {categories.map((category) => (
          <div key={category} className="flex flex-col justify-between gap-1 rounded-lp-sm border border-lp-line bg-lp-sunken/60 px-4 py-3">
            <dt className="text-[0.8125rem] leading-snug text-lp-muted">{t.categories[category]}</dt>
            <dd className="lp-tabular text-[1.0625rem] font-bold text-lp-fg">{fmt.currency(program.initialPayment[category])}</dd>
          </div>
        ))}
      </dl>
      <p className="mt-3 text-[0.8438rem] leading-relaxed text-lp-muted">
        {before}
        <a
          href={MYATMA_URL}
          target="_blank"
          rel="noopener noreferrer"
          className="inline-flex items-center gap-1 font-medium text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text"
        >
          myatma.atmajaya.ac.id
          <ArrowUpRight size={14} aria-hidden />
          <span className="sr-only"> {m.studentLife.contact.newTab}</span>
        </a>
        {after}
      </p>
    </>
  )
}

function Notes() {
  const { m, fmt } = useLandingI18n()
  const t = m.undergraduateTuition
  const { uniform } = UNDERGRADUATE_TUITION
  const vars = { tourismUniform: fmt.currency(uniform.tourism), mechanicalUniform: fmt.currency(uniform.mechanicalEngineering) }

  return (
    <div className="mt-5 border-t border-lp-line pt-5">
      <h3 className="lp-h4 inline-flex items-center gap-2 text-lp-fg">
        <Info size={17} aria-hidden className="text-lp-brand-text" />
        {t.notesTitle}
      </h3>
      <ol className="mt-4 list-decimal space-y-2.5 pl-5 text-[0.8438rem] leading-relaxed text-lp-muted marker:font-semibold marker:text-lp-brand-text">
        {Object.entries(t.notes).map(([key, note]) => (
          <li key={key}>{fill(note, vars)}</li>
        ))}
      </ol>
    </div>
  )
}
