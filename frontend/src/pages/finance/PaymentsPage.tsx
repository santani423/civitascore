import { useCallback, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { paymentService } from '@/services/financeService'
import type { ListParams } from '@/types/api'
import type { Payment } from '@/types/finance'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR, humanizeSlug } from '@/utils/formatters'

export function PaymentsPage() {
  const [searchParams] = useSearchParams()
  // Rentang tanggal dari link chart tren pembayaran (?date_from=...&date_to=...)
  // — nilai awal stabil (useState initializer), jadi fetcher di bawah tetap
  // referensi stabil sesuai kontrak usePaginatedList.
  const [dateRange] = useState(() => ({
    date_from: searchParams.get('date_from') ?? undefined,
    date_to: searchParams.get('date_to') ?? undefined,
  }))

  const fetcher = useCallback((params: ListParams) => paymentService.index({ ...params, ...dateRange }), [dateRange])
  const list = usePaginatedList<Payment>({ fetcher })

  const rangeLabel =
    dateRange.date_from || dateRange.date_to
      ? `Periode ${dateRange.date_from ?? '...'} s.d. ${dateRange.date_to ?? '...'}`
      : 'Seluruh pembayaran tagihan yang tercatat.'

  const columns: DataTableColumn<Payment>[] = [
    { header: 'Tanggal', cell: (row) => row.paid_at },
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.student_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.invoice_period ?? '-'}</p>
        </div>
      ),
    },
    { header: 'Jumlah', cell: (row) => formatCurrencyIDR(Number(row.amount)) },
    { header: 'Metode', cell: (row) => humanizeSlug(row.method) },
    {
      header: 'Tagihan',
      cell: (row) => (
        <Link to={`${ROUTES.keuangan.tagihan}/${row.invoice_id}`} className="font-medium text-primary hover:underline">
          Lihat Tagihan
        </Link>
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pembayaran"
        description={rangeLabel}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keuangan' }, { label: 'Pembayaran' }]}
      />

      <Card noPadding>
        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada pembayaran.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="pembayaran" />
      </Card>
    </div>
  )
}
