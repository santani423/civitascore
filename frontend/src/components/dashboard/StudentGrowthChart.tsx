import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { YEARLY_STUDENT_COUNTS } from '@/data/mock/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export function StudentGrowthChart() {
  return (
    <Card title="Jumlah Mahasiswa per Tahun" description="Tren pertumbuhan 5 tahun terakhir">
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <AreaChart data={YEARLY_STUDENT_COUNTS} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
            <defs>
              <linearGradient id="studentGrowthFill" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor="var(--color-primary)" stopOpacity={0.25} />
                <stop offset="100%" stopColor="var(--color-primary)" stopOpacity={0} />
              </linearGradient>
            </defs>
            <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
            <XAxis dataKey="year" tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }} axisLine={false} tickLine={false} />
            <YAxis
              tickFormatter={(value: number) => formatNumber(value)}
              tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }}
              axisLine={false}
              tickLine={false}
              width={56}
            />
            <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} />
            <Area
              type="monotone"
              dataKey="students"
              name="Mahasiswa"
              stroke="var(--color-primary)"
              strokeWidth={2}
              fill="url(#studentGrowthFill)"
              isAnimationActive={false}
            />
          </AreaChart>
        </ResponsiveContainer>
      </div>
    </Card>
  )
}
