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
import { curriculumService } from '@/services/academicService'
import type { Curriculum } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatNumber } from '@/utils/formatters'

export function CurriculumsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['study_program_id', 'is_active']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Curriculum>({ fetcher: curriculumService.index, initialFilter })

  const columns: DataTableColumn<Curriculum>[] = [
    {
      header: 'Kurikulum',
      cell: (row) => (
        <Link to={`${ROUTES.akademik.kurikulum}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.academic_year}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Mata Kuliah', cell: (row) => formatNumber(row.courses_count ?? 0) },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kurikulum"
        description="Daftar kurikulum per program studi pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Kurikulum' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama kurikulum atau tahun ajaran..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada kurikulum.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="kurikulum" />
      </Card>
    </div>
  )
}
