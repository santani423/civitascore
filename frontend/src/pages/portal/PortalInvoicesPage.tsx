import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR } from '@/utils/formatters'

const INVOICES = [
  { id: '1', period: '2026/2027 Ganjil', amount: 8_500_000, paidAmount: 0, status: 'unpaid' as const },
  { id: '2', period: '2025/2026 Genap', amount: 8_000_000, paidAmount: 4_000_000, status: 'partial' as const },
  { id: '3', period: '2025/2026 Ganjil', amount: 8_000_000, paidAmount: 8_000_000, status: 'paid' as const },
]

const INVOICE_STATUS_LABEL: Record<(typeof INVOICES)[number]['status'], string> = {
  unpaid: 'Belum Dibayar',
  partial: 'Dibayar Sebagian',
  paid: 'Lunas',
}

const INVOICE_STATUS_VARIANT: Record<(typeof INVOICES)[number]['status'], BadgeVariant> = {
  unpaid: 'danger',
  partial: 'warning',
  paid: 'success',
}

const PAYMENT_HISTORY = [
  { id: '1', paidAt: '2026-02-10', amount: 8_000_000, method: 'Transfer Bank' },
  { id: '2', paidAt: '2026-06-05', amount: 4_000_000, method: 'Virtual Account' },
  { id: '3', paidAt: '2025-08-12', amount: 8_000_000, method: 'Transfer Bank' },
]

const TOTAL_INVOICE = INVOICES.reduce((sum, invoice) => sum + invoice.amount, 0)
const TOTAL_PAID = INVOICES.reduce((sum, invoice) => sum + invoice.paidAmount, 0)
const TOTAL_REMAINING = TOTAL_INVOICE - TOTAL_PAID
const NEXT_DUE_DATE = '2026-08-15'

const INVOICE_COLUMNS: DataTableColumn<(typeof INVOICES)[number]>[] = [
  { header: 'Periode', cell: (row) => row.period },
  { header: 'Jumlah Tagihan', cell: (row) => formatCurrencyIDR(row.amount) },
  { header: 'Terbayar', cell: (row) => formatCurrencyIDR(row.paidAmount) },
  {
    header: 'Status',
    cell: (row) => <Badge variant={INVOICE_STATUS_VARIANT[row.status]}>{INVOICE_STATUS_LABEL[row.status]}</Badge>,
  },
  {
    header: 'Aksi',
    cell: (row) =>
      row.status !== 'paid' ? (
        <Button variant="primary" size="sm">
          Bayar Sekarang
        </Button>
      ) : null,
  },
]

const PAYMENT_COLUMNS: DataTableColumn<(typeof PAYMENT_HISTORY)[number]>[] = [
  { header: 'Tanggal Bayar', cell: (row) => row.paidAt },
  { header: 'Jumlah', cell: (row) => formatCurrencyIDR(row.amount) },
  { header: 'Metode', cell: (row) => row.method },
]

export function PortalInvoicesPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Tagihan" breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Tagihan' }]} />

      <Card>
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-ink-tertiary">Total Tagihan Semester Ini</p>
            <p className="font-medium text-ink-primary">{formatCurrencyIDR(TOTAL_INVOICE)}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Sudah Dibayar</p>
            <p className="font-medium text-ink-primary">{formatCurrencyIDR(TOTAL_PAID)}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Sisa Tagihan</p>
            <p className={TOTAL_REMAINING > 0 ? 'font-semibold text-danger' : 'font-medium text-ink-primary'}>
              {formatCurrencyIDR(TOTAL_REMAINING)}
            </p>
          </div>
          <div>
            <p className="text-ink-tertiary">Jatuh Tempo</p>
            <p className="font-medium text-ink-primary">{NEXT_DUE_DATE}</p>
          </div>
        </div>
      </Card>

      <Card title="Tagihan Saya" noPadding>
        <DataTable columns={INVOICE_COLUMNS} data={INVOICES} rowKey={(row) => row.id} />
      </Card>

      <Card title="Riwayat Pembayaran" noPadding>
        <DataTable columns={PAYMENT_COLUMNS} data={PAYMENT_HISTORY} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
