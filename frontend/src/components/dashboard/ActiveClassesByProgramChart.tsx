import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { ProgramCount } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export interface ActiveClassesByProgramChartProps {
  data: ProgramCount[]
}

export function ActiveClassesByProgramChart({ data }: ActiveClassesByProgramChartProps) {
  return (
    <Card title="Kelas Aktif per Program Studi" description="Jumlah kelas aktif pada tahun akademik dan semester berjalan">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada kelas aktif" description="Data akan muncul setelah kelas dijadwalkan pada periode berjalan." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <BarChart data={data} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis
                dataKey="program"
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
                width={40}
              />
              <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} cursor={{ fill: 'var(--color-surface-hover)' }} />
              <Bar dataKey="total" name="Kelas Aktif" fill="var(--color-accent)" radius={[6, 6, 0, 0]} isAnimationActive={false} />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
