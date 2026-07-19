import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { invoiceService } from '@/services/financeService'
import type { Invoice, InvoiceStatus } from '@/types/finance'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatCurrencyIDR } from '@/utils/formatters'

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

/** Opsi filter — termasuk gabungan "Belum Lunas" (unpaid+partial), target link kartu "Tagihan Belum Dibayar" di dashboard. */
const STATUS_OPTIONS = [
  { value: 'unpaid,partial', label: 'Belum Lunas (semua)' },
  { value: 'unpaid', label: 'Belum Dibayar' },
  { value: 'partial', label: 'Dibayar Sebagian' },
  { value: 'paid', label: 'Lunas' },
]

export function InvoicesPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['status', 'student_id']))
  const [searchInput, setSearchInput] = useState('')
  const [statusInput, setStatusInput] = useState(initialFilter.status ?? '')

  const list = usePaginatedList<Invoice>({ fetcher: invoiceService.index, initialFilter })

  const applyFilters = () => {
    list.setSearch(searchInput)
    const next = { ...list.filter }
    delete next.status
    if (statusInput) next.status = statusInput
    list.setFilter(next)
  }

  const columns: DataTableColumn<Invoice>[] = [
    {
      header: 'Tagihan',
      cell: (row) => (
        <Link to={`${ROUTES.keuangan.tagihan}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.student_name ?? row.student_id}</p>
          <p className="text-xs text-ink-tertiary">
            {row.student_nim ?? '-'} · {row.period}
          </p>
        </Link>
      ),
    },
    { header: 'Jumlah', cell: (row) => formatCurrencyIDR(Number(row.amount)) },
    { header: 'Terbayar', cell: (row) => formatCurrencyIDR(Number(row.paid_amount)) },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
    { header: 'Jatuh Tempo', cell: (row) => row.due_date },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Tagihan"
        description="Daftar tagihan mahasiswa pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keuangan' }, { label: 'Tagihan' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Periode, mis. 2026/2027 Ganjil..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Select
            label="Status"
            value={statusInput}
            onChange={(event) => setStatusInput(event.target.value)}
            options={STATUS_OPTIONS}
            placeholder="Semua status"
          />
          <Button variant="outline" onClick={applyFilters}>
            Terapkan Filter
          </Button>
        </div>

        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada tagihan.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="tagihan" />
      </Card>
    </div>
  )
}
