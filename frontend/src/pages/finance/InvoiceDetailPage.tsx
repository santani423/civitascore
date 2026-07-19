import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { invoiceService } from '@/services/financeService'
import type { InvoiceStatus, Payment } from '@/types/finance'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR, humanizeSlug } from '@/utils/formatters'

const STATUS_LABEL: Record<InvoiceStatus, string> = {
  paid: 'Lunas',
  partial: 'Dibayar Sebagian',
  unpaid: 'Belum Dibayar',
}

const STATUS_VARIANT: Record<InvoiceStatus, BadgeVariant> = {
  paid: 'success',
  partial: 'warning',
  unpaid: 'danger',
}

const PAYMENT_COLUMNS: DataTableColumn<Payment>[] = [
  { header: 'Tanggal', cell: (row) => row.paid_at },
  { header: 'Jumlah', cell: (row) => formatCurrencyIDR(Number(row.amount)) },
  { header: 'Metode', cell: (row) => humanizeSlug(row.method) },
]

export function InvoiceDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canSeeStudent = usePermission('students.read')

  const getInvoice = useCallback(() => invoiceService.show(id ?? ''), [id])
  const { data: invoice, isLoading, error } = useFetch(getInvoice)

  const remaining = invoice ? Number(invoice.amount) - Number(invoice.paid_amount) : 0

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Tagihan"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Tagihan', path: ROUTES.keuangan.tagihan },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.keuangan.tagihan)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {invoice && (
        <>
          <Card title={invoice.student_name ?? invoice.student_id} description={`${invoice.student_nim ?? '-'} · ${invoice.period}`}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
              <div>
                <p className="text-ink-tertiary">Status</p>
                <Badge variant={STATUS_VARIANT[invoice.status]}>{STATUS_LABEL[invoice.status]}</Badge>
              </div>
              <div>
                <p className="text-ink-tertiary">Jumlah Tagihan</p>
                <p className="font-medium text-ink-primary">{formatCurrencyIDR(Number(invoice.amount))}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Terbayar</p>
                <p className="font-medium text-ink-primary">{formatCurrencyIDR(Number(invoice.paid_amount))}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Sisa Tagihan</p>
                <p className="font-medium text-ink-primary">{formatCurrencyIDR(remaining)}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Jatuh Tempo</p>
                <p className="font-medium text-ink-primary">{invoice.due_date}</p>
              </div>
            </div>

            {canSeeStudent && (
              <div className="mt-5 border-t border-border pt-4">
                <Link to={`${ROUTES.mahasiswa}/${invoice.student_id}`}>
                  <Button variant="outline">Lihat Profil Mahasiswa</Button>
                </Link>
              </div>
            )}
          </Card>

          <Card title="Riwayat Pembayaran" noPadding>
            <DataTable
              columns={PAYMENT_COLUMNS}
              data={invoice.payments ?? []}
              rowKey={(row) => row.id}
              emptyMessage="Belum ada pembayaran untuk tagihan ini."
            />
          </Card>
        </>
      )}
    </div>
  )
}
