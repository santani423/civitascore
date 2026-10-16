import { ArrowRight, Check } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { useLandingUi } from '../../store/ui'
import { SECTION_IDS } from '../../paths'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { SectionLink } from '../layout/SectionLink'
import { DashboardPreview } from './DashboardPreview'

export function HeroSection() {
  const { m, locale } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  const h = m.hero
  // Chinese runs the three headline parts together; Latin scripts need spaces.
  const sep = locale === 'zh-CN' ? '' : ' '

  return (
    <section id={SECTION_IDS.home} aria-labelledby="hero-title" className="relative overflow-hidden pb-24 pt-28 sm:pb-32 sm:pt-36 lg:pt-40">
      <div aria-hidden className="lp-grid-bg pointer-events-none absolute inset-x-0 top-0 h-[44rem]" />
      <div aria-hidden className="lp-hero-glow pointer-events-none absolute inset-0" />

      <div className="lp-container relative">
        <div className="mx-auto max-w-4xl text-center">
          <Reveal>
            <p className="inline-flex items-center gap-2 rounded-full border border-lp-line bg-lp-surface/70 py-1.5 pl-2 pr-4 text-[13px] font-medium text-lp-muted shadow-lp-sm backdrop-blur">
              <span className="relative flex h-5 w-5 items-center justify-center rounded-full bg-lp-brand/10">
                <span className="lp-pulse relative h-1.5 w-1.5 rounded-full bg-lp-brand text-lp-brand" />
              </span>
              {h.eyebrow}
            </p>
          </Reveal>
          <Reveal delay={80}>
            <h1 id="hero-title" className="lp-display mt-7 text-lp-fg">
              {h.titleLead}
              {sep}
              <span className="whitespace-nowrap text-lp-brand-text">{h.titleAccent}</span>
              {sep}
              {h.titleTail}
            </h1>
          </Reveal>
          <Reveal delay={160}>
            <p className="lp-lead mx-auto mt-7 max-w-2xl text-lp-muted">{h.subtitle}</p>
          </Reveal>
          <Reveal delay={240}>
            <div className="mt-10 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
              <Button size="lg" onClick={() => openRequest('demo')}>
                {h.primaryCta}
                <ArrowRight size={17} aria-hidden />
              </Button>
              <SectionLink section={SECTION_IDS.platform} className={buttonClass('secondary', 'lg')}>
                {h.secondaryCta}
              </SectionLink>
            </div>
          </Reveal>
          <Reveal delay={320}>
            <ul className="mt-9 flex flex-wrap items-center justify-center gap-x-6 gap-y-2.5">
              {[h.points.rbac, h.points.audit, h.points.responsive].map((point) => (
                <li key={point} className="flex items-center gap-2 text-[13.5px] text-lp-muted">
                  <span className="flex h-4 w-4 items-center justify-center rounded-full bg-lp-ok/10 text-lp-ok">
                    <Check size={11} strokeWidth={3} aria-hidden />
                  </span>
                  {point}
                </li>
              ))}
            </ul>
          </Reveal>
        </div>

        <Reveal delay={200} className="mx-auto mt-16 max-w-6xl sm:mt-20 lg:mb-28">
          <DashboardPreview />
        </Reveal>
      </div>
    </section>
  )
}
