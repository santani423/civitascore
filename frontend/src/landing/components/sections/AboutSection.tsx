import { Compass, DatabaseZap, Info, Map as MapIcon, RefreshCw, SlidersHorizontal, Users } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader } from '../ui/Primitives'

type StepKey = keyof Messages['about']['steps']
const STEPS: Array<{ key: StepKey; icon: typeof Compass }> = [
  { key: 'needs', icon: Compass },
  { key: 'mapping', icon: MapIcon },
  { key: 'configuration', icon: SlidersHorizontal },
  { key: 'data', icon: DatabaseZap },
  { key: 'pilot', icon: Users },
  { key: 'improvement', icon: RefreshCw },
]

export function AboutSection() {
  const { m, fmt } = useLandingI18n()
  const a = m.about
  const principles = [a.principles.integrated, a.principles.collaborative, a.principles.service]

  return (
    <section id={SECTION_IDS.about} aria-labelledby="about-title" className="pt-[var(--lp-section-y)]">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div className="lg:col-span-7">
          <SectionHeader id="about-title" eyebrow={a.eyebrow} title={a.title} />
          <Reveal delay={80}>
            <p className="lp-lead mt-6 text-lp-fg">{a.body}</p>
            <p className="lp-body mt-5 text-lp-muted">{a.bodySecondary}</p>
          </Reveal>
        </div>
        <ul className="divide-y divide-lp-line self-end border-y border-lp-line lg:col-span-5">
          {principles.map((p, i) => (
            <li key={p.title}>
              <Reveal delay={i * 80} className="flex items-baseline gap-5 py-6">
                <span className="lp-tabular font-display text-[15px] font-bold text-lp-gold-text">{fmt.number(i + 1).padStart(2, '0')}</span>
                <div>
                  <h3 className="lp-h3 text-lp-fg">{p.title}</h3>
                  <p className="lp-small mt-1 text-lp-muted">{p.body}</p>
                </div>
              </Reveal>
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}

export function ImplementationTimeline() {
  const { m } = useLandingI18n()
  const a = m.about

  return (
    <section id={SECTION_IDS.journey} aria-labelledby="journey-title" className="lp-section">
      <div className="lp-container">
        <SectionHeader id="journey-title" eyebrow={a.journeyEyebrow} title={a.journeyTitle} description={a.journeyDescription} />

        <ol className="relative mt-14 grid gap-8 md:grid-cols-3 md:gap-x-8 md:gap-y-12 lg:grid-cols-6 lg:gap-6">
          <span aria-hidden className="absolute bottom-5 left-5 top-5 w-px bg-lp-line md:hidden" />
          <span aria-hidden className="absolute left-5 right-5 top-5 hidden h-px bg-gradient-to-r from-lp-brand/50 via-lp-gold/50 to-lp-line lg:block" />
          {STEPS.map(({ key, icon: Icon }, i) => (
            <li key={key} className="relative">
              <Reveal delay={i * 70} className="flex gap-5 md:block">
                <span className="relative z-10 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-lp-line bg-lp-surface text-lp-brand-text shadow-lp-sm md:mb-6">
                  <Icon size={17} aria-hidden />
                </span>
                <div>
                  <p className="text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-gold-text">{fill(a.stepLabel, { n: i + 1 })}</p>
                  <h3 className="lp-h4 mt-1.5 text-lp-fg">{a.steps[key].title}</h3>
                  <p className="lp-small mt-2 text-lp-muted">{a.steps[key].body}</p>
                </div>
              </Reveal>
            </li>
          ))}
        </ol>

        <Reveal className="mt-12">
          <p className="flex items-start gap-2.5 rounded-lp border border-dashed border-lp-strong px-4 py-3.5 text-[13.5px] text-lp-muted">
            <Info size={16} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
            {a.journeyNote}
          </p>
        </Reveal>
      </div>
    </section>
  )
}
