import { useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { ArrowRight, Check, Factory, GraduationCap, HandCoins, HeartHandshake, MessagesSquare, Presentation, Quote, Sparkles, type LucideIcon } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { CAMPAIGNS, CONTRIBUTION_PROGRAMS, CONTRIBUTORS, DONATION_BY_FACULTY, DONATION_BY_YEAR, MENTORING_HOURS_BY_YEAR, findAlumnus, type ContributionKey, type ContributionProgram } from '../../data/alumni'
import { facultyName } from '../../data/faculties'
import { LANDING_PATHS } from '../../paths'
import { formatRupiahBillions, isEmail } from '../../lib/alumniFormat'
import { alumnusPath } from '../../lib/alumniPaths'
import { useLandingUi } from '../../store/ui'
import { Button } from '../../components/ui/Button'
import { Dialog } from '../../components/ui/Overlay'
import { Reveal } from '../../components/ui/Primitives'
import { BulletList } from '../../components/student-affairs/Detail'
import { BarList, ColumnChart, Meter } from '../../components/alumni/Charts'
import { TextField } from '../../components/alumni/FormFields'
import { Portrait } from '../../components/alumni/Portrait'
import { AlumniDemoNotice, AlumniPageHero, PreviewHeader } from '../../components/alumni/Shared'

const PROGRAM_ICON: Record<ContributionKey, LucideIcon> = {
  mentoring: HeartHandshake,
  scholarship: GraduationCap,
  donation: HandCoins,
  lecturer: Presentation,
  sharing: MessagesSquare,
  industry: Factory,
}

function useProgramFigure() {
  const { intl, fmt } = useLandingI18n()
  return (program: Pick<ContributionProgram, 'unit'>, value: number) => (program.unit === 'rupiahBillions' ? formatRupiahBillions(value, intl) : fmt.number(value))
}

/** Taking part is simulated: a confirmation in the page, nothing leaves the browser. */
function ProgramDialog({ program, onClose }: { program: ContributionProgram | null; onClose: () => void }) {
  const { m } = useLandingI18n()
  const c = m.alumni.contributions
  const toast = useLandingUi((s) => s.toast)
  const [email, setEmail] = useState('')
  const [error, setError] = useState<string>()
  if (!program) return null
  const Icon = PROGRAM_ICON[program.key]

  const submit = (e: FormEvent) => {
    e.preventDefault()
    if (!isEmail(email)) {
      setError(c.emailError)
      return
    }
    setError(undefined)
    setEmail('')
    onClose()
    toast(fill(c.toast, { program: c.programs[program.key] }))
  }

  return (
    <Dialog
      open
      onClose={onClose}
      title={c.programs[program.key]}
      description={<span lang="id">{program.description}</span>}
      icon={
        <span aria-hidden className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
          <Icon size={20} />
        </span>
      }
    >
      <h3 className="lp-h4 text-lp-fg">{c.howTo}</h3>
      <BulletList items={program.involvement} className="mt-3" />
      <form onSubmit={submit} noValidate className="mt-6 border-t border-lp-line pt-6">
        <TextField label={c.emailLabel} type="email" value={email} onChange={setEmail} error={error} autoComplete="email" placeholder={c.emailPlaceholder} />
        <Button type="submit" className="mt-4 w-full">
          <Check size={16} aria-hidden />
          {c.submit}
        </Button>
        <p className="mt-3 text-[0.8125rem] text-lp-muted">{c.note}</p>
      </form>
    </Dialog>
  )
}

function ProgramCard({ program, onOpen }: { program: ContributionProgram; onOpen: () => void }) {
  const { m, fmt } = useLandingI18n()
  const c = m.alumni.contributions
  const figure = useProgramFigure()
  const Icon = PROGRAM_ICON[program.key]
  const pct = (program.value / program.target) * 100
  return (
    <article className="group flex h-full w-full flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg sm:p-6">
      <div className="flex items-start justify-between gap-4">
        <span aria-hidden className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text transition-colors duration-300 group-hover:bg-lp-brand group-hover:text-lp-onbrand">
          <Icon size={20} />
        </span>
        <div className="text-right">
          <p className="font-display text-[1.75rem] font-extrabold leading-none tracking-tight text-lp-fg">{figure(program, program.value)}</p>
          <p className="mt-1 text-[0.75rem] text-lp-subtle">{c.units[program.key]}</p>
        </div>
      </div>
      <h3 className="lp-h4 mt-4 text-lp-fg">{c.programs[program.key]}</h3>
      <p lang="id" className="lp-small mt-1.5 text-lp-muted">
        {program.description}
      </p>
      <div className="mt-auto pt-5">
        <Meter value={program.value} max={program.target} label={fill(c.progress, { program: c.programs[program.key] })}>
          <span>{fill(c.target, { value: figure(program, program.target) })}</span>
          <span className="font-semibold text-lp-fg">{fmt.percent(pct, 0)}</span>
        </Meter>
        <Button variant="secondary" size="sm" className="group/btn mt-4 w-full" onClick={onOpen} aria-label={`${c.join}: ${c.programs[program.key]}`}>
          {c.join}
          <ArrowRight size={15} aria-hidden className="transition-transform duration-200 group-hover/btn:translate-x-1" />
        </Button>
      </div>
    </article>
  )
}

/** /komunitas-alumni/kontribusi — the six ways alumni give back, the figures behind them, and open campaigns. */
export function ContributionsPage() {
  const { m, fmt, intl } = useLandingI18n()
  const c = m.alumni.contributions
  useDocumentMeta({ title: fill(m.meta.pageTitle, { page: c.metaTitle }), description: c.metaDescription, ogLocale: m.meta.ogLocale, path: LANDING_PATHS.alumniContributions })
  const [selected, setSelected] = useState<ContributionProgram | null>(null)
  const totalDonation = CONTRIBUTION_PROGRAMS.find((p) => p.key === 'donation')!.value
  const rupiah = (n: number) => formatRupiahBillions(n, intl)

  return (
    <>
      <AlumniPageHero
        titleId="contributions-title"
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.alumni, to: LANDING_PATHS.alumni }, { label: m.nav.alumniContributions }]}
        eyebrow={c.eyebrow}
        titleLead={c.titleLead}
        titleAccent={c.titleAccent}
        lead={c.lead}
        aside={
          <div className="rounded-lp-lg border border-lp-band-line bg-lp-band-elev p-6 shadow-lp-lg">
            <p className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-accent">{c.heroLabel}</p>
            <p className="mt-3 font-display text-[3.25rem] font-extrabold leading-none tracking-tight text-lp-band-fg">{rupiah(totalDonation)}</p>
            <p className="mt-2 text-[0.875rem] text-lp-band-muted">{c.heroBody}</p>
          </div>
        }
      />

      <section aria-labelledby="contributions-programs" className="lp-section pb-0 pt-8 sm:pt-10">
        <div className="lp-container">
          <AlumniDemoNotice className="mb-10" />
          <PreviewHeader id="contributions-programs" eyebrow={c.programsEyebrow} title={c.programsTitle} lead={c.programsLead} />
          <ul className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {CONTRIBUTION_PROGRAMS.map((program) => (
              <li key={program.key} className="flex">
                <ProgramCard program={program} onOpen={() => setSelected(program)} />
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section aria-labelledby="contributions-impact" className="lp-section lp-warm mt-16 sm:mt-20">
        <div className="lp-container">
          <PreviewHeader id="contributions-impact" eyebrow={c.impactEyebrow} title={c.impactTitle} lead={c.impactLead} />
          <div className="mt-8 grid gap-5 lg:grid-cols-3">
            <ColumnChart title={c.donationChart} caption={c.donationChartSub} axis={c.yearAxis} unit={c.donationUnit} format={rupiah} data={DONATION_BY_YEAR.map((d) => ({ label: d.year, value: d.value }))} />
            <ColumnChart title={c.hoursChart} caption={c.hoursChartSub} axis={c.yearAxis} unit={c.hoursUnit} format={(n) => fmt.compact(n)} data={MENTORING_HOURS_BY_YEAR.map((d) => ({ label: d.year, value: d.value }))} />
            <BarList title={c.facultyChart} caption={c.facultyChartSub} lang="id" format={rupiah} data={DONATION_BY_FACULTY.map((d) => ({ label: facultyName(d.faculty).replace('Fakultas ', ''), value: d.value }))} />
          </div>
        </div>
      </section>

      <section aria-labelledby="contributions-campaigns" className="lp-section pb-0">
        <div className="lp-container">
          <PreviewHeader id="contributions-campaigns" eyebrow={c.campaignsEyebrow} title={c.campaignsTitle} />
          <ul className="mt-8 grid gap-5 md:grid-cols-3">
            {CAMPAIGNS.map((campaign) => (
              <li key={campaign.slug} className="flex">
                <article className="flex w-full flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm sm:p-6">
                  <Sparkles size={20} aria-hidden className="text-lp-brand-text" />
                  <h3 lang="id" className="lp-h4 mt-3 text-lp-fg">
                    {campaign.title}
                  </h3>
                  <p lang="id" className="lp-small mt-1.5 text-lp-muted">
                    {campaign.description}
                  </p>
                  <div className="mt-auto pt-5">
                    <p className="font-display text-[1.5rem] font-extrabold leading-none text-lp-fg">{rupiah(campaign.raised)}</p>
                    <p className="mb-3 mt-1 text-[0.8125rem] text-lp-muted">{fill(c.ofGoal, { goal: rupiah(campaign.goal), donors: fmt.number(campaign.donors) })}</p>
                    <Meter value={campaign.raised} max={campaign.goal} label={campaign.title} />
                  </div>
                </article>
              </li>
            ))}
          </ul>
        </div>
      </section>

      <section aria-labelledby="contributions-voices" className="lp-section">
        <div className="lp-container">
          <PreviewHeader id="contributions-voices" eyebrow={c.voicesEyebrow} title={c.voicesTitle} />
          <ul className="mt-8 grid gap-5 md:grid-cols-2">
            {CONTRIBUTORS.map((contributor) => {
              const person = findAlumnus(contributor.alumnus)
              if (!person) return null
              return (
                <li key={contributor.alumnus} className="flex">
                  <Reveal className="w-full">
                    <figure className="flex h-full gap-4 rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm sm:gap-5 sm:p-6">
                      <Portrait person={person} className="aspect-[4/5] w-20 shrink-0 rounded-b-lp-sm rounded-t-full sm:w-24" />
                      <div className="min-w-0">
                        <Quote aria-hidden size={20} className="text-lp-brand-text" />
                        <blockquote lang="id" className="lp-serif mt-1.5 text-[1.125rem] leading-snug text-lp-fg">
                          {contributor.statement}
                        </blockquote>
                        <figcaption className="mt-3 text-[0.8125rem] text-lp-muted">
                          <Link to={alumnusPath(person.slug)} lang="id" className="font-semibold text-lp-fg underline decoration-lp-strong underline-offset-[0.2em] transition-colors hover:text-lp-brand-text">
                            {person.name}
                          </Link>{' '}
                          · {c.programs[contributor.kind]}
                        </figcaption>
                      </div>
                    </figure>
                  </Reveal>
                </li>
              )
            })}
          </ul>
        </div>
      </section>

      <ProgramDialog program={selected} onClose={() => setSelected(null)} />
    </>
  )
}
