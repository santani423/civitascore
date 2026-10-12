import { ArrowRight, Mail, MessageSquare } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { landingConfig } from '../../config'
import { useLandingUi } from '../../store/ui'
import { Button } from '../ui/Button'
import { buttonClass } from '../ui/buttonClass'
import { Reveal } from '../ui/Primitives'
import { LogoMark } from '../layout/Wordmark'

export function FinalCTA() {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)
  const { contactEmail } = landingConfig

  return (
    <section aria-labelledby="cta-title" className="lp-section">
      <div className="lp-container">
        <Reveal>
          <div className="lp-on-band relative overflow-hidden rounded-lp-xl bg-lp-band px-6 py-16 text-center shadow-lp-xl sm:px-12 sm:py-20 lg:py-24">
            <div aria-hidden className="lp-band-glow pointer-events-none absolute inset-0" />
            <div aria-hidden className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full border border-lp-band-fg/10" />
            <div aria-hidden className="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full border border-lp-band-fg/10" />
            <div aria-hidden className="pointer-events-none absolute -bottom-28 -left-16 h-80 w-80 rounded-full border border-lp-band-fg/5" />

            <div className="relative mx-auto max-w-3xl">
              <LogoMark size={48} className="mx-auto" />
              <p className="lp-eyebrow mt-7 text-lp-gold">{m.cta.eyebrow}</p>
              <h2 id="cta-title" className="lp-h2 mt-4 text-lp-band-fg">
                {m.cta.title}
              </h2>
              <p className="lp-lead mx-auto mt-5 max-w-2xl text-lp-band-muted">{m.cta.description}</p>
              <div className="mt-10 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                <Button variant="band" size="lg" onClick={() => openRequest('demo')}>
                  {m.cta.primary}
                  <ArrowRight size={17} aria-hidden />
                </Button>
                {contactEmail ? (
                  <a href={`mailto:${contactEmail}`} className={buttonClass('bandOutline', 'lg')}>
                    <Mail size={17} aria-hidden />
                    {m.cta.secondary}
                  </a>
                ) : (
                  <Button variant="bandOutline" size="lg" onClick={() => openRequest('contact')}>
                    <MessageSquare size={17} aria-hidden />
                    {m.cta.secondary}
                  </Button>
                )}
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
