import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const QUIZZES = [
  {
    id: '1',
    title: 'Kuis Struktur Data Minggu 5',
    course: 'Struktur Data',
    duration: '30 menit',
    deadline: '2026-07-21 23:59',
    status: 'Belum Dikerjakan' as const,
  },
  {
    id: '2',
    title: 'Kuis Normalisasi Database',
    course: 'Basis Data',
    duration: '20 menit',
    deadline: '2026-07-20 15:00',
    status: 'Sedang Berlangsung' as const,
  },
  {
    id: '3',
    title: 'Kuis Grammar Dasar',
    course: 'Bahasa Inggris Akademik',
    duration: '15 menit',
    deadline: '2026-07-15 23:59',
    status: 'Selesai' as const,
    score: '92/100',
  },
  {
    id: '4',
    title: 'Kuis Algoritma Pemrograman Web',
    course: 'Pemrograman Web',
    duration: '25 menit',
    deadline: '2026-07-12 23:59',
    status: 'Selesai' as const,
    score: '85/100',
  },
  {
    id: '5',
    title: 'Kuis Topologi Jaringan',
    course: 'Jaringan Komputer',
    duration: '30 menit',
    deadline: '2026-07-08 23:59',
    status: 'Selesai' as const,
    score: '78/100',
  },
]

const QUIZ_STATUS_VARIANT: Record<(typeof QUIZZES)[number]['status'], BadgeVariant> = {
  'Belum Dikerjakan': 'warning',
  'Sedang Berlangsung': 'info',
  Selesai: 'success',
}

const QUIZ_COLUMNS: DataTableColumn<(typeof QUIZZES)[number]>[] = [
  { header: 'Judul Kuis', cell: (row) => row.title },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'Durasi', cell: (row) => row.duration },
  { header: 'Batas Waktu', cell: (row) => row.deadline },
  {
    header: 'Status',
    cell: (row) => (
      <Badge variant={QUIZ_STATUS_VARIANT[row.status]}>{row.status === 'Selesai' ? `Selesai (${row.score})` : row.status}</Badge>
    ),
  },
  {
    header: 'Aksi',
    cell: (row) =>
      row.status !== 'Selesai' && (
        <Button variant="primary" size="sm">
          {row.status === 'Sedang Berlangsung' ? 'Lanjutkan' : 'Mulai Kuis'}
        </Button>
      ),
  },
]

export function PortalQuizzesPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kuis"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Kuis' }]}
      />

      <Card noPadding>
        <DataTable columns={QUIZ_COLUMNS} data={QUIZZES} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
