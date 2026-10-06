import { useCallback, useState } from 'react'
import { ClipboardCheck } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Modal } from '@/components/ui/Modal'
import { Select } from '@/components/ui/Select'
import { PortalEmpty, PortalError, PortalLoading, ProgressBar } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { AttendanceCourse, AttendanceDetail } from '@/types/studentPortal'
import { formatDate, formatPercent } from '@/utils/portalFormat'
import type { NormalizedApiError } from '@/services/api'

const MEETING_VARIANT = { present: 'success', permitted: 'info', sick: 'warning', absent: 'danger' } as const

function Counts({ row }: { row: Pick<AttendanceCourse, 'present' | 'permitted' | 'sick' | 'absent'> }) {
  return (
    <div className="grid grid-cols-4 gap-2 text-center text-xs">
      {[
        ['Hadir', row.present],
        ['Izin', row.permitted],
        ['Sakit', row.sick],
        ['Alpa', row.absent],
      ].map(([label, value]) => (
        <div key={label as string} className="rounded-lg bg-surface-hover px-1 py-1.5">
          <p className="text-base font-semibold text-ink-primary">{value}</p>
          <p className="text-ink-tertiary">{label}</p>
        </div>
      ))}
    </div>
  )
}

export function PortalAttendancePage() {
  const [termId, setTermId] = useState<string>('')
  const fetchAttendance = useCallback(() => studentPortalService.attendance(termId || undefined), [termId])
  const { data, isLoading, error, refetch } = useFetch(fetchAttendance)

  const [detail, setDetail] = useState<AttendanceDetail | null>(null)
  const [detailError, setDetailError] = useState<string | null>(null)
  const [loadingDetail, setLoadingDetail] = useState<string | null>(null)

  const openDetail = async (row: AttendanceCourse) => {
    setLoadingDetail(row.krs_item_id)
    setDetailError(null)

    try {
      setDetail(await studentPortalService.attendanceDetail(row.krs_item_id))
    } catch (err) {
      setDetailError((err as NormalizedApiError).message)
    } finally {
      setLoadingDetail(null)
    }
  }

  if (isLoading && !data) return <PortalLoading cards={3} rows={4} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Presensi"
        description={`Rekap kehadiran per mata kuliah. Batas minimum kehadiran ${formatPercent(data.minimum_percent)}.`}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Presensi' }]}
        actions={
          data.terms.length > 1 ? (
            <div className="w-56">
              <Select
                aria-label="Pilih semester"
                value={termId || data.term?.id || ''}
                onChange={(event) => setTermId(event.target.value)}
                options={data.terms.map((term) => ({ value: term.id, label: term.label }))}
              />
            </div>
          ) : undefined
        }
      />

      {detailError && <PortalError message={detailError} />}

      <Card>
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p className="text-xs text-ink-tertiary">Kehadiran keseluruhan {data.term ? `· ${data.term.label}` : ''}</p>
            <p className="text-2xl font-semibold text-ink-primary">{formatPercent(data.overall.percentage)}</p>
          </div>
          <div className="w-full sm:max-w-sm">
            <Counts row={data.overall} />
          </div>
        </div>
      </Card>

      {data.courses.length === 0 ? (
        <PortalEmpty icon={ClipboardCheck} title="Belum ada data presensi." description="Presensi muncul setelah dosen mencatat kehadiran di kelas yang Anda ikuti." />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {data.courses.map((row) => (
            <Card key={row.krs_item_id}>
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                  <p className="font-medium text-ink-primary">{row.course_name}</p>
                  <p className="text-xs text-ink-secondary">
                    {row.course_code} · Kelas {row.class_code} · {row.lecturer?.name ?? '-'}
                  </p>
                </div>
                {row.is_below_minimum && <Badge variant="danger">Di bawah minimum</Badge>}
              </div>
              <div className="mt-3 flex items-end justify-between">
                <p className="text-xl font-semibold text-ink-primary">{formatPercent(row.percentage)}</p>
                <p className="text-xs text-ink-tertiary">{row.total_meetings} pertemuan</p>
              </div>
              <div className="mt-1.5">
                <ProgressBar value={row.percentage ?? 0} tone={row.is_below_minimum ? 'danger' : 'primary'} />
              </div>
              <div className="mt-3">
                <Counts row={row} />
              </div>
              <Button size="sm" variant="ghost" className="mt-2 w-full" isLoading={loadingDetail === row.krs_item_id} onClick={() => openDetail(row)}>
                Lihat detail pertemuan
              </Button>
            </Card>
          ))}
        </div>
      )}

      <Modal open={detail !== null} onClose={() => setDetail(null)} title={detail ? `Presensi ${detail.course_name}` : undefined}>
        {detail && (
          <ul className="flex flex-col divide-y divide-border">
            {detail.meetings.length === 0 && <li className="py-2 text-sm text-ink-secondary">Belum ada pertemuan tercatat.</li>}
            {detail.meetings.map((meeting) => (
              <li key={meeting.meeting_number} className="flex items-center justify-between gap-2 py-2.5 text-sm">
                <div>
                  <p className="font-medium text-ink-primary">Pertemuan {meeting.meeting_number}</p>
                  <p className="text-xs text-ink-secondary">{formatDate(meeting.meeting_date, 'long')}</p>
                  {meeting.notes && <p className="text-xs text-ink-tertiary">{meeting.notes}</p>}
                </div>
                <Badge variant={MEETING_VARIANT[meeting.status]}>{meeting.status_label}</Badge>
              </li>
            ))}
          </ul>
        )}
      </Modal>
    </div>
  )
}
