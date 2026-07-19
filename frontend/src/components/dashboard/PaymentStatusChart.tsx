import { Legend, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts'
import { Card } from '@/components/ui/Card'
import { EmptyState } from '@/components/ui/EmptyState'
import type { InvoiceStatusSlice } from '@/types/dashboard'
import { formatCurrencyIDR } from '@/utils/formatters'
import { ChartTooltip } from '@/components/dashboard/ChartTooltip'
import { datumOf } from '@/components/dashboard/chartClick'

const STATUS_COLORS: Record<string, string> = {
  Lunas: 'var(--color-primary)',
  'Dibayar Sebagian': 'var(--color-warning)',
  'Belum Dibayar': 'var(--color-danger)',
}

export interface PaymentStatusChartProps {
  data: InvoiceStatusSlice[]
  onSliceClick?: (slice: InvoiceStatusSlice) => void
}

export function PaymentStatusChart({ data, onSliceClick }: PaymentStatusChartProps) {
  // Slice size represents total Rupiah per status, not invoice count — see
  // DashboardPage docs for why (the "unpaid_invoices" summary card already
  // covers the count; this chart is the one place currency formatting is
  // meaningful, per the dashboard requirements).
  const chartData = data.map((entry) => ({
    ...entry,
    amountValue: Number(entry.amount),
    fill: STATUS_COLORS[entry.status] ?? 'var(--color-accent)',
  }))

  return (
    <Card title="Status Pembayaran" description="Total tagihan (Rupiah) berdasarkan status pembayaran">
      <div className="h-64">
        {data.length === 0 ? (
          <EmptyState title="Belum ada data tagihan" description="Data akan muncul setelah tagihan diterbitkan." />
        ) : (
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Tooltip content={<ChartTooltip valueFormatter={formatCurrencyIDR} />} />
              <Legend
                verticalAlign="bottom"
                height={36}
                formatter={(value: string) => <span className="text-xs text-ink-secondary">{value}</span>}
              />
              <Pie
                data={chartData}
                dataKey="amountValue"
                nameKey="status"
                innerRadius={55}
                outerRadius={80}
                paddingAngle={2}
                strokeWidth={0}
                isAnimationActive={false}
                cursor={onSliceClick ? 'pointer' : undefined}
                onClick={(entry) => {
                  const slice = datumOf<InvoiceStatusSlice>(entry)
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
