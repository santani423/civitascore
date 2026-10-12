import { Blocks, ChartNoAxesCombined, Gauge, Layers, MonitorSmartphone, Workflow } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { Reveal, SectionHeader } from '../ui/Primitives'

const items: Array<{ key: keyof Messages['value']['items']; icon: typeof Workflow }> = [
  { key: 'integration', icon: Workflow },
  { key: 'efficiency', icon: Gauge },
  { key: 'centralized', icon: Layers },
  { key: 'consistent', icon: MonitorSmartphone },
  { key: 'data', icon: ChartNoAxesCombined },
  { key: 'longterm', icon: Blocks },
]

export function ValueProposition() {
  const { m } = useLandingI18n()
  return (
    <section aria-labelledby="value-title" className="lp-section border-t border-lp-line bg-lp-surface">
      <div className="lp-container grid gap-14 lg:grid-cols-12 lg:gap-10">
        <div className="lg:col-span-4">
          <div className="lg:sticky lg:top-28">
            <SectionHeader id="value-title" eyebrow={m.value.eyebrow} title={m.value.title} description={m.value.description} />
          </div>
        </div>
        <ul className="grid gap-x-10 sm:grid-cols-2 lg:col-span-8 lg:col-start-6">
          {items.map(({ key, icon: Icon }, i) => (
            <li key={key}>
              <Reveal delay={(i % 2) * 90} className="h-full border-t border-lp-line py-8">
                <span className="flex h-11 w-11 items-center justify-center rounded-lp border border-lp-line bg-lp-canvas text-lp-brand-text">
                  <Icon size={20} aria-hidden />
                </span>
                <h3 className="lp-h3 mt-5 text-lp-fg">{m.value.items[key].title}</h3>
                <p className="lp-body mt-2.5 text-lp-muted">{m.value.items[key].body}</p>
              </Reveal>
            </li>
          ))}
        </ul>
      </div>
    </section>
  )
}
