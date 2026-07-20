import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const ASSIGNMENTS = [
  { id: '1', title: 'Tugas Besar 1 - Struktur Data', course: 'Struktur Data', dueDate: '2026-07-22', status: 'Belum Dikumpulkan' as const },
  { id: '2', title: 'Kuis Normalisasi Database', course: 'Basis Data', dueDate: '2026-07-15', status: 'Terlambat' as const },
  { id: '3', title: 'Esai Argumentatif', course: 'Bahasa Inggris Akademik', dueDate: '2026-07-10', status: 'Dikumpulkan' as const },
  {
    id: '4',
    title: 'Laporan Praktikum Jaringan',
    course: 'Jaringan Komputer',
    dueDate: '2026-07-05',
    status: 'Dinilai' as const,
    score: 88,
  },
  {
    id: '5',
    title: 'Tugas Individu Pemrograman Web',
    course: 'Pemrograman Web',
    dueDate: '2026-07-25',
    status: 'Belum Dikumpulkan' as const,
  },
  {
    id: '6',
    title: 'Review Jurnal Kecerdasan Buatan',
    course: 'Kecerdasan Buatan',
    dueDate: '2026-06-30',
    status: 'Dinilai' as const,
    score: 95,
  },
]

const ASSIGNMENT_STATUS_VARIANT: Record<(typeof ASSIGNMENTS)[number]['status'], BadgeVariant> = {
  'Belum Dikumpulkan': 'warning',
  Dikumpulkan: 'success',
  Terlambat: 'danger',
  Dinilai: 'info',
}

const ASSIGNMENT_COLUMNS: DataTableColumn<(typeof ASSIGNMENTS)[number]>[] = [
  { header: 'Judul Tugas', cell: (row) => row.title },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'Tenggat Waktu', cell: (row) => row.dueDate },
  {
    header: 'Status',
    cell: (row) => (
      <Badge variant={ASSIGNMENT_STATUS_VARIANT[row.status]}>
        {row.status === 'Dinilai' ? `Dinilai (${row.score})` : row.status}
      </Badge>
    ),
  },
  {
    header: 'Aksi',
    cell: (row) =>
      (row.status === 'Belum Dikumpulkan' || row.status === 'Terlambat') && (
        <Button variant="outline" size="sm">
          Kumpulkan
        </Button>
      ),
  },
]

export function PortalAssignmentsPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Tugas"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Tugas' }]}
      />

      <Card noPadding>
        <DataTable columns={ASSIGNMENT_COLUMNS} data={ASSIGNMENTS} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
