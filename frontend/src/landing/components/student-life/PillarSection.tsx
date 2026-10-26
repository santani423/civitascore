import { ArrowRight, CalendarClock } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import { pillarNumber, type Pillar } from '../../data/pillars'
import { activityTarget } from '../../lib/activities'
import { LANDING_PATHS, STUDENT_LIFE_IDS } from '../../paths'
import { SectionLink } from '../layout/SectionLink'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { ActivityLink } from './ActivityLink'
import { PillarImage } from './PillarImage'

/**
 * One pillar as an editorial spread: the official icon on a tinted panel with
 * an oversized number, beside the name, description and activity link.
 * Sides and background alternate from one pillar to the next.
 */
export function PillarSection({ pillar, index }: { pillar: Pillar; index: number }) {
  const { m } = useLandingI18n()
  const s = m.studentLife
  const flipped = index % 2 === 1
  const number = pillarNumber(pillar)
  const gloss = s.gloss[pillar.key]

  return (
    // scroll-mt clears the sticky pillar nav (3.5rem + border; the navbar is covered by scroll-padding-top).
    <section id={pillar.id} aria-labelledby={`${pillar.id}-title`} className={cn('lp-section scroll-mt-16', flipped ? 'lp-warm' : 'bg-lp-canvas')}>
      <div className="lp-container grid items-center gap-10 lg:grid-cols-12 lg:gap-16">
        <Reveal className={cn('lg:col-span-6', flipped && 'lg:order-2')}>
          <div className="group relative isolate flex aspect-[4/3] items-center justify-center overflow-hidden rounded-lp-xl border border-lp-line bg-gradient-to-br from-lp-brand-soft via-lp-surface to-lp-sunken shadow-lp-sm sm:aspect-[16/9] lg:aspect-[5/4]">
            <span
              aria-hidden
              data-lp-decor
              className={cn(
                'lp-tabular pointer-events-none absolute -bottom-[0.2em] -z-10 select-none font-display text-[10rem] font-extrabold leading-none tracking-[-0.06em] text-lp-accent/[0.09] sm:text-[13rem] lg:text-[15rem]',
                flipped ? '-right-[0.04em]' : '-left-[0.04em]',
              )}
            >
              {number}
            </span>
            <span
              aria-hidden
              data-lp-decor
              className={cn('pointer-events-none absolute -top-20 -z-10 h-72 w-72 rounded-full border-[28px] border-lp-accent/[0.07]', flipped ? '-left-20' : '-right-20')}
            />
            <PillarImage
              pillar={pillar}
              alt={s.art[pillar.key]}
              className="h-[86%] w-auto drop-shadow-[0_24px_28px_rgb(var(--lp-brand)/0.22)] transition-transform duration-700 ease-out group-hover:scale-[1.04]"
            />
          </div>
        </Reveal>

        <Reveal delay={80} className="lg:col-span-6">
          <p className="lp-eyebrow flex items-center gap-3 text-lp-brand-text">
            <span aria-hidden className="h-px w-8 bg-current opacity-60" />
            {fill(s.pillarLabel, { n: number })}
          </p>
          <h2 id={`${pillar.id}-title`} lang="id" className="lp-h2 mt-4 text-lp-fg">
            {pillar.title}
          </h2>
          {gloss && <p className="mt-2 text-[0.9375rem] font-medium text-lp-subtle">{gloss}</p>}
          <p lang="id" className="lp-lead mt-6 max-w-xl text-lp-muted">
            {pillar.description}
          </p>
          <PillarActivityCta pillar={pillar} />
        </Reveal>
      </div>
    </section>
  )
}

/**
 * "Lihat Kegiatan untuk Pilar Ini" — only promised when the destination
 * really filters by pillar. Without any activity page the section says so
 * and offers the BKK contact instead of a button that goes nowhere.
 */
function PillarActivityCta({ pillar }: { pillar: Pillar }) {
  const { m } = useLandingI18n()
  const a = m.studentLife.activities
  const target = activityTarget(pillar)

  if (target) {
    return (
      <div className="mt-9">
        <ActivityLink target={target} className={buttonClass('primary', 'lg', 'group')}>
          {target.filtered ? a.cta : a.ctaAll}
          {target.filtered && <span className="sr-only">: {pillar.title}</span>}
          <ArrowRight size={18} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
        </ActivityLink>
        {!target.filtered && <p className="mt-3 text-[0.8125rem] text-lp-subtle">{a.allNote}</p>}
      </div>
    )
  }

  return (
    <div className="mt-9 flex max-w-xl flex-col gap-4 rounded-lp border border-dashed border-lp-strong bg-lp-surface/70 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
      <p className="flex items-start gap-2.5 text-[0.875rem] leading-relaxed text-lp-muted">
        <CalendarClock size={18} aria-hidden className="mt-0.5 shrink-0 text-lp-gold-text" />
        {a.pending}
      </p>
      <SectionLink section={STUDENT_LIFE_IDS.contact} path={LANDING_PATHS.studentLife} className={buttonClass('secondary', 'sm', 'group shrink-0')}>
        {a.ask}
        <ArrowRight size={15} aria-hidden className="transition-transform duration-200 group-hover:translate-x-1" />
      </SectionLink>
    </div>
  )
}
