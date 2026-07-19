import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { StudentGrowthPoint } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export interface StudentGrowthChartProps {
  data: StudentGrowthPoint[]
}

export function StudentGrowthChart({ data }: StudentGrowthChartProps) {
  return (
    <Card title="Jumlah Mahasiswa per Tahun" description="Tren pertumbuhan berdasarkan tahun angkatan">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada data mahasiswa" description="Data akan muncul setelah mahasiswa terdaftar." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
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
                dataKey="total"
                name="Mahasiswa"
                stroke="var(--color-primary)"
                strokeWidth={2}
                fill="url(#studentGrowthFill)"
                isAnimationActive={false}
              />
            </AreaChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
