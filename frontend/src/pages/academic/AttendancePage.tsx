import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { attendanceService } from '@/services/academicService'
import type { Attendance, AttendanceStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const STATUS_LABEL: Record<AttendanceStatus, string> = {
  present: 'Hadir',
  permitted: 'Izin',
  sick: 'Sakit',
  absent: 'Alpa',
}

const STATUS_VARIANT: Record<AttendanceStatus, BadgeVariant> = {
  present: 'success',
  permitted: 'info',
  sick: 'warning',
  absent: 'danger',
}

export function AttendancePage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['student_id', 'class_section_id', 'status']))

  const list = usePaginatedList<Attendance>({ fetcher: attendanceService.index, initialFilter })

  const columns: DataTableColumn<Attendance>[] = [
    { header: 'Tanggal', cell: (row) => row.meeting_date },
    { header: 'Pertemuan', cell: (row) => `Ke-${row.meeting_number}` },
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.student_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.student_nim ?? '-'}</p>
        </div>
      ),
    },
    {
      header: 'Mata Kuliah',
      cell: (row) => (
        <div>
          <p className="text-ink-primary">{row.course_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.course_code ?? '-'}</p>
        </div>
      ),
    },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Absensi"
        description="Riwayat kehadiran mahasiswa per pertemuan kelas."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Absensi' }]}
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada data absensi.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="data absensi" />
      </Card>
    </div>
  )
}
