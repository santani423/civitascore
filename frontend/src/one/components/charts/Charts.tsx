import { Bar, BarChart, CartesianGrid, LabelList, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'

/**
 * Chart wrappers. Every chart here is single-series, so it uses slot 1 of
 * the validated palette (`--one-viz-1`) and no legend — the card title
 * names the series. Grid/axis colors come from CSS tokens so charts follow
 * light/dark without re-rendering. Each chart is paired with a table view
 * by the calling page for accessibility.
 */

interface TipProps {
  active?: boolean
  payload?: ReadonlyArray<{ value?: unknown }>
  label?: unknown
  format: (v: number) => string
  seriesLabel: string
}

function ChartTip({ active, payload, label, format, seriesLabel }: TipProps) {
  if (!active || !payload?.length) return null
  const v = Number(payload[0].value)
  return (
    <div className="rounded-xl border border-one-line bg-one-elev px-3 py-2 shadow-overlay">
      <p className="text-[12px] text-one-subtle">{String(label)}</p>
      <p className="mt-0.5 flex items-center gap-2 text-[13px] text-one-fg">
        <span aria-hidden className="h-2 w-2 rounded-full" style={{ background: 'var(--one-viz-1)' }} />
        <span className="text-one-muted">{seriesLabel}</span>
        <span className="font-semibold tabular">{format(v)}</span>
      </p>
    </div>
  )
}

interface TrendProps {
  data: Array<Record<string, string | number>>
  x: string
  y: string
  seriesLabel: string
  domain?: [number, number]
  format?: (v: number) => string
  height?: number
  ariaLabel: string
}

export function TrendLine({ data, x, y, seriesLabel, domain, format = (v) => String(v), height = 240, ariaLabel }: TrendProps) {
  const last = data.length - 1
  return (
    <div role="img" aria-label={ariaLabel} style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data} margin={{ top: 24, right: 28, bottom: 4, left: 0 }}>
          <CartesianGrid vertical={false} strokeWidth={1} />
          <XAxis dataKey={x} tickLine={false} axisLine={false} tickMargin={10} />
          <YAxis domain={domain} tickLine={false} axisLine={false} tickMargin={8} tickFormatter={format} width={48} />
          <Tooltip
            cursor={{ stroke: 'var(--one-viz-axis)', strokeWidth: 1 }}
            content={(p) => <ChartTip active={p.active} payload={p.payload} label={p.label} format={format} seriesLabel={seriesLabel} />}
          />
          <Line
            type="monotone"
            dataKey={y}
            stroke="var(--one-viz-1)"
            strokeWidth={2}
            dot={{ r: 4, strokeWidth: 2, stroke: 'rgb(var(--one-card))', fill: 'var(--one-viz-1)' }}
            activeDot={{ r: 6, strokeWidth: 2, stroke: 'rgb(var(--one-card))', fill: 'var(--one-viz-1)' }}
            isAnimationActive={false}
          >
            {/* Direct-label only the latest point — not every value. */}
            <LabelList
              dataKey={y}
              content={(props) => {
                if (props.index !== last) return null
                return (
                  <text x={Number(props.x)} y={Number(props.y) - 12} textAnchor="middle" className="fill-one-fg text-[12px] font-semibold">
                    {format(Number(props.value))}
                  </text>
                )
              }}
            />
          </Line>
        </LineChart>
      </ResponsiveContainer>
    </div>
  )
}

interface BarsProps {
  data: Array<Record<string, string | number>>
  x: string
  y: string
  seriesLabel: string
  layout?: 'columns' | 'rows'
  domain?: [number, number]
  format?: (v: number) => string
  height?: number
  ariaLabel: string
  categoryWidth?: number
}

export function Bars({
  data,
  x,
  y,
  seriesLabel,
  layout = 'columns',
  domain,
  format = (v) => String(v),
  height = 260,
  ariaLabel,
  categoryWidth = 150,
}: BarsProps) {
  const rows = layout === 'rows'
  return (
    <div role="img" aria-label={ariaLabel} style={{ height }}>
      <ResponsiveContainer width="100%" height="100%">
        <BarChart
          data={data}
          layout={rows ? 'vertical' : 'horizontal'}
          margin={rows ? { top: 4, right: 44, bottom: 4, left: 4 } : { top: 16, right: 8, bottom: 4, left: -8 }}
          barCategoryGap={rows ? 8 : '28%'}
        >
          <CartesianGrid vertical={rows} horizontal={!rows} strokeWidth={1} />
          {rows ? (
            <>
              <XAxis type="number" domain={domain} tickLine={false} axisLine={false} tickFormatter={format} />
              <YAxis type="category" dataKey={x} tickLine={false} axisLine={false} width={categoryWidth} />
            </>
          ) : (
            <>
              <XAxis dataKey={x} tickLine={false} axisLine={false} tickMargin={10} interval={0} />
              <YAxis domain={domain} tickLine={false} axisLine={false} tickFormatter={format} width={48} />
            </>
          )}
          <Tooltip
            cursor={{ fill: 'var(--one-viz-grid)', opacity: 0.5 }}
            content={(p) => <ChartTip active={p.active} payload={p.payload} label={p.label} format={format} seriesLabel={seriesLabel} />}
          />
          <Bar dataKey={y} fill="var(--one-viz-1)" radius={rows ? [0, 4, 4, 0] : [4, 4, 0, 0]} maxBarSize={rows ? 22 : 40} isAnimationActive={false}>
            {rows && (
              <LabelList dataKey={y} position="right" formatter={(v: unknown) => format(Number(v))} className="fill-one-muted text-[12px]" />
            )}
          </Bar>
        </BarChart>
      </ResponsiveContainer>
    </div>
  )
}
