import { CheckCircle } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { StatCard } from '@/components/ui/StatCard'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const TODAY_CLASSES = [
  { id: '1', course: 'Struktur Data', time: '08:00 - 09:40', room: 'Lab Komputer 2', checkedIn: true },
  { id: '2', course: 'Basis Data', time: '10:00 - 11:40', room: 'Ruang 301', checkedIn: false },
]

const ATTENDANCE_SUMMARY = [
  { label: 'Hadir', value: 42 },
  { label: 'Izin', value: 2 },
  { label: 'Sakit', value: 1 },
  { label: 'Alpa', value: 0 },
]

const ATTENDANCE_HISTORY = [
  { id: '1', date: '2026-07-18', course: 'Struktur Data', meeting: 5, status: 'present' as const },
  { id: '2', date: '2026-07-17', course: 'Basis Data', meeting: 5, status: 'present' as const },
  { id: '3', date: '2026-07-16', course: 'Bahasa Inggris Akademik', meeting: 4, status: 'permitted' as const },
  { id: '4', date: '2026-07-15', course: 'Struktur Data', meeting: 4, status: 'present' as const },
  { id: '5', date: '2026-07-14', course: 'Basis Data', meeting: 4, status: 'sick' as const },
  { id: '6', date: '2026-07-11', course: 'Bahasa Inggris Akademik', meeting: 3, status: 'present' as const },
  { id: '7', date: '2026-07-10', course: 'Struktur Data', meeting: 3, status: 'absent' as const },
  { id: '8', date: '2026-07-09', course: 'Basis Data', meeting: 3, status: 'present' as const },
]

const ATTENDANCE_STATUS_LABEL: Record<(typeof ATTENDANCE_HISTORY)[number]['status'], string> = {
  present: 'Hadir',
  permitted: 'Izin',
  sick: 'Sakit',
  absent: 'Alpa',
}

const ATTENDANCE_STATUS_VARIANT: Record<(typeof ATTENDANCE_HISTORY)[number]['status'], BadgeVariant> = {
  present: 'success',
  permitted: 'info',
  sick: 'warning',
  absent: 'danger',
}

const ATTENDANCE_COLUMNS: DataTableColumn<(typeof ATTENDANCE_HISTORY)[number]>[] = [
  { header: 'Tanggal', cell: (row) => row.date },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'Pertemuan Ke-', cell: (row) => `Ke-${row.meeting}` },
  {
    header: 'Status',
    cell: (row) => <Badge variant={ATTENDANCE_STATUS_VARIANT[row.status]}>{ATTENDANCE_STATUS_LABEL[row.status]}</Badge>,
  },
]

export function PortalAttendancePage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Absensi"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Absensi' }]}
      />

      <Card title="Presensi Kelas Hari Ini">
        <ul className="divide-y divide-border">
          {TODAY_CLASSES.map((item) => (
            <li key={item.id} className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
              <div>
                <p className="font-medium text-ink-primary">{item.course}</p>
                <p className="text-xs text-ink-tertiary">
                  {item.time} · {item.room}
                </p>
              </div>
              {item.checkedIn ? (
                <Badge variant="success">Sudah Presensi</Badge>
              ) : (
                <Button variant="primary" size="sm">
                  Lakukan Presensi
                </Button>
              )}
            </li>
          ))}
        </ul>
      </Card>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {ATTENDANCE_SUMMARY.map((item) => (
          <StatCard key={item.label} label={item.label} value={String(item.value)} icon={CheckCircle} />
        ))}
      </div>

      <Card title="Riwayat Kehadiran" noPadding>
        <DataTable columns={ATTENDANCE_COLUMNS} data={ATTENDANCE_HISTORY} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
