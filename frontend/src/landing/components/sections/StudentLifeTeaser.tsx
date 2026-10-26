import { Link } from 'react-router-dom'
import { ArrowRight } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { STUDENT_LIFE_TITLE } from '../../data/pillars'
import { LANDING_PATHS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { PillarColonnade } from '../student-life/PillarColonnade'

/**
 * Home-page entry to /student-life-journey, between the academic cycle and
 * the news. Its bottom spacing comes from the next section's top padding.
 */
export function StudentLifeTeaser() {
  const { m } = useLandingI18n()
  const s = m.studentLife

  return (
    <section aria-labelledby="slj-teaser-title" className="pt-[var(--lp-section-y)]">
      <div className="lp-container">
        <Reveal>
          <div className="lp-on-band relative isolate overflow-hidden rounded-lp-xl bg-lp-band px-6 py-10 shadow-lp-lg sm:px-10 sm:py-12 lg:px-14 lg:py-14">
            <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
            <div aria-hidden data-lp-decor className="pointer-events-none absolute -right-32 -top-40 -z-10 h-96 w-96 rounded-full bg-lp-accent/[0.12] blur-3xl" />
            <div className="grid items-center gap-12 lg:grid-cols-12">
              <div className="lg:col-span-6">
                <p className="lp-eyebrow text-lp-accent">{s.eyebrow}</p>
                <h2 id="slj-teaser-title" lang="id" className="lp-h2 mt-3 text-lp-band-fg">
                  <span className="text-lp-accent">{STUDENT_LIFE_TITLE.lead}</span> {STUDENT_LIFE_TITLE.rest}
                </h2>
                <p className="lp-lead mt-4 max-w-lg text-lp-band-muted">{s.teaser.body}</p>
                <Link to={LANDING_PATHS.studentLife} className={buttonClass('primary', 'lg', 'group mt-8')}>
                  {s.teaser.cta}
                  <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
                </Link>
              </div>
              <div className="lg:col-span-6">
                <PillarColonnade className="h-56 sm:h-64" />
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
