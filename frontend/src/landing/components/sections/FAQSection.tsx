import { MessageSquare } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { useLandingUi } from '../../store/ui'
import { SECTION_IDS } from '../../paths'
import { Accordion } from '../ui/Accordion'
import { Button } from '../ui/Button'
import { Reveal, SectionHeader } from '../ui/Primitives'

const ORDER: Array<keyof Messages['faq']['items']> = ['what', 'who', 'modules', 'customize', 'mobile', 'demo', 'security', 'integration']

export function FAQSection() {
  const { m } = useLandingI18n()
  const openRequest = useLandingUi((s) => s.openRequest)

  return (
    <section id={SECTION_IDS.faq} aria-labelledby="faq-title" className="lp-section border-t border-lp-line bg-lp-surface">
      <div className="lp-container grid gap-12 lg:grid-cols-12 lg:gap-16">
        <div className="lg:col-span-4">
          <div className="lg:sticky lg:top-28">
            <SectionHeader id="faq-title" eyebrow={m.faq.eyebrow} title={m.faq.title} description={m.faq.description} />
            <Reveal delay={100} className="mt-8">
              <Button variant="secondary" onClick={() => openRequest('contact')}>
                <MessageSquare size={16} aria-hidden />
                {m.faq.contactCta}
              </Button>
            </Reveal>
          </div>
        </div>
        <Reveal className="lg:col-span-8">
          <Accordion defaultOpen="what" items={ORDER.map((key) => ({ key, title: m.faq.items[key].q, content: m.faq.items[key].a }))} />
        </Reveal>
      </div>
    </section>
  )
}
