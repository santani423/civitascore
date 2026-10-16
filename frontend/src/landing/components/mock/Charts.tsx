import { useState, type KeyboardEvent, type PointerEvent } from 'react'
import { cn } from '@/utils/cn'
import { useElementWidth } from '../../hooks/useElementWidth'

/**
 * Lightweight SVG charts for the dashboard mockups (no chart library on the
 * landing bundle). Colors come from the validated `--lp-viz-*` tokens; text
 * always uses ink tokens, never the series color.
 */

export type VizSlot = 1 | 2 | 3

const strokeClass: Record<VizSlot, string> = { 1: 'stroke-lp-viz1', 2: 'stroke-lp-viz2', 3: 'stroke-lp-viz3' }
const fillClass: Record<VizSlot, string> = { 1: 'fill-lp-viz1', 2: 'fill-lp-viz2', 3: 'fill-lp-viz3' }
const bgClass: Record<VizSlot, string> = { 1: 'bg-lp-viz1', 2: 'bg-lp-viz2', 3: 'bg-lp-viz3' }

export interface LineSeries {
  key: string
  label: string
  values: number[]
  slot: VizSlot
}

export function Legend({ items, className }: { items: Array<{ key: string; label: string; slot: VizSlot }>; className?: string }) {
  return (
    <ul className={cn('flex flex-wrap items-center gap-x-4 gap-y-1', className)}>
      {items.map((item) => (
        <li key={item.key} className="flex items-center gap-1.5 text-[11.5px] text-lp-muted">
          <span aria-hidden className={cn('h-2 w-2 rounded-full', bgClass[item.slot])} />
          {item.label}
        </li>
      ))}
    </ul>
  )
}

interface LineChartProps {
  series: LineSeries[]
  labels: string[]
  /** Longer label used in the tooltip (defaults to `labels`). */
  tooltipLabels?: string[]
  height?: number
  min?: number
  max: number
  ticks: number[]
  formatTick: (v: number) => string
  formatValue: (v: number) => string
  area?: boolean
  interactive?: boolean
  ariaLabel?: string
  className?: string
}

const PAD = { top: 10, right: 10, bottom: 24, left: 40 }

export function LineChart({
  series,
  labels,
  tooltipLabels,
  height = 200,
  min = 0,
  max,
  ticks,
  formatTick,
  formatValue,
  area = true,
  interactive = true,
  ariaLabel,
  className,
}: LineChartProps) {
  const [ref, width] = useElementWidth<HTMLDivElement>()
  const [hover, setHover] = useState<number | null>(null)
  const count = labels.length
  const innerW = Math.max(0, width - PAD.left - PAD.right)
  const innerH = height - PAD.top - PAD.bottom
  const step = count > 1 ? innerW / (count - 1) : 0
  const x = (i: number) => PAD.left + i * step
  const y = (v: number) => PAD.top + innerH - ((v - min) / (max - min)) * innerH
  const labelEvery = step > 0 && step < 34 ? Math.ceil(34 / step) : 1

  const indexAt = (clientX: number, rect: DOMRect) => {
    if (step === 0) return 0
    const i = Math.round((clientX - rect.left - PAD.left) / step)
    return Math.max(0, Math.min(count - 1, i))
  }

  const onMove = (e: PointerEvent<SVGRectElement>) => {
    const svg = e.currentTarget.ownerSVGElement
    if (svg) setHover(indexAt(e.clientX, svg.getBoundingClientRect()))
  }

  const onKey = (e: KeyboardEvent<HTMLDivElement>) => {
    if (e.key === 'ArrowRight') setHover((h) => Math.min(count - 1, (h ?? -1) + 1))
    else if (e.key === 'ArrowLeft') setHover((h) => Math.max(0, (h ?? count) - 1))
    else if (e.key === 'Escape') setHover(null)
    else return
    e.preventDefault()
  }

  const tipLeft = hover !== null ? Math.min(Math.max(x(hover), 70), width - 70) : 0

  return (
    <div
      ref={ref}
      className={cn('relative w-full select-none', interactive && 'rounded-lp-sm', className)}
      style={{ height }}
      tabIndex={interactive ? 0 : undefined}
      role={interactive ? 'group' : undefined}
      aria-label={interactive ? ariaLabel : undefined}
      onKeyDown={interactive ? onKey : undefined}
      onBlur={interactive ? () => setHover(null) : undefined}
    >
      {width > 0 && (
        <svg width={width} height={height} aria-hidden className="block overflow-visible">
          {ticks.map((t) => (
            <g key={t}>
              <line x1={PAD.left} x2={width - PAD.right} y1={y(t)} y2={y(t)} className="stroke-lp-line" strokeWidth={1} shapeRendering="crispEdges" />
              <text x={PAD.left - 8} y={y(t)} dy="0.32em" textAnchor="end" className="fill-lp-subtle lp-tabular" fontSize={10.5}>
                {formatTick(t)}
              </text>
            </g>
          ))}
          {labels.map((label, i) =>
            (count - 1 - i) % labelEvery === 0 ? (
              <text key={label + i} x={x(i)} y={height - 6} textAnchor="middle" className="fill-lp-subtle" fontSize={10.5}>
                {label}
              </text>
            ) : null,
          )}
          {series.map((s) => {
            const points = s.values.map((v, i) => `${x(i)},${y(v)}`).join(' ')
            return (
              <g key={s.key}>
                {area && (
                  <polygon
                    points={`${x(0)},${y(min)} ${points} ${x(count - 1)},${y(min)}`}
                    className={cn(fillClass[s.slot], 'opacity-[0.09]')}
                  />
                )}
                <polyline points={points} fill="none" className={strokeClass[s.slot]} strokeWidth={2} strokeLinejoin="round" strokeLinecap="round" />
              </g>
            )
          })}
          {hover !== null && (
            <g>
              <line x1={x(hover)} x2={x(hover)} y1={PAD.top} y2={PAD.top + innerH} className="stroke-lp-strong" strokeWidth={1} strokeDasharray="3 3" />
              {series.map((s) => (
                <circle key={s.key} cx={x(hover)} cy={y(s.values[hover])} r={4.5} className={cn(fillClass[s.slot], 'stroke-lp-surface')} strokeWidth={2} />
              ))}
            </g>
          )}
          {interactive && (
            <rect
              x={PAD.left - step / 2}
              y={0}
              width={innerW + step}
              height={height}
              fill="transparent"
              onPointerMove={onMove}
              onPointerDown={onMove}
              onPointerLeave={() => setHover(null)}
            />
          )}
        </svg>
      )}
      {hover !== null && (
        <div
          className="pointer-events-none absolute top-0 z-10 min-w-[8.5rem] -translate-x-1/2 rounded-lp-sm border border-lp-line bg-lp-elev px-3 py-2 shadow-lp-lg"
          style={{ left: tipLeft }}
          aria-live="polite"
        >
          <p className="text-[11px] font-semibold text-lp-fg">{(tooltipLabels ?? labels)[hover]}</p>
          <ul className="mt-1 space-y-0.5">
            {series.map((s) => (
              <li key={s.key} className="flex items-center justify-between gap-3 text-[11px]">
                <span className="flex items-center gap-1.5 text-lp-muted">
                  <span aria-hidden className={cn('h-1.5 w-1.5 rounded-full', bgClass[s.slot])} />
                  {s.label}
                </span>
                <span className="lp-tabular font-semibold text-lp-fg">{formatValue(s.values[hover])}</span>
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  )
}

/** Axis-free trend line for tiles and cards. */
export function Sparkline({ values, slot = 1, className, area = true }: { values: number[]; slot?: VizSlot; className?: string; area?: boolean }) {
  const lo = Math.min(...values)
  const hi = Math.max(...values)
  const span = hi - lo || 1
  const pts = values.map((v, i) => `${(i / (values.length - 1)) * 100},${36 - ((v - lo) / span) * 30 - 3}`).join(' ')
  return (
    <svg viewBox="0 0 100 36" preserveAspectRatio="none" aria-hidden className={cn('block h-9 w-full overflow-visible', className)}>
      {area && <polygon points={`0,36 ${pts} 100,36`} className={cn(fillClass[slot], 'opacity-10')} />}
      <polyline points={pts} fill="none" className={strokeClass[slot]} strokeWidth={2} vectorEffect="non-scaling-stroke" strokeLinejoin="round" strokeLinecap="round" />
    </svg>
  )
}

/** Horizontal bars with direct labels (single series). */
export function BarList({ items, format, className }: { items: Array<{ key: string; label: string; value: number }>; format: (v: number) => string; className?: string }) {
  const max = Math.max(...items.map((i) => i.value))
  const [active, setActive] = useState<string | null>(null)
  return (
    <ul className={cn('space-y-2.5', className)} onPointerLeave={() => setActive(null)}>
      {items.map((item) => (
        <li key={item.key} className="group" onPointerEnter={() => setActive(item.key)}>
          <div className="flex items-baseline justify-between gap-3 text-[12px]">
            <span className={cn('truncate transition-colors', active === item.key ? 'text-lp-fg' : 'text-lp-muted')}>{item.label}</span>
            <span className="lp-tabular shrink-0 font-semibold text-lp-fg">{format(item.value)}</span>
          </div>
          <div className="mt-1.5 h-2 overflow-hidden rounded-full bg-lp-sunken">
            <div
              className={cn('h-full rounded-full bg-lp-viz1 transition-opacity duration-200', active && active !== item.key && 'opacity-45')}
              style={{ width: `${(item.value / max) * 100}%` }}
            />
          </div>
        </li>
      ))}
    </ul>
  )
}

/** Vertical bars with value-on-hover and category labels underneath (single series). */
export function Columns({
  items,
  format,
  height = 96,
  className,
  highlightLast,
}: {
  items: Array<{ key: string; label: string; value: number }>
  format: (v: number) => string
  height?: number
  className?: string
  highlightLast?: boolean
}) {
  const max = Math.max(...items.map((i) => i.value))
  const [active, setActive] = useState<number | null>(null)
  return (
    <div className={className} onPointerLeave={() => setActive(null)}>
      <div className="flex items-end gap-1.5" style={{ height }}>
        {items.map((item, i) => {
          const emphasized = active === null ? !highlightLast || i === items.length - 1 : active === i
          return (
            <div key={item.key} className="relative flex h-full flex-1 items-end" onPointerEnter={() => setActive(i)}>
              {active === i && (
                <span className="lp-tabular absolute -top-5 left-1/2 -translate-x-1/2 whitespace-nowrap text-[10.5px] font-semibold text-lp-fg">{format(item.value)}</span>
              )}
              <div
                className={cn('w-full rounded-t-[4px] bg-lp-viz1 transition-opacity duration-200', emphasized ? 'opacity-100' : 'opacity-35')}
                style={{ height: `${Math.max(4, (item.value / max) * 100)}%` }}
              />
            </div>
          )
        })}
      </div>
      <div className="mt-1.5 flex gap-1.5 border-t border-lp-line pt-1.5">
        {items.map((item) => (
          <span key={item.key} className="flex-1 truncate text-center text-[10.5px] text-lp-subtle">
            {item.label}
          </span>
        ))}
      </div>
    </div>
  )
}

/** Part-to-whole ring (≤ 3 segments) with a 2px surface gap between segments. */
export function Donut({
  items,
  format,
  size = 132,
  centerLabel,
}: {
  items: Array<{ key: string; label: string; value: number; slot: VizSlot }>
  format: (v: number) => string
  size?: number
  centerLabel?: string
}) {
  const [active, setActive] = useState<string | null>(null)
  const total = items.reduce((s, i) => s + i.value, 0)
  const r = 50
  const c = 2 * Math.PI * r
  const gap = 2.5
  let offset = 0
  const focus = items.find((i) => i.key === active) ?? items[0]

  return (
    <div className="flex flex-wrap items-center gap-5">
      <div className="relative shrink-0" style={{ width: size, height: size }} onPointerLeave={() => setActive(null)}>
        <svg viewBox="0 0 120 120" width={size} height={size} aria-hidden className="-rotate-90">
          <circle cx={60} cy={60} r={r} fill="none" className="stroke-lp-sunken" strokeWidth={14} />
          {items.map((item) => {
            const len = (item.value / total) * c
            const dash = `${Math.max(0, len - gap)} ${c - Math.max(0, len - gap)}`
            const el = (
              <circle
                key={item.key}
                cx={60}
                cy={60}
                r={r}
                fill="none"
                className={cn(strokeClass[item.slot], 'transition-[stroke-width,opacity] duration-200', active && active !== item.key && 'opacity-40')}
                strokeWidth={active === item.key ? 17 : 14}
                strokeDasharray={dash}
                strokeDashoffset={-offset}
                onPointerEnter={() => setActive(item.key)}
              />
            )
            offset += len
            return el
          })}
        </svg>
        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
          <span className="lp-tabular font-display text-[22px] font-bold text-lp-fg">{format(focus.value)}</span>
          <span className="max-w-[80px] text-[10.5px] leading-tight text-lp-subtle">{centerLabel ?? focus.label}</span>
        </div>
      </div>
      <ul className="min-w-0 flex-1 space-y-2">
        {items.map((item) => (
          <li
            key={item.key}
            className={cn('flex items-center justify-between gap-3 rounded-md px-1.5 py-1 text-[12px] transition-colors', active === item.key && 'bg-lp-sunken')}
            onPointerEnter={() => setActive(item.key)}
            onPointerLeave={() => setActive(null)}
          >
            <span className="flex min-w-0 items-center gap-2 text-lp-muted">
              <span aria-hidden className={cn('h-2 w-2 shrink-0 rounded-full', bgClass[item.slot])} />
              <span className="truncate">{item.label}</span>
            </span>
            <span className="lp-tabular font-semibold text-lp-fg">{format(item.value)}</span>
          </li>
        ))}
      </ul>
    </div>
  )
}
