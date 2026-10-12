import { useEffect, useRef, useState } from 'react'
import {
  AppWindow,
  Archive,
  Combine,
  Eye,
  FileBarChart,
  FileSpreadsheet,
  FileText,
  HeartHandshake,
  LayoutDashboard,
  ListChecks,
  Mail,
  MessagesSquare,
  MonitorSmartphone,
  Route,
  SearchCheck,
  TriangleAlert,
} from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'
import type { Messages } from '../../i18n/messages/id'
import { useInView } from '../../hooks/useInView'
import { useMediaQuery } from '../../hooks/useMediaQuery'
import { prefersReducedMotion } from '../../hooks/useScroll'
import { SECTION_IDS } from '../../paths'
import { Reveal, SectionHeader } from '../ui/Primitives'
import { LogoMark } from '../layout/Wordmark'

type SourceKey = keyof Messages['benefits']['sources']
type OutputKey = keyof Messages['benefits']['outputs']
type Point = [x: number, y: number, rotate?: number]

const SOURCES: Array<{ key: SourceKey; icon: typeof Mail; warn?: boolean }> = [
  { key: 'spreadsheet', icon: FileSpreadsheet, warn: true },
  { key: 'email', icon: Mail },
  { key: 'paper', icon: FileText, warn: true },
  { key: 'apps', icon: AppWindow },
  { key: 'chat', icon: MessagesSquare, warn: true },
  { key: 'archive', icon: Archive },
]
const OUTPUTS: Array<{ key: OutputKey; icon: typeof Mail }> = [
  { key: 'dashboard', icon: LayoutDashboard },
  { key: 'reports', icon: FileBarChart },
  { key: 'services', icon: HeartHandshake },
]

/** Chip positions (% of the diagram box) per layout and state. */
const LAYOUT = {
  wide: {
    before: [[20, 20, -5], [56, 12, 4], [84, 30, -3], [18, 60, 3], [52, 50, -4], [80, 76, 5]] as Point[],
    after: [[17, 14], [17, 28.5], [17, 43], [17, 57.5], [17, 72], [17, 86.5]] as Point[],
    hub: [52, 50] as Point,
    outputs: [[85, 30], [85, 50], [85, 70]] as Point[],
  },
  narrow: {
    before: [[30, 10, -4], [72, 19, 4], [28, 35, 3], [70, 47, -3], [32, 63, -2], [68, 79, 4]] as Point[],
    after: [[27, 9], [73, 9], [27, 21], [73, 21], [27, 33], [73, 33]] as Point[],
    hub: [50, 56] as Point,
    outputs: [[29, 82], [71, 82], [50, 93]] as Point[],
  },
}
const BROKEN_LINKS: Array<[number, number]> = [
  [0, 1],
  [3, 4],
  [2, 5],
]

const BENEFITS: Array<{ key: keyof Messages['benefits']['items']; icon: typeof Mail }> = [
  { key: 'structured', icon: ListChecks },
  { key: 'fragmentation', icon: Combine },
  { key: 'consistent', icon: MonitorSmartphone },
  { key: 'access', icon: SearchCheck },
  { key: 'transparency', icon: Eye },
  { key: 'transformation', icon: Route },
]

function curve([x1, y1]: Point, [x2, y2]: Point, vertical: boolean) {
  if (vertical) {
    const my = (y1 + y2) / 2
    return `M${x1} ${y1} C ${x1} ${my}, ${x2} ${my}, ${x2} ${y2}`
  }
  const mx = (x1 + x2) / 2
  return `M${x1} ${y1} C ${mx} ${y1}, ${mx} ${y2}, ${x2} ${y2}`
}

export function BenefitsSection() {
  const { m } = useLandingI18n()
  const b = m.benefits
  const wide = useMediaQuery('(min-width: 640px)')
  const layout = wide ? LAYOUT.wide : LAYOUT.narrow
  const [state, setState] = useState<'before' | 'after'>('before')
  const touched = useRef(false)
  const [diagramRef, inView] = useInView<HTMLDivElement>({ threshold: 0.45 })
  const after = state === 'after'

  // Play the transformation once when the diagram is first seen (unless the visitor already chose).
  useEffect(() => {
    if (!inView || touched.current) return
    if (prefersReducedMotion()) {
      setState('after')
      return
    }
    const timer = window.setTimeout(() => {
      if (!touched.current) setState('after')
    }, 900)
    return () => window.clearTimeout(timer)
  }, [inView])

  const choose = (next: 'before' | 'after') => {
    touched.current = true
    setState(next)
  }

  return (
    <section id={SECTION_IDS.benefits} aria-labelledby="benefits-title" className="lp-section">
      <div className="lp-container">
        <SectionHeader id="benefits-title" eyebrow={b.eyebrow} title={b.title} description={b.description} />

        <div className="mt-14 grid gap-12 lg:grid-cols-12 lg:gap-14">
          <Reveal className="lg:col-span-7">
            <div ref={diagramRef} className="overflow-hidden rounded-lp-xl border border-lp-line bg-lp-surface shadow-lp-md">
              <div className="flex flex-wrap items-center justify-between gap-3 border-b border-lp-line px-5 py-3.5">
                <div role="group" aria-label={b.toggleLabel} className="inline-flex rounded-full border border-lp-line bg-lp-sunken p-1">
                  {(['before', 'after'] as const).map((key) => (
                    <button
                      key={key}
                      type="button"
                      aria-pressed={state === key}
                      onClick={() => choose(key)}
                      className={cn(
                        'h-8 rounded-full px-4 text-[13px] font-semibold transition-[background-color,color,box-shadow] duration-200',
                        state === key ? 'bg-lp-surface text-lp-fg shadow-lp-sm ring-1 ring-lp-line' : 'text-lp-muted hover:text-lp-fg',
                      )}
                    >
                      {key === 'before' ? b.before : b.after}
                    </button>
                  ))}
                </div>
              </div>

              <div className={cn('lp-dots relative', wide ? 'aspect-[4/3]' : 'aspect-[3/4]')} aria-hidden>
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" className="absolute inset-0 h-full w-full">
                  {after
                    ? [
                        ...layout.after.map((p, i) => (
                          <path key={`s${i}`} d={curve(p, layout.hub, !wide)} fill="none" vectorEffect="non-scaling-stroke" strokeWidth={1.5} className="lp-flow-line stroke-lp-brand/60" />
                        )),
                        ...layout.outputs.map((p, i) => (
                          <path key={`o${i}`} d={curve(layout.hub, p, !wide)} fill="none" vectorEffect="non-scaling-stroke" strokeWidth={1.5} className="lp-flow-line stroke-lp-gold" />
                        )),
                      ]
                    : BROKEN_LINKS.map(([a, z]) => {
                        const [x1, y1] = layout.before[a]
                        const [x2, y2] = layout.before[z]
                        return (
                          <line
                            key={`${a}-${z}`}
                            x1={x1}
                            y1={y1}
                            x2={x1 + (x2 - x1) * 0.55}
                            y2={y1 + (y2 - y1) * 0.55}
                            vectorEffect="non-scaling-stroke"
                            strokeWidth={1.25}
                            strokeDasharray="3 4"
                            className="stroke-lp-strong"
                          />
                        )
                      })}
                </svg>

                {SOURCES.map((src, i) => {
                  const [x, y, r = 0] = after ? layout.after[i] : layout.before[i]
                  const Icon = src.icon
                  return (
                    <span
                      key={src.key}
                      className={cn(
                        'absolute flex items-center gap-1.5 whitespace-nowrap rounded-full border px-3 py-1.5 text-[12px] font-medium shadow-lp-sm transition-[left,top,transform,background-color,border-color,color] duration-700 ease-[cubic-bezier(0.2,0.7,0.2,1)]',
                        after ? 'border-lp-line bg-lp-surface text-lp-fg' : 'border-dashed border-lp-strong bg-lp-canvas text-lp-muted',
                      )}
                      style={{ left: `${x}%`, top: `${y}%`, transform: `translate(-50%, -50%) rotate(${r}deg)` }}
                    >
                      <Icon size={13} className={after ? 'text-lp-brand-text' : 'text-lp-subtle'} />
                      {b.sources[src.key]}
                      {src.warn && !after && <TriangleAlert size={12} className="text-lp-warn" />}
                    </span>
                  )
                })}

                <div
                  className={cn(
                    'absolute flex flex-col items-center gap-1.5 rounded-lp-lg border border-lp-brand/30 bg-lp-surface px-4 py-3 shadow-lp-lg transition-[opacity,transform] duration-700',
                    after ? 'scale-100 opacity-100' : 'scale-90 opacity-0',
                  )}
                  style={{ left: `${layout.hub[0]}%`, top: `${layout.hub[1]}%`, translate: '-50% -50%' }}
                >
                  <LogoMark size={36} />
                  <span className="whitespace-nowrap font-display text-[12.5px] font-bold text-lp-fg">{m.common.brand}</span>
                </div>

                {OUTPUTS.map((out, i) => {
                  const [x, y] = layout.outputs[i]
                  const Icon = out.icon
                  return (
                    <span
                      key={out.key}
                      className={cn(
                        'absolute flex items-center gap-1.5 whitespace-nowrap rounded-full bg-lp-brand px-3 py-1.5 text-[12px] font-semibold text-lp-onbrand shadow-lp-md transition-[opacity,transform] duration-700',
                        after ? 'scale-100 opacity-100' : 'scale-75 opacity-0',
                      )}
                      style={{ left: `${x}%`, top: `${y}%`, translate: '-50% -50%', transitionDelay: after ? `${250 + i * 90}ms` : '0ms' }}
                    >
                      <Icon size={13} />
                      {b.outputs[out.key]}
                    </span>
                  )
                })}
              </div>
              {/* Both captions share one grid cell so switching never changes the height (no layout shift). */}
              <div className="grid border-t border-lp-line px-5 py-4 text-[13.5px] leading-relaxed text-lp-muted" aria-live="polite">
                <p className={cn('col-start-1 row-start-1', after && 'invisible')} aria-hidden={after}>
                  {b.beforeCaption}
                </p>
                <p className={cn('col-start-1 row-start-1', !after && 'invisible')} aria-hidden={!after}>
                  {b.afterCaption}
                </p>
              </div>
            </div>
          </Reveal>

          <ol className="space-y-1 lg:col-span-5">
            {BENEFITS.map(({ key, icon: Icon }, i) => (
              <li key={key}>
                <Reveal delay={i * 60} className="flex gap-4 rounded-lp-lg p-4 transition-colors hover:bg-lp-surface">
                  <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lp border border-lp-line bg-lp-surface text-lp-brand-text">
                    <Icon size={18} aria-hidden />
                  </span>
                  <div>
                    <h3 className="lp-h4 text-lp-fg">{b.items[key].title}</h3>
                    <p className="lp-small mt-1 text-lp-muted">{b.items[key].body}</p>
                  </div>
                </Reveal>
              </li>
            ))}
          </ol>
        </div>
      </div>
    </section>
  )
}
