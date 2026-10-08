import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { classSectionService } from '@/services/academicService'
import type { ClassSection } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatNumber } from '@/utils/formatters'

// "Semua" sebagai opsi biasa (bukan placeholder Select yang disabled) supaya
// filter bisa dikembalikan tanpa memuat ulang halaman.
const LECTURER_OPTIONS = [
  { value: '', label: 'Semua' },
  { value: '1', label: 'Sudah ditetapkan' },
  { value: '0', label: 'Belum ditetapkan' },
]

export function ClassSectionsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['study_program_id', 'academic_term_id', 'is_active', 'has_lecturer']),
  )
  const [searchInput, setSearchInput] = useState('')
  const [hasLecturerInput, setHasLecturerInput] = useState(initialFilter.has_lecturer ?? '')

  const list = usePaginatedList<ClassSection>({ fetcher: classSectionService.index, initialFilter })

  const applyFilters = () => {
    list.setSearch(searchInput)
    list.setFilter((current) =>
      Object.fromEntries(Object.entries({ ...current, has_lecturer: hasLecturerInput }).filter(([, value]) => value !== '')),
    )
  }

  const columns: DataTableColumn<ClassSection>[] = [
    {
      header: 'Kelas',
      cell: (row) => (
        <Link to={`${ROUTES.akademik.kelasJadwal}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.course_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">
            {row.course_code ?? '-'} · {row.class_code}
          </p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
    {
      header: 'Dosen Pengampu',
      cell: (row) => row.lecturer?.name ?? <Badge variant="warning">Belum ditetapkan</Badge>,
    },
    {
      header: 'Jadwal',
      cell: (row) =>
        row.schedules.length > 0 ? (
          <div className="flex flex-col">
            {row.schedules.map((schedule) => (
              <span key={schedule.id} className="whitespace-nowrap">
                {schedule.day_label} {schedule.start_time}–{schedule.end_time}
                {schedule.room && <span className="text-ink-tertiary"> · {schedule.room}</span>}
              </span>
            ))}
          </div>
        ) : (
          <span className="text-ink-tertiary">Belum ada</span>
        ),
    },
    { header: 'Terisi/Kapasitas', cell: (row) => `${formatNumber(row.enrolled_count ?? 0)}/${formatNumber(row.capacity)}` },
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
            onKeyDown={(event) => {
              if (event.key === 'Enter') applyFilters()
            }}
          />
          <Select
            label="Dosen Pengampu"
            value={hasLecturerInput}
            onChange={(event) => setHasLecturerInput(event.target.value)}
            options={LECTURER_OPTIONS}
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada kelas.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="kelas" />
      </Card>
    </div>
  )
}
