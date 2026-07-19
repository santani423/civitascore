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
import { classSectionService } from '@/services/academicService'
import type { ClassSection } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatNumber } from '@/utils/formatters'

export function ClassSectionsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['study_program_id', 'academic_term_id', 'is_active']),
  )
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<ClassSection>({ fetcher: classSectionService.index, initialFilter })

  const columns: DataTableColumn<ClassSection>[] = [
    {
      header: 'Kelas',
      cell: (row) => (
        <Link to={`${ROUTES.akademik.kelasJadwal}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.course_name}</p>
          <p className="text-xs text-ink-tertiary">{row.class_code}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
    { header: 'Kapasitas', cell: (row) => formatNumber(row.capacity) },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kelas dan Jadwal"
        description="Daftar kelas perkuliahan pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Kelas dan Jadwal' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama mata kuliah atau kode kelas..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada kelas.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="kelas" />
      </Card>
    </div>
  )
}
