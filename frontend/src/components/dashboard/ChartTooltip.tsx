interface ChartTooltipPayloadEntry {
  name?: string
  value?: number | string
  color?: string
}

export interface ChartTooltipProps {
  active?: boolean
  label?: string
  payload?: ChartTooltipPayloadEntry[]
  valueFormatter?: (value: number) => string
}

/** Shared Recharts tooltip so every chart on the dashboard looks the same and respects dark mode. */
export function ChartTooltip({ active, label, payload, valueFormatter }: ChartTooltipProps) {
  if (!active || !payload?.length) return null

  return (
    <div className="rounded-lg border border-border bg-surface px-3 py-2 text-xs shadow-popover">
      {label && <p className="mb-1 font-medium text-ink-primary">{label}</p>}
      {payload.map((entry) => (
        <div key={entry.name} className="flex items-center gap-1.5 text-ink-secondary">
          <span className="size-2 rounded-full" style={{ backgroundColor: entry.color }} />
          <span>{entry.name}:</span>
          <span className="font-medium text-ink-primary">
            {typeof entry.value === 'number' && valueFormatter ? valueFormatter(entry.value) : entry.value}
          </span>
        </div>
      ))}
    </div>
  )
}
