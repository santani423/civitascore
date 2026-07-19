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
import { studentService } from '@/services/academicService'
import type { Student, StudentStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatDateRange, humanizeSlug } from '@/utils/formatters'

const STATUS_LABEL: Record<StudentStatus, string> = {
  active: 'Aktif',
  leave: 'Cuti',
  graduated: 'Lulus',
  inactive: 'Nonaktif',
  dropped_out: 'Drop Out',
}

const STATUS_VARIANT: Record<StudentStatus, BadgeVariant> = {
  active: 'success',
  leave: 'warning',
  graduated: 'info',
  inactive: 'neutral',
  dropped_out: 'danger',
}

export function StudentsPage() {
  const [searchParams] = useSearchParams()
  // Filter awal dari link kartu/chart dashboard (mis. ?status=active) —
  // dibaca sekali saat mount, lihat utils/listInitial.ts.
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['status', 'study_program_id', 'admission_year']))

  const [searchInput, setSearchInput] = useState('')
  const [statusInput, setStatusInput] = useState(initialFilter.status ?? '')

  const list = usePaginatedList<Student>({ fetcher: studentService.index, initialFilter })

  const applyFilters = () => {
    list.setSearch(searchInput)
    // Filter dimensi dari URL (prodi/angkatan) sengaja dipertahankan saat
    // user cuma mengubah status — cocok dengan judul halaman yang dia buka.
    const next = { ...list.filter }
    delete next.status
    if (statusInput) next.status = statusInput
    list.setFilter(next)
  }

  const columns: DataTableColumn<Student>[] = [
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <Link to={`${ROUTES.mahasiswa}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.nim}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Angkatan', cell: (row) => row.admission_year },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status] ?? humanizeSlug(row.status)}</Badge>,
    },
    { header: 'Email', cell: (row) => row.email ?? '-' },
    {
      header: 'Terdaftar',
      cell: (row) => (row.graduated_at ? formatDateRange(row.enrolled_at, row.graduated_at) : row.enrolled_at),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Mahasiswa"
        description="Daftar mahasiswa terdaftar pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Mahasiswa' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama atau NIM..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Select
            label="Status"
            value={statusInput}
            onChange={(event) => setStatusInput(event.target.value)}
            options={Object.entries(STATUS_LABEL).map(([value, label]) => ({ value, label }))}
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada mahasiswa terdaftar.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="mahasiswa" />
      </Card>
    </div>
  )
}
