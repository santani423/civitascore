import { ArrowRight, DatabaseZap, History, MonitorSmartphone, ShieldCheck, type LucideIcon } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { PHOTOS } from '../../data/photos'
import { SECTION_IDS } from '../../paths'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { SectionLink } from '../layout/SectionLink'
import { Crest } from '../layout/Wordmark'

type PointKey = keyof Messages['integration']['points']

/** Each point maps to something the platform enforces today (shared master data, RBAC, audit log, responsive UI). */
const POINTS: Array<{ key: PointKey; icon: LucideIcon }> = [
  { key: 'data', icon: DatabaseZap },
  { key: 'roles', icon: ShieldCheck },
  { key: 'audit', icon: History },
  { key: 'devices', icon: MonitorSmartphone },
]

/** Section E — integration purpose and institutional context, in the reference's photo-band layout. */
export function IntegrationSection() {
  const { m } = useLandingI18n()
  const photo = PHOTOS.campusFacade
  const c = m.integration

  return (
    <section id={SECTION_IDS.integration} aria-labelledby="integration-title" className="lp-warm relative isolate overflow-hidden">
      <div aria-hidden data-lp-decor className="lp-sweep-br absolute -bottom-px right-0 -z-10 h-20 w-28 bg-lp-accent sm:h-36 sm:w-48 lg:h-44 lg:w-56" />

      <div className="grid lg:grid-cols-12">
        <div className="relative h-72 sm:h-96 lg:col-span-5 lg:h-auto lg:min-h-[36rem]">
          <img
            src={photo.src}
            srcSet={photo.srcSet}
            sizes="(min-width: 1024px) 42vw, 100vw"
            width={photo.width}
            height={photo.height}
            alt={m.photos.campusFacade}
            loading="lazy"
            decoding="async"
            className="lp-photo-curve absolute inset-0 h-full w-full object-cover object-[50%_30%]"
          />
          <div aria-hidden data-lp-decor className="lp-sweep-bl absolute -bottom-px left-0 h-20 w-32 bg-lp-accent sm:h-28 sm:w-44" />
        </div>

        <div className="px-[var(--lp-gutter)] py-14 sm:py-16 lg:col-span-7 lg:py-20 lg:pl-14 xl:pl-20">
          <Reveal className="max-w-2xl">
            <p className="lp-eyebrow text-lp-brand-text">{c.eyebrow}</p>
            <h2 id="integration-title" className="lp-h2 mt-3 text-lp-fg">
              {c.title}
            </h2>
            <p className="lp-lead mt-5 text-lp-muted">{c.description}</p>
          </Reveal>

          <ul className="mt-10 grid max-w-2xl grid-cols-2 gap-x-6 gap-y-8 sm:grid-cols-4 sm:gap-x-0 sm:divide-x sm:divide-lp-line">
            {POINTS.map(({ key, icon: Icon }, i) => (
              <li key={key} className="sm:px-4 sm:first:pl-0 sm:last:pr-0">
                <Reveal delay={i * 70}>
                  <Icon size={30} strokeWidth={1.6} aria-hidden className="text-lp-brand-text" />
                  <h3 className="mt-3 text-[0.9063rem] font-semibold leading-snug text-lp-fg">{c.points[key].title}</h3>
                  <p className="mt-1.5 text-[0.8125rem] leading-relaxed text-lp-muted">{c.points[key].body}</p>
                </Reveal>
              </li>
            ))}
          </ul>

          <Reveal className="mt-10 flex max-w-2xl items-start gap-4 rounded-lp border border-lp-line bg-lp-surface p-4 shadow-lp-sm sm:p-5">
            <Crest className="h-12" />
            <p className="text-[0.875rem] leading-relaxed text-lp-muted">{c.mission}</p>
          </Reveal>

          <Reveal className="mt-8">
            <SectionLink section={SECTION_IDS.services} className={buttonClass('primary', 'md')}>
              {c.cta}
              <ArrowRight size={16} aria-hidden />
            </SectionLink>
          </Reveal>
        </div>
      </div>
    </section>
  )
}
