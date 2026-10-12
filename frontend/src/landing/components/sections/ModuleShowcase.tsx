import { useState } from 'react'
import { ArrowRight } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { MODULES, type ModuleDef, type ModuleKey } from '../../data/catalog'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader, StatusLegend, StatusPill } from '../ui/Primitives'
import { ModuleDialog } from '../ModuleDialog'
import { ModuleSnippet } from './ModuleSnippets'

/** Bento placement on large screens (6-column grid). */
const span: Record<ModuleKey, string> = {
  studentData: 'lg:col-span-3',
  lectures: 'lg:col-span-3',
  curriculum: 'lg:col-span-2',
  assessment: 'lg:col-span-2',
  people: 'lg:col-span-2',
  services: 'lg:col-span-2',
  finance: 'lg:col-span-2',
  admissions: 'lg:col-span-2',
  analytics: 'lg:col-span-3',
  governance: 'lg:col-span-3',
}

export function ModuleShowcase() {
  const { m } = useLandingI18n()
  const [open, setOpen] = useState<ModuleDef | null>(null)

  return (
    <section id={SECTION_IDS.modules} aria-labelledby="modules-title" className="lp-section border-t border-lp-line bg-lp-surface">
      <div className="lp-container">
        <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
          <SectionHeader id="modules-title" eyebrow={m.modules.eyebrow} title={m.modules.title} description={m.modules.description} />
          <Reveal className="lg:max-w-sm lg:pb-1">
            <StatusLegend className="flex-col items-start gap-y-2.5 [&>span:first-child]:mb-0.5" />
          </Reveal>
        </div>

        <ul className="mt-14 grid gap-4 md:grid-cols-2 lg:grid-cols-6 lg:gap-5">
          {MODULES.map((mod, i) => {
            const copy = m.modules.items[mod.key]
            const Icon = mod.icon
            const planned = mod.status === 'planned'
            return (
              <li key={mod.key} className={span[mod.key]}>
                <Reveal delay={(i % 3) * 70} className="h-full">
                  <article
                    className={cn(
                      'group relative flex h-full flex-col overflow-hidden rounded-lp-xl border bg-lp-canvas transition-[transform,box-shadow,border-color] duration-300 hover:-translate-y-1 hover:shadow-lp-lg',
                      planned ? 'border-dashed border-lp-strong' : 'border-lp-line hover:border-lp-strong',
                    )}
                  >
                    <div className="flex flex-1 flex-col p-6 sm:p-7">
                      <div className="flex items-start justify-between gap-3">
                        <span
                          className={cn(
                            'flex h-11 w-11 items-center justify-center rounded-lp transition-colors duration-300',
                            planned ? 'bg-lp-sunken text-lp-subtle' : 'bg-lp-brand/10 text-lp-brand-text group-hover:bg-lp-brand group-hover:text-lp-onbrand',
                          )}
                        >
                          <Icon size={20} aria-hidden />
                        </span>
                        <StatusPill status={mod.status} />
                      </div>
                      <h3 className="lp-h3 mt-5 text-lp-fg">{copy.name}</h3>
                      <p className="lp-small mt-2 text-lp-muted">{copy.summary}</p>
                      <button
                        type="button"
                        onClick={() => setOpen(mod)}
                        aria-haspopup="dialog"
                        className="mt-4 inline-flex items-center gap-1.5 self-start text-[14px] font-semibold text-lp-brand-text after:absolute after:inset-0 after:rounded-lp-xl after:content-['']"
                      >
                        {m.common.learnMore}
                        <span className="sr-only">: {copy.name}</span>
                        <ArrowRight size={15} aria-hidden className="transition-transform duration-300 group-hover:translate-x-1" />
                      </button>
                    </div>
                    <div aria-hidden className="relative mx-6 h-48 overflow-hidden rounded-t-lp-lg border border-b-0 border-lp-line bg-lp-sunken/60 p-4 sm:mx-7">
                      <ModuleSnippet moduleKey={mod.key} />
                      <div className="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-lp-canvas to-transparent" />
                    </div>
                  </article>
                </Reveal>
              </li>
            )
          })}
        </ul>
      </div>

      <ModuleDialog module={open} onClose={() => setOpen(null)} />
    </section>
  )
}
