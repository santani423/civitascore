import { Link } from 'react-router-dom'
import { ArrowRight, MessageSquare } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { useAppEntry } from '../../hooks/useAppEntry'
import { Crest } from '../layout/Wordmark'

export function FinalCTA() {
  const { m } = useLandingI18n()
  const openContact = useLandingUi((s) => s.openContact)
  const entry = useAppEntry()

  return (
    <section aria-labelledby="cta-title" className="pb-16 pt-4 sm:pb-20 lg:pb-24">
      <div className="lp-container">
        <Reveal>
          <div className="lp-on-brand relative isolate overflow-hidden rounded-lp-xl bg-lp-brand px-6 py-10 shadow-lp-lg sm:px-10 sm:py-12 lg:px-14">
            {/* Decorative: brighter orange sweep + concentric rings behind the crest. */}
            {/* Kept clear of the copy column so white body text stays above 4.5:1. */}
            <div aria-hidden data-lp-decor className="absolute inset-y-0 right-0 -z-10 hidden w-[28%] bg-gradient-to-l from-lp-accent/80 to-transparent lg:block" />
            <div aria-hidden data-lp-decor className="absolute -right-24 -top-24 -z-10 h-80 w-80 rounded-full border-[28px] border-white/10 lg:-right-10" />
            <div aria-hidden data-lp-decor className="absolute -bottom-32 right-40 -z-10 h-64 w-64 rounded-full border-[18px] border-white/[0.07]" />

            <div className="flex flex-col gap-10 lg:flex-row lg:items-center lg:justify-between">
              <div className="max-w-2xl">
                <h2 id="cta-title" className="lp-h2 text-lp-onbrand">
                  {m.cta.title}
                </h2>
                <p className="lp-lead mt-4 text-lp-onbrand">{m.cta.description}</p>
                <div className="mt-8 flex flex-col gap-3 min-[420px]:flex-row min-[420px]:flex-wrap">
                  <Link to={entry.to} className={buttonClass('band', 'lg')}>
                    {entry.authenticated ? m.nav.openDashboard : m.cta.primary}
                    <ArrowRight size={18} aria-hidden />
                  </Link>
                  <Button variant="bandOutline" size="lg" onClick={openContact}>
                    <MessageSquare size={17} aria-hidden />
                    {m.cta.secondary}
                  </Button>
                </div>
              </div>
              <div aria-hidden className="hidden shrink-0 lg:block">
                <span className="flex h-40 w-40 items-center justify-center rounded-full bg-white shadow-lp-xl ring-8 ring-white/20">
                  <Crest className="h-28" />
                </span>
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
