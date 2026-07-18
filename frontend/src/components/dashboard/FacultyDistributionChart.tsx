import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { FACULTY_DISTRIBUTION } from '@/data/mock/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export function FacultyDistributionChart() {
  return (
    <Card title="Mahasiswa per Fakultas" description="Distribusi mahasiswa aktif semester ini">
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={FACULTY_DISTRIBUTION} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
            <XAxis
              dataKey="faculty"
              tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }}
              axisLine={false}
              tickLine={false}
              interval={0}
              angle={-20}
              textAnchor="end"
              height={50}
            />
            <YAxis
              tickFormatter={(value: number) => formatNumber(value)}
              tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }}
              axisLine={false}
              tickLine={false}
              width={48}
            />
            <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} cursor={{ fill: 'var(--color-surface-hover)' }} />
            <Bar dataKey="students" name="Mahasiswa" fill="var(--color-accent)" radius={[6, 6, 0, 0]} isAnimationActive={false} />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </Card>
  )
}
