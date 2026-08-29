import { useCallback } from 'react'
import { useNavigate } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Alert } from '@/components/ui/Alert'
import { SkeletonRow } from '@/components/ui/Skeleton'
import { useFetch } from '@/hooks/useFetch'
import { studentExamService } from '@/services/academicService'
import type { StudentExam, StudentExamStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

const STATUS_LABEL: Record<StudentExamStatus, string> = {
  upcoming: 'Belum Dimulai',
  available: 'Tersedia',
  in_progress: 'Sedang Dikerjakan',
  completed: 'Selesai',
  expired: 'Berakhir',
}

const STATUS_VARIANT: Record<StudentExamStatus, BadgeVariant> = {
  upcoming: 'neutral',
  available: 'info',
  in_progress: 'warning',
  completed: 'success',
  expired: 'danger',
}

function formatSchedule(startsAt: string | null, endsAt: string | null): string {
  if (!startsAt && !endsAt) return 'Tidak dijadwalkan'

  const format = (iso: string) =>
    new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })

  if (startsAt && endsAt) return `${format(startsAt)} — ${format(endsAt)}`
  if (startsAt) return `Mulai ${format(startsAt)}`
  return `Berakhir ${format(endsAt as string)}`
}

export function PortalExamsPage() {
  const navigate = useNavigate()

  const fetchExams = useCallback(() => studentExamService.index(), [])
  const { data: exams, isLoading, error } = useFetch(fetchExams)

  const columns: DataTableColumn<StudentExam>[] = [
    {
      header: 'Ujian',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.title}</p>
          <p className="text-xs text-ink-tertiary">{row.course_name ?? '-'}</p>
        </div>
      ),
    },
    { header: 'Dosen', cell: (row) => row.lecturer_name ?? '-' },
    { header: 'Durasi', cell: (row) => `${row.duration_minutes} menit` },
    { header: 'Jadwal', cell: (row) => formatSchedule(row.starts_at, row.ends_at) },
    { header: 'Jumlah Soal', cell: (row) => row.questions_per_participant },
    {
      header: 'Status',
      cell: (row) => (
        <div className="flex flex-col gap-1">
          <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>
          {row.status === 'completed' &&
            (row.result_visible ? (
              <span className="text-xs text-ink-tertiary">Nilai: {row.score}</span>
            ) : (
              <span className="text-xs text-ink-tertiary">Menunggu hasil</span>
            ))}
        </div>
      ),
    },
    {
      header: 'Aksi',
      cell: (row) =>
        (row.status === 'available' || row.status === 'in_progress') && (
          <Button variant="primary" size="sm" onClick={() => navigate(`${ROUTES.portal.ujian}/${row.id}/kerjakan`)}>
            {row.status === 'in_progress' ? 'Lanjutkan' : 'Mulai Ujian'}
          </Button>
        ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Ujian"
        description="Ujian yang dipublikasikan dosen untuk mata kuliah yang Anda ambil."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Ujian' }]}
      />

      <Card noPadding>
        {isLoading ? (
          <div className="flex flex-col gap-3 p-4">
            <SkeletonRow />
            <SkeletonRow />
            <SkeletonRow />
          </div>
        ) : error ? (
          <Alert variant="danger" className="m-4">
            {error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={exams ?? []}
            rowKey={(row) => row.id}
            emptyMessage="Belum ada ujian yang dipublikasikan untuk Anda."
          />
        )}
      </Card>
    </div>
  )
}
