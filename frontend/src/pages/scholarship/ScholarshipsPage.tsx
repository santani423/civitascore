import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { scholarshipService } from '@/services/scholarshipService'
import type { Scholarship } from '@/types/scholarship'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatNumber } from '@/utils/formatters'

export function ScholarshipsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['is_active']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Scholarship>({ fetcher: scholarshipService.index, initialFilter })

  const columns: DataTableColumn<Scholarship>[] = [
    {
      header: 'Beasiswa',
      cell: (row) => (
        <Link to={`${ROUTES.keuangan.beasiswa}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.academic_year}</p>
        </Link>
      ),
    },
    { header: 'Penyedia', cell: (row) => row.provider },
    { header: 'Pendaftar', cell: (row) => formatNumber(row.applications_count ?? 0) },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Beasiswa"
        description="Daftar program beasiswa pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keuangan' }, { label: 'Beasiswa' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama beasiswa atau penyedia..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada program beasiswa.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="beasiswa" />
      </Card>
    </div>
  )
}
