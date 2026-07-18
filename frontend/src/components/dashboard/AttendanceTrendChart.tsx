import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { ATTENDANCE_TREND } from '@/data/mock/dashboard'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export function AttendanceTrendChart() {
  return (
    <Card title="Kehadiran Mahasiswa" description="Persentase hadir vs. tidak hadir per bulan">
      <div className="h-64">
        <ResponsiveContainer width="100%" height="100%">
          <BarChart data={ATTENDANCE_TREND} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
            <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
            <XAxis dataKey="month" tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }} axisLine={false} tickLine={false} />
            <YAxis
              tickFormatter={(value: number) => `${value}%`}
              tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }}
              axisLine={false}
              tickLine={false}
              width={40}
            />
            <Tooltip content={<ChartTooltip valueFormatter={(value) => `${value}%`} />} cursor={{ fill: 'var(--color-surface-hover)' }} />
            <Legend formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>} />
            <Bar dataKey="present" name="Hadir" stackId="attendance" fill="var(--color-primary)" radius={[6, 6, 0, 0]} isAnimationActive={false} />
            <Bar
              dataKey="absent"
              name="Tidak Hadir"
              stackId="attendance"
              fill="var(--color-danger)"
              radius={[6, 6, 0, 0]}
              isAnimationActive={false}
            />
          </BarChart>
        </ResponsiveContainer>
      </div>
    </Card>
  )
}
