import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { ProgramCount } from '@/types/dashboard'
import { formatNumber } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'
import { datumOf } from '@/components/dashboard/chartClick'

export interface FacultyDistributionChartProps {
  data: ProgramCount[]
  onBarClick?: (point: ProgramCount) => void
}

export function FacultyDistributionChart({ data, onBarClick }: FacultyDistributionChartProps) {
  return (
    <Card title="Mahasiswa per Program Studi" description="Distribusi mahasiswa berdasarkan program studi — klik batang untuk melihat daftarnya">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada data mahasiswa" description="Data akan muncul setelah mahasiswa terdaftar." />
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
                width={48}
              />
              <Tooltip content={<ChartTooltip valueFormatter={formatNumber} />} cursor={{ fill: 'var(--color-surface-hover)' }} />
              <Bar
                dataKey="total"
                name="Mahasiswa"
                fill="var(--color-accent)"
                radius={[6, 6, 0, 0]}
                isAnimationActive={false}
                cursor={onBarClick ? 'pointer' : undefined}
                onClick={(entry) => {
                  const point = datumOf<ProgramCount>(entry)
                  if (point && onBarClick) onBarClick(point)
                }}
              />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
