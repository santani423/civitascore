import { ArrowDown } from 'lucide-react'
import { fill, plural, useLandingI18n } from '../../i18n'
import type { Faculty } from '../../data/faculties'
import { LANDING_PATHS, detailPath } from '../../paths'
import { Breadcrumbs } from '../layout/Breadcrumbs'
import { SectionLink } from '../layout/SectionLink'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { FacultyCover } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

export const FACULTY_HERO_ID = 'sekilas'

/**
 * Charcoal hero of a faculty page. Both buttons scroll within the page
 * (study programs; Ormawa/UKM/activities) — no external destinations.
 * `programCount` is null until the PMB list has loaded.
 */
export function FacultyHero({ faculty, programCount }: { faculty: Faculty; programCount: number | null }) {
  const { m, intl } = useLandingI18n()
  const f = m.faculty
  const path = detailPath(LANDING_PATHS.faculties, faculty.slug)

  return (
    <section id={FACULTY_HERO_ID} aria-labelledby="faculty-title" className="lp-on-band relative isolate overflow-hidden bg-lp-band pt-16 lg:pt-[4.5rem]">
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div aria-hidden data-lp-decor className="pointer-events-none absolute -right-48 -top-48 -z-10 h-[34rem] w-[34rem] rounded-full bg-lp-accent/[0.12] blur-3xl" />

      <div className="lp-container grid gap-10 pb-14 pt-8 sm:pb-16 sm:pt-10 lg:grid-cols-12 lg:items-center lg:gap-12 lg:pb-20 lg:pt-12">
        <div className="lg:col-span-7">
          <Breadcrumbs
            tone="band"
            items={[
              { label: m.nav.home, to: LANDING_PATHS.home },
              { label: m.nav.faculties, to: LANDING_PATHS.faculties },
              { label: faculty.name, lang: 'id' },
            ]}
          />
          <Reveal>
            <p className="lp-eyebrow mt-8 text-lp-accent">{f.eyebrow}</p>
            <h1 id="faculty-title" lang="id" className="lp-display mt-4 text-lp-band-fg">
              {faculty.name}
            </h1>
            {faculty.tagline ? (
              <p lang="id" className="lp-lead mt-6 max-w-xl text-lp-band-muted">
                {faculty.tagline}
              </p>
            ) : (
              <p className="lp-lead mt-6 max-w-xl text-lp-band-muted">{f.hero.fallbackLead}</p>
            )}
            <div className="mt-9 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
              <SectionLink section={FACULTY_SECTION_IDS.programs} path={path} className={buttonClass('primary', 'lg', 'group')}>
                {f.hero.programsCta}
                <ArrowDown size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-y-0.5" />
              </SectionLink>
              <SectionLink section={FACULTY_SECTION_IDS.studentLife} path={path} className={buttonClass('bandOutline', 'lg')}>
                {f.hero.studentLifeCta}
              </SectionLink>
            </div>
            {programCount !== null && programCount > 0 && (
              <p className="mt-8 text-[0.875rem] font-medium text-lp-band-muted">
                <span className="lp-tabular font-display text-[1.25rem] font-extrabold text-lp-band-fg">{programCount}</span>{' '}
                {plural(intl, programCount, f.hero.programsFound)}
              </p>
            )}
          </Reveal>
        </div>

        <Reveal delay={80} className="lg:col-span-5">
          <div className="relative">
            <FacultyCover faculty={faculty} size="hero" priority className="aspect-[16/10] rounded-lp-xl border border-lp-band-line shadow-lp-xl lg:aspect-[5/4]" />
            {faculty.logo && (
              <img
                src={faculty.logo}
                alt={fill(f.logoAlt, { name: faculty.name })}
                className="absolute -bottom-6 left-6 h-20 w-20 rounded-lp-lg bg-white object-contain p-2 shadow-lp-lg ring-4 ring-lp-band"
              />
            )}
          </div>
        </Reveal>
      </div>
    </section>
  )
}
