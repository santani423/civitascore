import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { StaffByUnitPoint } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export interface StaffByUnitChartProps {
  data: StaffByUnitPoint[]
}

export function StaffByUnitChart({ data }: StaffByUnitChartProps) {
  return (
    <Card title="Dosen dan Pegawai per Unit Kerja" description="Sebaran dosen (per fakultas) dan pegawai (per unit)">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada data dosen/pegawai" description="Data akan muncul setelah dosen atau pegawai terdaftar." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis
                dataKey="unit"
                tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }}
                axisLine={false}
                tickLine={false}
                interval={0}
                angle={-20}
                textAnchor="end"
                height={60}
              />
              <YAxis
                tickFormatter={(value: number) => formatNumber(value)}
                tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }}
                axisLine={false}
                tickLine={false}
                width={40}
              />
              <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} cursor={{ fill: 'var(--color-surface-hover)' }} />
              <Legend formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>} />
              <Bar dataKey="lecturers" name="Dosen" fill="var(--color-primary)" radius={[6, 6, 0, 0]} isAnimationActive={false} />
              <Bar dataKey="employees" name="Pegawai" fill="var(--color-accent)" radius={[6, 6, 0, 0]} isAnimationActive={false} />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
