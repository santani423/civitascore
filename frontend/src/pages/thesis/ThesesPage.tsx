import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { thesisService } from '@/services/thesisService'
import type { Thesis, ThesisStatus } from '@/types/thesis'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const STATUS_LABEL: Record<ThesisStatus, string> = {
  proposal: 'Pengajuan Judul',
  bimbingan: 'Bimbingan',
  seminar_proposal: 'Seminar Proposal',
  penelitian: 'Penelitian',
  sidang: 'Sidang',
  selesai: 'Selesai',
}

const STATUS_VARIANT: Record<ThesisStatus, BadgeVariant> = {
  proposal: 'neutral',
  bimbingan: 'info',
  seminar_proposal: 'info',
  penelitian: 'warning',
  sidang: 'warning',
  selesai: 'success',
}

export function ThesesPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['status', 'thesis_type']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Thesis>({ fetcher: thesisService.index, initialFilter })

  const columns: DataTableColumn<Thesis>[] = [
    {
      header: 'Judul',
      cell: (row) => (
        <Link to={`${ROUTES.skripsi}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.title}</p>
          <p className="text-xs text-ink-tertiary">
            {row.student_name ?? '-'} · {row.student_nim ?? '-'}
          </p>
        </Link>
      ),
    },
    { header: 'Pembimbing', cell: (row) => row.supervisor_name ?? '-' },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Skripsi"
        description="Daftar skripsi mahasiswa pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Skripsi' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Judul skripsi..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Button variant="outline" onClick={() => list.setSearch(searchInput)}>
            Cari
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada data skripsi.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="skripsi" />
      </Card>
    </div>
  )
}
