import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { PaymentTrendPoint } from '@/types/dashboard'
import { formatCurrencyIDR } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'

export interface PaymentTrendChartProps {
  data: PaymentTrendPoint[]
}

export function PaymentTrendChart({ data }: PaymentTrendChartProps) {
  const chartData = data.map((point) => ({ month: point.month, total: Number(point.total) }))

  return (
    <Card title="Tren Pembayaran" description="Total pembayaran tagihan diterima per bulan">
      <div className="h-64">
        {chartData.length === 0 ? (
          <EmptyState title="Belum ada data pembayaran" description="Data akan muncul setelah ada pembayaran tercatat." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={chartData} margin={{ top: 4, right: 8, left: 0, bottom: 0 }}>
              <defs>
                <linearGradient id="paymentTrendFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stopColor="var(--color-primary)" stopOpacity={0.25} />
                  <stop offset="100%" stopColor="var(--color-primary)" stopOpacity={0} />
                </linearGradient>
              </defs>
              <CartesianGrid strokeDasharray="3 3" stroke="var(--color-border)" vertical={false} />
              <XAxis dataKey="month" tick={{ fill: 'var(--color-text-secondary)', fontSize: 12 }} axisLine={false} tickLine={false} />
              <YAxis
                tickFormatter={(value: number) => formatCurrencyIDR(value)}
                tick={{ fill: 'var(--color-text-secondary)', fontSize: 11 }}
                axisLine={false}
                tickLine={false}
                width={72}
              />
              <Tooltip content={<ChartTooltip valueFormatter={formatCurrencyIDR} />} />
              <Area
                type="monotone"
                dataKey="total"
                name="Pembayaran"
                stroke="var(--color-primary)"
                strokeWidth={2}
                fill="url(#paymentTrendFill)"
                isAnimationActive={false}
              />
            </AreaChart>
          </ResponsiveContainer>
        )}
      </div>
    </Card>
  )
}
