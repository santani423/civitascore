import { Info, MapPin } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { PHOTOS, type PhotoKey } from '../../data/photos'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { Breadcrumbs, type Crumb } from '../layout/Breadcrumbs'
import { SectionLink } from '../layout/SectionLink'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'

interface PageHeroProps {
  titleId: string
  crumbs: Crumb[]
  eyebrow: string
  titleLead: string
  titleAccent: string
  lead: string
  photo: PhotoKey
  /** Figures computed from the (sample) data, e.g. how many entries are listed. */
  stats: Array<{ value: string; label: string }>
}

/** Charcoal hero of the directory pages, in the style of the home and Student Life Journey heroes. */
export function PageHero({ titleId, crumbs, eyebrow, titleLead, titleAccent, lead, photo, stats }: PageHeroProps) {
  const { m } = useLandingI18n()
  const image = PHOTOS[photo]

  return (
    <section aria-labelledby={titleId} className="lp-on-band relative isolate overflow-hidden bg-lp-band pt-16 lg:pt-[4.5rem]">
      <div aria-hidden data-lp-decor className="lp-flutes pointer-events-none absolute inset-0 -z-10" />
      <div aria-hidden data-lp-decor className="pointer-events-none absolute -right-48 -top-48 -z-10 h-[34rem] w-[34rem] rounded-full bg-lp-accent/[0.12] blur-3xl" />

      <div className="lp-container grid gap-10 pb-14 pt-8 sm:pb-16 sm:pt-10 lg:grid-cols-12 lg:items-center lg:gap-12 lg:pb-20 lg:pt-12">
        <div className="lg:col-span-6 xl:col-span-7">
          <Breadcrumbs tone="band" items={crumbs} />
          <Reveal>
            <p className="lp-eyebrow mt-8 text-lp-accent">{eyebrow}</p>
            <h1 id={titleId} className="lp-display mt-4 text-lp-band-fg">
              {titleLead} <span className="text-lp-accent">{titleAccent}</span>
            </h1>
            <p className="lp-lead mt-6 max-w-xl text-lp-band-muted">{lead}</p>
            <dl className="mt-9 flex flex-wrap gap-x-10 gap-y-5">
              {stats.map((stat) => (
                <div key={stat.label} className="flex flex-col-reverse">
                  <dt className="mt-1 text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-band-muted">{stat.label}</dt>
                  <dd className="lp-tabular font-display text-[2rem] font-extrabold leading-none text-lp-band-fg">{stat.value}</dd>
                </div>
              ))}
            </dl>
          </Reveal>
        </div>

        <Reveal delay={80} className="lg:col-span-6 xl:col-span-5">
          <figure className="relative aspect-[16/10] overflow-hidden rounded-lp-xl border border-lp-band-line bg-lp-band-elev shadow-lp-xl lg:aspect-[5/4]">
            <img
              src={image.src}
              srcSet={image.srcSet}
              sizes="(min-width: 1024px) 40vw, 100vw"
              width={image.width}
              height={image.height}
              alt={m.photos[photo]}
              fetchPriority="high"
              decoding="async"
              className="h-full w-full object-cover"
            />
            <figcaption className="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-lp-band/75 px-3 py-1.5 text-[0.75rem] font-medium text-lp-band-fg backdrop-blur">
              <MapPin size={13} aria-hidden className="text-lp-accent" />
              {m.hero.photoCaption}
            </figcaption>
          </figure>
        </Reveal>
      </div>
    </section>
  )
}

/** States plainly that the entries are demonstration content, and where official information comes from. */
export function DemoNotice({ className }: { className?: string }) {
  const { m } = useLandingI18n()
  const d = m.affairs.demo
  return (
    <div role="note" className={className}>
      <div className="flex flex-col gap-4 rounded-lp-lg border border-lp-gold/40 bg-lp-gold/[0.07] p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <p className="flex items-start gap-3 text-[0.875rem] leading-relaxed text-lp-fg">
          <Info size={18} aria-hidden className="mt-0.5 shrink-0 text-lp-gold-text" />
          <span>
            <strong className="font-semibold">{d.title}</strong> {d.body}
          </span>
        </p>
        <SectionLink section={STUDENT_LIFE_IDS.contact} path={LANDING_PATHS.studentLife} className={buttonClass('secondary', 'sm', 'shrink-0')}>
          {d.contact}
        </SectionLink>
      </div>
    </div>
  )
}
