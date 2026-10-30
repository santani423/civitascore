import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { PILLARS, pillarNumber } from '../../data/pillars'
import { LANDING_PATHS } from '../../paths'
import { SectionLink } from '../layout/SectionLink'
import { Reveal } from '../ui/Primitives'

/**
 * The five pillars drawn as a colonnade on the charcoal band. The columns are
 * equal and stand side by side — the pillars are developed together through
 * the whole journey, not one after another. Each column links to its section
 * of /student-life-journey. `className` sets the columns' height.
 */
export function PillarColonnade({ className }: { className?: string }) {
  const { m } = useLandingI18n()

  return (
    // Capped on tablets, where five full-width columns would look stretched.
    <div className="max-w-lg lg:max-w-none">
      <ol aria-label={m.studentLife.colonnade} className={cn('grid grid-cols-5 gap-2 sm:gap-3', className)}>
        {PILLARS.map((pillar, i) => {
          // Two-line inscription, e.g. "Kepemimpinan" / "Transformatif".
          const split = pillar.title.lastIndexOf(' ')
          return (
            <li key={pillar.id} className="flex">
              <Reveal delay={i * 70} className="flex w-full">
                <SectionLink
                  section={pillar.id}
                  path={LANDING_PATHS.studentLife}
                  className="group flex w-full flex-col items-center rounded-t-full border border-b-0 border-lp-band-fg/15 bg-gradient-to-b from-lp-band-fg/[0.09] to-lp-band-fg/[0.02] px-1 pb-4 pt-3 transition-[background-color,border-color,transform] duration-300 hover:-translate-y-1 hover:border-lp-accent/70 hover:from-lp-band-fg/[0.14] sm:pb-5 sm:pt-4"
                >
                  <span className="lp-tabular flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-lp-brand font-display text-[0.8125rem] font-extrabold text-lp-onbrand shadow-lp-sm transition-transform duration-300 group-hover:scale-110 sm:h-11 sm:w-11 sm:text-[0.9375rem]">
                    {pillarNumber(pillar)}
                  </span>
                  <span lang="id" className="mt-auto rotate-180 font-display text-[0.75rem] font-semibold leading-[1.35] [writing-mode:vertical-rl] sm:text-[0.875rem]">
                    <span className="block whitespace-nowrap text-lp-band-fg">{pillar.title.slice(0, split)}</span>
                    <span className="block whitespace-nowrap text-lp-band-muted">{pillar.title.slice(split + 1)}</span>
                  </span>
                </SectionLink>
              </Reveal>
            </li>
          )
        })}
      </ol>
      <span aria-hidden className="block h-1.5 rounded-full bg-lp-band-fg/15" />
      <p className="mt-4 flex items-center gap-3 text-[0.6875rem] font-semibold uppercase tracking-[0.14em] text-lp-band-muted sm:text-[0.75rem]">
        <span aria-hidden className="h-px flex-1 bg-lp-band-line" />
        <span className="text-center">{m.studentLife.span}</span>
        <span aria-hidden className="h-px flex-1 bg-lp-band-line" />
      </p>
    </div>
  )
}
