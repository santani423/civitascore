import { Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { ApprovalStatusCount } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'
import { datumOf } from '@/components/dashboard/chartClick'

const STATUS_COLORS: Record<string, string> = {
  Disetujui: 'var(--color-primary)',
  Ditolak: 'var(--color-danger)',
  Menunggu: 'var(--color-warning)',
}

export interface ApprovalStatusChartProps {
  data: ApprovalStatusCount[]
  onSliceClick?: (slice: ApprovalStatusCount) => void
}

export function ApprovalStatusChart({ data, onSliceClick }: ApprovalStatusChartProps) {
  const chartData = data.map((entry) => ({ ...entry, fill: STATUS_COLORS[entry.status] ?? 'var(--color-accent)' }))
  const hasData = chartData.some((entry) => entry.total > 0)

  return (
    <Card title="Status Pengajuan Persetujuan" description="Disetujui, ditolak, dan masih menunggu">
      <div className="h-64">
        {!hasData ? (
          <EmptyState title="Belum ada pengajuan" description="Data akan muncul setelah ada pengajuan persetujuan." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} />
              <Legend
                verticalAlign="bottom"
                height={36}
                formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>}
              />
              <Pie
                data={chartData}
                dataKey="total"
                nameKey="status"
                innerRadius={55}
                outerRadius={80}
                paddingAngle={2}
                strokeWidth={0}
                isAnimationActive={false}
                cursor={onSliceClick ? 'pointer' : undefined}
                onClick={(entry) => {
                  const slice = datumOf<ApprovalStatusCount>(entry)
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
