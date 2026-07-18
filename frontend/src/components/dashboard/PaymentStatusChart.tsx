import { Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { Card } from '@/components/ui/Card'
import { PAYMENT_STATUS } from '@/data/mock/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

const SLICE_COLORS = ['var(--color-primary)', 'var(--color-accent)', 'var(--color-warning)', 'var(--color-danger)']

// Cell is deprecated in Recharts v3 — per-slice color now comes from a
// `fill` field directly on each datum, which Legend/Tooltip also read.
const data = PAYMENT_STATUS.map((entry, index) => ({ ...entry, fill: SLICE_COLORS[index % SLICE_COLORS.length] }))

export function PaymentStatusChart() {
  return (
    <Card title="Status Pembayaran" description="Distribusi tagihan mahasiswa semester ini">
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <PieChart>
            <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} />
            <Legend
              verticalAlign="bottom"
              height={36}
              formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>}
            />
            <Pie
              data={data}
              dataKey="value"
              nameKey="status"
              innerRadius={55}
              outerRadius={80}
              paddingAngle={2}
              strokeWidth={0}
              isAnimationActive={false}
            />
          </PieChart>
        </ResponsiveContainer>
      </div>
    </Card>
  )
}
