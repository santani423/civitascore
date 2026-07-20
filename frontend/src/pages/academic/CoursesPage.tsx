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
import { courseService } from '@/services/academicService'
import type { Course } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

export function CoursesPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['study_program_id', 'curriculum_id', 'semester_level', 'is_active']),
  )
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Course>({ fetcher: courseService.index, initialFilter })

  const columns: DataTableColumn<Course>[] = [
    {
      header: 'Mata Kuliah',
      cell: (row) => (
        <Link to={`${ROUTES.akademik.mataKuliah}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.code}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Kurikulum', cell: (row) => row.curriculum_name ?? '-' },
    { header: 'SKS', cell: (row) => row.credits },
    { header: 'Semester', cell: (row) => row.semester_level },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Mata Kuliah"
        description="Daftar mata kuliah pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Mata Kuliah' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama atau kode mata kuliah..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada mata kuliah.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="mata kuliah" />
      </Card>
    </div>
  )
}
