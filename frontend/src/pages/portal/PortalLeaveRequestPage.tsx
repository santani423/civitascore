import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const SEMESTER_OPTIONS = [
  { value: 'genap-2026-2027', label: 'Genap 2026/2027' },
  { value: 'ganjil-2027-2028', label: 'Ganjil 2027/2028' },
]

const LEAVE_TYPE_OPTIONS = [
  { value: 'akademik', label: 'Cuti Akademik' },
  { value: 'sakit', label: 'Cuti Sakit' },
  { value: 'melahirkan', label: 'Cuti Melahirkan' },
]

type LeaveStatus = 'Diajukan' | 'Disetujui' | 'Ditolak'

interface LeaveHistoryRow {
  id: string
  semester: string
  type: string
  submittedAt: string
  status: LeaveStatus
}

const LEAVE_HISTORY: LeaveHistoryRow[] = [
  { id: '1', semester: 'Ganjil 2025/2026', type: 'Cuti Sakit', submittedAt: '2025-08-02', status: 'Disetujui' },
  { id: '2', semester: 'Genap 2024/2025', type: 'Cuti Akademik', submittedAt: '2025-01-10', status: 'Ditolak' },
]

const LEAVE_STATUS_VARIANT: Record<LeaveStatus, BadgeVariant> = {
  Diajukan: 'neutral',
  Disetujui: 'success',
  Ditolak: 'danger',
}

const LEAVE_HISTORY_COLUMNS: DataTableColumn<LeaveHistoryRow>[] = [
  { header: 'Semester', cell: (row) => row.semester },
  { header: 'Jenis Cuti', cell: (row) => row.type },
  { header: 'Tanggal Pengajuan', cell: (row) => row.submittedAt },
  {
    header: 'Status',
    cell: (row) => <Badge variant={LEAVE_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
  },
]

export function PortalLeaveRequestPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengajuan Cuti Akademik"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Pengajuan' },
          { label: 'Cuti' },
        ]}
      />

      <Card title="Ajukan Cuti Baru">
        <form onSubmit={(e) => e.preventDefault()} className="flex flex-col gap-4">
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Select label="Semester Cuti" options={SEMESTER_OPTIONS} placeholder="Pilih semester" />
            <Select label="Jenis Cuti" options={LEAVE_TYPE_OPTIONS} placeholder="Pilih jenis cuti" />
          </div>

          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Alasan Pengajuan</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Jelaskan alasan pengajuan cuti Anda..."
            />
          </div>

          <div className="flex justify-end">
            <Button type="submit" variant="primary">
              Ajukan Cuti
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Riwayat Pengajuan Cuti" noPadding>
        <DataTable columns={LEAVE_HISTORY_COLUMNS} data={LEAVE_HISTORY} rowKey={(row) => row.id} emptyMessage="Belum ada pengajuan cuti." />
      </Card>
    </div>
  )
}
