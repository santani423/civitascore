import { useLandingI18n } from '../../i18n'
import { PILLARS, STUDENT_LIFE_INTRO, STUDENT_LIFE_TITLE } from '../../data/pillars'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { Breadcrumbs } from '../layout/Breadcrumbs'
import { SectionLink } from '../layout/SectionLink'
import { Reveal } from '../ui/Primitives'
import { PillarColonnade } from './PillarColonnade'

/** Charcoal hero, like the home page's: official title and introduction, with the five pillars as a colonnade. */
export function StudentLifeHero() {
  const { m } = useLandingI18n()
  const s = m.studentLife

  return (
    <section
      id={STUDENT_LIFE_IDS.hero}
      aria-labelledby="slj-title"
      className="lp-on-band relative isolate overflow-hidden bg-lp-band pt-16 lg:pt-[4.5rem]"
    >
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div aria-hidden data-lp-decor className="pointer-events-none absolute -right-48 -top-48 -z-10 h-[36rem] w-[36rem] rounded-full bg-lp-accent/[0.13] blur-3xl" />

      <div className="lp-container grid gap-14 pb-16 pt-8 sm:pb-20 sm:pt-10 lg:grid-cols-12 lg:items-end lg:gap-12 lg:pb-24 lg:pt-14">
        <div className="lg:col-span-6 xl:col-span-7">
          <Breadcrumbs tone="band" items={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: s.eyebrow }]} />

          <Reveal>
            <p className="lp-eyebrow mt-8 text-lp-accent sm:mt-12">{s.eyebrow}</p>
            <h1 id="slj-title" lang="id" className="lp-display mt-4 text-lp-band-fg">
              <span className="text-lp-accent">{STUDENT_LIFE_TITLE.lead}</span> {STUDENT_LIFE_TITLE.rest}
            </h1>
            <p lang="id" className="lp-lead mt-6 max-w-xl text-lp-band-muted">
              {STUDENT_LIFE_INTRO}
            </p>
            {s.officialNote && <p className="mt-4 max-w-xl text-[0.8125rem] leading-relaxed text-lp-band-muted">{s.officialNote}</p>}
          </Reveal>

          <SectionLink
            section={PILLARS[0].id}
            path={LANDING_PATHS.studentLife}
            className="mt-10 hidden min-h-11 items-center gap-3 rounded-lp-sm text-[0.8125rem] font-medium text-lp-band-muted transition-colors hover:text-lp-band-fg md:inline-flex"
          >
            <span aria-hidden className="flex h-9 w-6 items-start justify-center rounded-full border-2 border-lp-band-fg/50 pt-1.5">
              <span className="lp-scroll-cue h-2 w-1 rounded-full bg-lp-accent" />
            </span>
            {s.scrollCue}
          </SectionLink>
        </div>

        <div className="lg:col-span-6 xl:col-span-5">
          <PillarColonnade className="h-60 sm:h-72 lg:h-[23rem]" />
        </div>
      </div>
    </section>
  )
}
