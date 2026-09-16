import { useCallback, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Download } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Alert } from '@/components/ui/Alert'
import { SkeletonRow } from '@/components/ui/Skeleton'
import { useFetch } from '@/hooks/useFetch'
import { studentExamService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
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
  const [downloadingId, setDownloadingId] = useState<string | null>(null)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  const fetchExams = useCallback(() => studentExamService.index(), [])
  const { data: exams, isLoading, error } = useFetch(fetchExams)

  const handleDownload = async (row: StudentExam) => {
    if (!row.latest_attempt_id) return
    setDownloadingId(row.latest_attempt_id)
    setDownloadError(null)

    try {
      const filename = `hasil-ujian-${row.title.replace(/\s+/g, '-').toLowerCase()}.pdf`
      await studentExamService.downloadResultPdf(row.latest_attempt_id, filename)
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message ?? 'Gagal mengunduh PDF hasil ujian.')
    } finally {
      setDownloadingId(null)
    }
  }

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
      cell: (row) => {
        if (row.status === 'available' || row.status === 'in_progress') {
          return (
            <Button variant="primary" size="sm" onClick={() => navigate(`${ROUTES.portal.ujian}/${row.id}/kerjakan`)}>
              {row.status === 'in_progress' ? 'Lanjutkan' : 'Mulai Ujian'}
            </Button>
          )
        }

        if (row.status === 'completed' && row.latest_attempt_id) {
          return (
            <div className="flex gap-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => navigate(ROUTES.portal.ujianHasil.replace(':attemptId', row.latest_attempt_id as string))}
              >
                Lihat Hasil
              </Button>
              {row.result_visible && (
                <Button
                  variant="ghost"
                  size="sm"
                  leftIcon={<Download className="size-3.5" />}
                  isLoading={downloadingId === row.latest_attempt_id}
                  onClick={() => handleDownload(row)}
                >
                  Download
                </Button>
              )}
            </div>
          )
        }

        return null
      },
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Ujian"
        description="Ujian yang dipublikasikan dosen untuk mata kuliah yang Anda ambil."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Ujian' }]}
      />

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)}>
          {downloadError}
        </Alert>
      )}

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
