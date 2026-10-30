import { useId, type ReactNode } from 'react'
import { useLandingI18n } from '../../i18n'
import { useInView } from '../../hooks/useInView'

/*
 * Plain HTML charts: each plots one series in the single brand hue (validated
 * against both theme surfaces), with hairline grid and thin marks. Values are
 * never hover-only: lists print them, and column charts carry a table view.
 */

interface Datum {
  label: string
  value: number
}

/** Round axis top: 1, 2 or 5 × a power of ten, at or above `max`. */
function niceMax(max: number): number {
  const power = 10 ** Math.floor(Math.log10(max))
  const step = [1, 2, 5, 10].find((s) => s * power >= max) ?? 10
  return step * power
}

/** Change over time for one series; the latest column is labelled, the rest answer on hover and focus. */
export function ColumnChart({ title, caption, data, format, unit, axis }: { title: string; caption?: string; data: Datum[]; format: (n: number) => string; unit: string; axis: string }) {
  const { m } = useLandingI18n()
  const titleId = useId()
  const [ref, inView] = useInView<HTMLDivElement>()
  const top = niceMax(Math.max(...data.map((d) => d.value)))
  const ticks = [top, top / 2, 0]

  return (
    <figure aria-labelledby={titleId} className="flex h-full min-w-0 flex-col rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm sm:p-6">
      <figcaption>
        <p id={titleId} className="lp-h4 text-lp-fg">
          {title}
        </p>
        {caption && <p className="lp-small mt-1 text-lp-muted">{caption}</p>}
      </figcaption>

      {/* Fixed plot height plus the label band below it, so nothing clips. */}
      <div ref={ref} className="mt-6 flex flex-1 gap-3">
        <div aria-hidden className="lp-tabular flex h-52 w-12 shrink-0 flex-col justify-between text-right text-[0.75rem] text-lp-subtle">
          {ticks.map((tick) => (
            <span key={tick} className="-translate-y-1/2 first:translate-y-0 last:translate-y-0">
              {format(tick)}
            </span>
          ))}
        </div>
        <div className="relative min-w-0 flex-1">
          <div aria-hidden className="absolute inset-x-0 top-0 flex h-52 flex-col justify-between">
            {ticks.map((tick) => (
              <span key={tick} className="block h-px bg-lp-line" />
            ))}
          </div>
          <ul className="relative flex">
            {data.map((d, i) => {
              const last = i === data.length - 1
              return (
                <li key={d.label} className="min-w-0 flex-1">
                  <div tabIndex={0} role="img" aria-label={`${d.label}: ${format(d.value)} ${unit}`} className="group/bar relative flex h-52 items-end justify-center rounded-lp-sm outline-offset-2">
                    <span
                      aria-hidden
                      className="lp-grow block w-full max-w-6 rounded-t-[4px] bg-lp-brand transition-opacity group-hover/bar:opacity-80 group-focus/bar:opacity-80"
                      style={{ height: inView ? `${(d.value / top) * 100}%` : 0 }}
                    />
                    {last && (
                      <span aria-hidden className="absolute left-1/2 -translate-x-1/2 whitespace-nowrap text-[0.8125rem] font-semibold text-lp-fg group-hover/bar:opacity-0 group-focus/bar:opacity-0" style={{ bottom: `calc(${(d.value / top) * 100}% + 0.25rem)` }}>
                        {format(d.value)}
                      </span>
                    )}
                    <span
                      aria-hidden
                      className="pointer-events-none absolute left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded-lp-sm border border-lp-line bg-lp-elev px-2.5 py-1.5 text-center opacity-0 shadow-lp-md transition-opacity group-hover/bar:opacity-100 group-focus/bar:opacity-100"
                      style={{ bottom: `calc(${(d.value / top) * 100}% + 0.375rem)` }}
                    >
                      <span className="block text-[0.875rem] font-semibold text-lp-fg">{format(d.value)}</span>
                      <span className="block text-[0.75rem] text-lp-muted">{d.label}</span>
                    </span>
                  </div>
                  <p aria-hidden className="lp-tabular mt-2 text-center text-[0.75rem] text-lp-subtle">
                    {d.label}
                  </p>
                </li>
              )
            })}
          </ul>
        </div>
      </div>

      <details className="mt-4 border-t border-lp-line pt-3">
        <summary className="inline-flex min-h-11 cursor-pointer items-center text-[0.8125rem] font-semibold text-lp-brand-text">{m.alumni.charts.viewTable}</summary>
        <table className="mt-2 w-full text-left text-[0.8125rem]">
          <thead>
            <tr className="border-b border-lp-line text-lp-subtle">
              <th scope="col" className="py-2 font-medium">
                {axis}
              </th>
              <th scope="col" className="py-2 text-right font-medium">
                {unit}
              </th>
            </tr>
          </thead>
          <tbody>
            {data.map((d) => (
              <tr key={d.label} className="border-b border-lp-line last:border-0">
                <td className="py-2 text-lp-muted">{d.label}</td>
                <td className="lp-tabular py-2 text-right font-semibold text-lp-fg">{format(d.value)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </details>
    </figure>
  )
}

/** Ranked list of magnitudes: every row prints its value, so it doubles as its own table. */
export function BarList({ title, caption, data, format, lang }: { title: string; caption?: string; data: Datum[]; format: (n: number) => string; lang?: string }) {
  const titleId = useId()
  const [ref, inView] = useInView<HTMLDivElement>()
  const top = Math.max(...data.map((d) => d.value))

  return (
    <figure aria-labelledby={titleId} className="min-w-0 rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm sm:p-6">
      <figcaption>
        <p id={titleId} className="lp-h4 text-lp-fg">
          {title}
        </p>
        {caption && <p className="lp-small mt-1 text-lp-muted">{caption}</p>}
      </figcaption>
      <div ref={ref}>
        <ul className="mt-5 space-y-4">
          {data.map((d) => (
            <li key={d.label}>
              <div className="flex items-baseline justify-between gap-4 text-[0.875rem]">
                <span lang={lang} className="min-w-0 text-lp-muted">
                  {d.label}
                </span>
                <span className="lp-tabular shrink-0 font-semibold text-lp-fg">{format(d.value)}</span>
              </div>
              <div aria-hidden className="mt-1.5 h-2">
                <div className="lp-grow h-full rounded-r-[4px] bg-lp-brand" style={{ width: inView ? `${(d.value / top) * 100}%` : 0 }} />
              </div>
            </li>
          ))}
        </ul>
      </div>
    </figure>
  )
}

/** Progress against a target: the fill is the brand hue, its track a lighter step of the same ramp. */
export function Meter({ value, max, label, children }: { value: number; max: number; label: string; children?: ReactNode }) {
  const [ref, inView] = useInView<HTMLDivElement>()
  const pct = Math.min(100, (value / max) * 100)
  return (
    <div ref={ref}>
      <div role="progressbar" aria-label={label} aria-valuemin={0} aria-valuemax={max} aria-valuenow={value} className="h-2 overflow-hidden rounded-full bg-lp-brand-soft ring-1 ring-inset ring-lp-brand/10">
        <div className="lp-grow h-full rounded-full bg-lp-brand" style={{ width: inView ? `${pct}%` : 0 }} />
      </div>
      {children && <div className="mt-2 flex items-baseline justify-between gap-3 text-[0.8125rem] text-lp-muted">{children}</div>}
    </div>
  )
}
