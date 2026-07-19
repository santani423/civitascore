import { Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { StatusCount } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'
import { datumOf } from '@/components/dashboard/chartClick'

const SLICE_COLORS = [
  'var(--color-primary)',
  'var(--color-warning)',
  'var(--color-danger)',
  '#94a3b8',
  'var(--color-accent)',
]

export interface StudentStatusChartProps {
  data: StatusCount[]
  onSliceClick?: (slice: StatusCount) => void
}

export function StudentStatusChart({ data, onSliceClick }: StudentStatusChartProps) {
  // Cell is deprecated in Recharts v3 — per-slice color now comes from a
  // `fill` field directly on each datum, which Legend/Tooltip also read.
  const chartData = data.map((entry, index) => ({ ...entry, fill: SLICE_COLORS[index % SLICE_COLORS.length] }))

  return (
    <Card title="Distribusi Status Mahasiswa" description="Seluruh mahasiswa terdaftar berdasarkan status akademik">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada data mahasiswa" description="Data akan muncul setelah mahasiswa terdaftar." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} />
              <Legend
                verticalAlign="bottom"
                height={48}
                formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>}
              />
              <Pie
                data={chartData}
                dataKey="total"
                nameKey="status"
                outerRadius={80}
                paddingAngle={2}
                strokeWidth={0}
                isAnimationActive={false}
                cursor={onSliceClick ? 'pointer' : undefined}
                onClick={(entry) => {
                  const slice = datumOf<StatusCount>(entry)
                  if (slice && onSliceClick) onSliceClick(slice)
                }}
              />
            </PieChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
