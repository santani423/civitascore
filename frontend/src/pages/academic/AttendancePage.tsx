import { useEffect, useState } from 'react'
import { Plus } from 'lucide-react'
import { useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { attendanceService, classSectionService, krsItemService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { Attendance, AttendanceStatus, ClassSection, KrsItem } from '@/types/academic'
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

const STATUS_OPTIONS = (Object.keys(STATUS_LABEL) as AttendanceStatus[]).map((value) => ({
  value,
  label: STATUS_LABEL[value],
}))

export function AttendancePage() {
  const canRecord = usePermission(['attendance.create', 'attendance.update'])

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['student_id', 'class_section_id', 'status']))

  const list = usePaginatedList<Attendance>({ fetcher: attendanceService.index, initialFilter })
  const [showRecordModal, setShowRecordModal] = useState(false)

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
        actions={
          canRecord && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowRecordModal(true)}>
              Rekam Kehadiran
            </Button>
          )
        }
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

      {showRecordModal && (
        <RecordAttendanceModal
          onClose={() => setShowRecordModal(false)}
          onSaved={() => {
            setShowRecordModal(false)
            list.refetch()
          }}
        />
      )}
    </div>
  )
}

function RecordAttendanceModal({ onClose, onSaved }: { onClose: () => void; onSaved: () => void }) {
  const [classSections, setClassSections] = useState<ClassSection[] | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)

  const [classSectionId, setClassSectionId] = useState('')
  const [meetingNumber, setMeetingNumber] = useState('1')
  const [meetingDate, setMeetingDate] = useState(() => new Date().toISOString().slice(0, 10))

  const [roster, setRoster] = useState<KrsItem[] | null>(null)
  const [rosterError, setRosterError] = useState<string | null>(null)
  const [statuses, setStatuses] = useState<Record<string, AttendanceStatus>>({})

  const [submitError, setSubmitError] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)

  useEffect(() => {
    classSectionService
      .index({ per_page: 100 })
      .then((result) => setClassSections(result.data))
      .catch((error: NormalizedApiError) => setLoadError(error.message))
  }, [])

  useEffect(() => {
    if (!classSectionId) {
      setRoster(null)
      return
    }

    setRoster(null)
    setRosterError(null)

    krsItemService
      .index({ per_page: 100, filter: { class_section_id: classSectionId, status: 'enrolled' } })
      .then((result) => {
        setRoster(result.data)
        setStatuses(Object.fromEntries(result.data.map((krsItem) => [krsItem.id, 'present' as AttendanceStatus])))
      })
      .catch((error: NormalizedApiError) => setRosterError(error.message))
  }, [classSectionId])

  const handleSubmit = async () => {
    if (!roster || roster.length === 0) return
    setSubmitError(null)
    setIsSubmitting(true)

    try {
      await attendanceService.recordBatch(classSectionId, {
        meeting_number: Number(meetingNumber),
        meeting_date: meetingDate,
        entries: roster.map((krsItem) => ({ krs_item_id: krsItem.id, status: statuses[krsItem.id] ?? 'present' })),
      })
      onSaved()
    } catch (error) {
      setSubmitError((error as NormalizedApiError).message ?? 'Gagal menyimpan kehadiran.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Rekam Kehadiran" className="max-w-lg">
      {loadError && <Alert variant="danger">{loadError}</Alert>}
      {submitError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setSubmitError(null)}>
          {submitError}
        </Alert>
      )}

      {classSections === null ? (
        <p className="text-sm text-ink-secondary">Memuat daftar kelas...</p>
      ) : (
        <div className="flex flex-col gap-4">
          <Select
            label="Kelas"
            placeholder="Pilih kelas"
            value={classSectionId}
            onChange={(event) => setClassSectionId(event.target.value)}
            options={classSections.map((classSection) => ({
              value: classSection.id,
              label: `${classSection.course_name ?? '-'} (${classSection.class_code})`,
            }))}
          />

          <div className="grid grid-cols-2 gap-3">
            <Input
              type="number"
              min={1}
              label="Pertemuan ke-"
              value={meetingNumber}
              onChange={(event) => setMeetingNumber(event.target.value)}
            />
            <Input
              type="date"
              label="Tanggal"
              value={meetingDate}
              onChange={(event) => setMeetingDate(event.target.value)}
            />
          </div>

          {rosterError && <Alert variant="danger">{rosterError}</Alert>}

          {classSectionId && roster === null && <p className="text-sm text-ink-secondary">Memuat peserta kelas...</p>}

          {roster !== null && roster.length === 0 && (
            <p className="text-sm text-ink-tertiary">Belum ada mahasiswa terdaftar aktif di kelas ini.</p>
          )}

          {roster !== null && roster.length > 0 && (
            <div className="max-h-72 overflow-y-auto rounded-lg border border-border">
              {roster.map((krsItem) => (
                <div
                  key={krsItem.id}
                  className="flex items-center justify-between gap-3 border-b border-border px-3 py-2 last:border-b-0"
                >
                  <div>
                    <p className="text-sm font-medium text-ink-primary">{krsItem.student_name ?? '-'}</p>
                    <p className="text-xs text-ink-tertiary">{krsItem.student_nim ?? '-'}</p>
                  </div>
                  <Select
                    className="h-9 w-32"
                    value={statuses[krsItem.id] ?? 'present'}
                    onChange={(event) =>
                      setStatuses((current) => ({ ...current, [krsItem.id]: event.target.value as AttendanceStatus }))
                    }
                    options={STATUS_OPTIONS}
                  />
                </div>
              ))}
            </div>
          )}

          <div className="mt-2 flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>
              Batal
            </Button>
            <Button
              type="button"
              isLoading={isSubmitting}
              disabled={!roster || roster.length === 0}
              onClick={handleSubmit}
            >
              Simpan Kehadiran
            </Button>
          </div>
        </div>
      )}
    </Modal>
  )
}
