import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import { CAPABILITY_STATUS, ECOSYSTEM } from '../../data/catalog'
import { useLandingUi } from '../../store/ui'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader, StatusLegend, StatusPill } from '../ui/Primitives'
import { LogoMark } from '../layout/Wordmark'

const RADIUS = 37 // % of the diagram box
const PANEL_ID = 'platform-detail'

function nodePosition(index: number, total: number) {
  const angle = (-90 + (360 / total) * index) * (Math.PI / 180)
  return { x: 50 + RADIUS * Math.cos(angle), y: 50 + RADIUS * Math.sin(angle) }
}

export function PlatformOverview() {
  const { m } = useLandingI18n()
  const selected = useLandingUi((s) => s.platformCategory)
  const select = useLandingUi((s) => s.setPlatformCategory)
  const active = ECOSYSTEM.find((c) => c.key === selected) ?? ECOSYSTEM[0]
  const ActiveIcon = active.icon

  return (
    <section id={SECTION_IDS.platform} aria-labelledby="platform-title" className="lp-section relative overflow-hidden">
      <div className="lp-container">
        <SectionHeader id="platform-title" eyebrow={m.platform.eyebrow} title={m.platform.title} description={m.platform.description} align="center" />

        <div className="mt-14 grid items-center gap-10 lg:mt-16 lg:grid-cols-12 lg:gap-12">
          {/* Diagram */}
          <Reveal className="lg:col-span-7">
            <div role="group" aria-label={m.platform.diagramLabel}>
              {/* Orbit layout (sm and up) */}
              <div className="relative mx-auto hidden aspect-square w-full max-w-[580px] sm:block">
                <svg viewBox="0 0 100 100" aria-hidden className="absolute inset-0 h-full w-full overflow-visible">
                  <circle cx="50" cy="50" r={RADIUS} fill="none" className="stroke-lp-line" strokeWidth="0.3" strokeDasharray="1 1.2" />
                  <circle cx="50" cy="50" r="24" fill="none" className="stroke-lp-line" strokeWidth="0.25" />
                  {ECOSYSTEM.map((cat, i) => {
                    const p = nodePosition(i, ECOSYSTEM.length)
                    const on = cat.key === active.key
                    return (
                      <line
                        key={cat.key}
                        x1="50"
                        y1="50"
                        x2={p.x}
                        y2={p.y}
                        strokeWidth={on ? 0.55 : 0.3}
                        className={cn('transition-[stroke] duration-300', on ? 'lp-flow-line stroke-lp-brand' : 'stroke-lp-strong')}
                      />
                    )
                  })}
                </svg>

                <div className="absolute left-1/2 top-1/2 flex aspect-square w-[34%] -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-full border border-lp-line bg-lp-surface text-center shadow-lp-lg">
                  <span aria-hidden className="absolute inset-[-10px] rounded-full border border-lp-brand/15" />
                  <LogoMark size={44} />
                  <p className="mt-2.5 px-3 font-display text-[clamp(0.8rem,1.6vw,1rem)] font-bold leading-tight text-lp-fg">{m.platform.hub}</p>
                  <p className="mt-1 px-4 text-[clamp(0.65rem,1.2vw,0.75rem)] leading-tight text-lp-subtle">{m.platform.hubCaption}</p>
                </div>

                {ECOSYSTEM.map((cat, i) => {
                  const p = nodePosition(i, ECOSYSTEM.length)
                  const on = cat.key === active.key
                  const Icon = cat.icon
                  return (
                    <button
                      key={cat.key}
                      type="button"
                      aria-pressed={on}
                      aria-controls={PANEL_ID}
                      onClick={() => select(cat.key)}
                      style={{ left: `${p.x}%`, top: `${p.y}%` }}
                      className={cn(
                        'absolute flex w-[8.75rem] -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-2 rounded-lp-lg border px-3 py-3.5 text-center transition-[background-color,border-color,box-shadow,transform] duration-300 md:w-[9.5rem]',
                        on
                          ? 'scale-[1.04] border-lp-brand/50 bg-lp-surface shadow-lp-lg ring-4 ring-lp-brand/10'
                          : 'border-lp-line bg-lp-surface/90 shadow-lp-sm hover:-translate-y-[calc(50%+2px)] hover:border-lp-strong hover:shadow-lp-md',
                      )}
                    >
                      <span
                        className={cn(
                          'flex h-9 w-9 items-center justify-center rounded-lp transition-colors',
                          on ? 'bg-lp-brand text-lp-onbrand' : 'bg-lp-sunken text-lp-brand-text',
                        )}
                      >
                        <Icon size={18} aria-hidden />
                      </span>
                      <span className={cn('text-[12.5px] font-semibold leading-tight', on ? 'text-lp-fg' : 'text-lp-muted')}>{m.platform.categories[cat.key].name}</span>
                    </button>
                  )
                })}
              </div>

              {/* Compact layout (phones) */}
              <div className="sm:hidden">
                <div className="flex items-center gap-3 rounded-lp-lg border border-lp-line bg-lp-surface p-4 shadow-lp-sm">
                  <LogoMark size={40} />
                  <div>
                    <p className="font-display text-[15px] font-bold text-lp-fg">{m.platform.hub}</p>
                    <p className="text-[12.5px] text-lp-subtle">{m.platform.hubCaption}</p>
                  </div>
                </div>
                <div aria-hidden className="mx-auto h-5 w-px bg-lp-strong" />
                <div className="grid grid-cols-2 gap-2">
                  {ECOSYSTEM.map((cat) => {
                    const on = cat.key === active.key
                    const Icon = cat.icon
                    return (
                      <button
                        key={cat.key}
                        type="button"
                        aria-pressed={on}
                        aria-controls={PANEL_ID}
                        onClick={() => select(cat.key)}
                        className={cn(
                          'flex min-h-[3.5rem] items-center gap-2.5 rounded-lp border px-3 py-2.5 text-left transition-colors',
                          on ? 'border-lp-brand/50 bg-lp-brand/5' : 'border-lp-line bg-lp-surface',
                        )}
                      >
                        <Icon size={17} aria-hidden className={on ? 'shrink-0 text-lp-brand-text' : 'shrink-0 text-lp-subtle'} />
                        <span className={cn('text-[13px] font-semibold leading-tight', on ? 'text-lp-fg' : 'text-lp-muted')}>{m.platform.categories[cat.key].name}</span>
                      </button>
                    )
                  })}
                </div>
              </div>
            </div>
          </Reveal>

          {/* Detail */}
          <Reveal delay={120} className="lg:col-span-5">
            <div id={PANEL_ID} aria-live="polite" className="rounded-lp-xl border border-lp-line bg-lp-surface p-6 shadow-lp-md sm:p-8">
              <div key={active.key} className="lp-tab-enter">
                <div className="flex items-center gap-3">
                  <span className="flex h-11 w-11 items-center justify-center rounded-lp bg-lp-brand text-lp-onbrand">
                    <ActiveIcon size={21} aria-hidden />
                  </span>
                  <h3 className="lp-h3 text-lp-fg">{m.platform.categories[active.key].name}</h3>
                </div>
                <p className="lp-body mt-4 text-lp-muted">{m.platform.categories[active.key].body}</p>
                <p className="mt-7 text-[12px] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{m.platform.capabilitiesTitle}</p>
                <ul className="mt-3 divide-y divide-lp-line border-y border-lp-line">
                  {active.capabilities.map((cap) => (
                    <li key={cap} className="flex items-center justify-between gap-4 py-3">
                      <span className="text-[14.5px] text-lp-fg">{m.capabilities[cap]}</span>
                      <StatusPill status={CAPABILITY_STATUS[cap]} compact />
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </Reveal>
        </div>

        <Reveal className="mt-12 flex justify-center">
          <StatusLegend className="justify-center" />
        </Reveal>
      </div>
    </section>
  )
}
