import { Link } from 'react-router-dom'
import { ArrowRight, BellRing, FileSignature, GraduationCap, Workflow, type LucideIcon } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { useAppEntry } from '../../hooks/useAppEntry'
import { SECTION_IDS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'

type FeatureKey = keyof Messages['features']['items']

/** Section C — each advantage describes a flow that works today (PMB→student conversion, KRS→billing, portal PDFs, approvals, announcements). */
const FEATURES: Array<{ key: FeatureKey; icon: LucideIcon }> = [
  { key: 'integrated', icon: Workflow },
  { key: 'academic', icon: GraduationCap },
  { key: 'administration', icon: FileSignature },
  { key: 'information', icon: BellRing },
]

export function FeaturesSection() {
  const { m } = useLandingI18n()
  const entry = useAppEntry()
  const f = m.features

  return (
    <section id={SECTION_IDS.about} aria-labelledby="features-title" className="lp-section">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-10">
        <Reveal className="lg:col-span-4 lg:pt-2">
          <p className="lp-eyebrow text-lp-brand-text">{f.eyebrow}</p>
          <h2 id="features-title" className="lp-h2 mt-3 text-lp-fg">
            {f.title}
          </h2>
          <p className="lp-body mt-4 text-lp-muted">{f.description}</p>
          <Link to={entry.to} className={buttonClass('primary', 'md', 'mt-8')}>
            {entry.authenticated ? m.nav.openDashboard : f.cta}
            <ArrowRight size={16} aria-hidden />
          </Link>
        </Reveal>

        <ul className="grid gap-5 sm:grid-cols-2 lg:col-span-8">
          {FEATURES.map(({ key, icon: Icon }, i) => (
            <li key={key} className="flex">
              <Reveal delay={i * 70} className="flex w-full">
                <article className="group relative flex w-full flex-col overflow-hidden rounded-lp border border-lp-line bg-lp-surface p-6 shadow-lp-sm transition-[box-shadow,border-color] duration-300 hover:border-lp-brand/30 hover:shadow-lp-md sm:p-7">
                  <span aria-hidden data-lp-decor className="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-lp-brand transition-transform duration-500 group-hover:scale-x-100" />
                  <span className="flex h-12 w-12 items-center justify-center rounded-full border-2 border-lp-brand/20 bg-lp-brand-soft text-lp-brand-text">
                    <Icon size={22} aria-hidden />
                  </span>
                  <h3 className="lp-h3 mt-5 text-lp-fg">{f.items[key].title}</h3>
                  <p className="lp-small mt-2 text-lp-muted">{f.items[key].body}</p>
                </article>
              </Reveal>
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}
