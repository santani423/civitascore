import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

type LetterGrade = 'A' | 'AB' | 'B' | 'BC' | 'C' | 'D' | 'E'

const LETTER_GRADE_VARIANT: Record<LetterGrade, BadgeVariant> = {
  A: 'success',
  AB: 'success',
  B: 'info',
  BC: 'info',
  C: 'warning',
  D: 'danger',
  E: 'danger',
}

interface GradeRow {
  id: string
  course: string
  period: string
  credits: number
  letterGrade: LetterGrade | null
  score: number | null
}

const GRADE_ROWS: GradeRow[] = [
  { id: '1', course: 'Rekayasa Perangkat Lunak', period: 'Ganjil 2026/2027', credits: 3, letterGrade: null, score: null },
  { id: '2', course: 'Pemrograman Web', period: 'Ganjil 2026/2027', credits: 3, letterGrade: null, score: null },
  { id: '3', course: 'Sistem Basis Data Lanjut', period: 'Genap 2025/2026', credits: 3, letterGrade: 'AB', score: 85 },
  { id: '4', course: 'Kewarganegaraan', period: 'Genap 2025/2026', credits: 2, letterGrade: 'A', score: 90 },
  { id: '5', course: 'Sistem Operasi', period: 'Ganjil 2025/2026', credits: 3, letterGrade: 'A', score: 91 },
  { id: '6', course: 'Pemrograman Berorientasi Objek', period: 'Ganjil 2025/2026', credits: 3, letterGrade: 'A', score: 92 },
  { id: '7', course: 'Matematika Diskrit', period: 'Ganjil 2025/2026', credits: 3, letterGrade: 'AB', score: 86 },
  { id: '8', course: 'Jaringan Komputer', period: 'Ganjil 2025/2026', credits: 3, letterGrade: 'B', score: 78 },
  { id: '9', course: 'Struktur Data', period: 'Genap 2024/2025', credits: 3, letterGrade: 'A', score: 90 },
  { id: '10', course: 'Basis Data', period: 'Genap 2024/2025', credits: 3, letterGrade: 'AB', score: 85 },
]

const GRADE_COLUMNS: DataTableColumn<GradeRow>[] = [
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'Periode', cell: (row) => row.period },
  { header: 'SKS', cell: (row) => row.credits },
  {
    header: 'Nilai Huruf',
    cell: (row) =>
      row.letterGrade ? (
        <Badge variant={LETTER_GRADE_VARIANT[row.letterGrade]}>{row.letterGrade}</Badge>
      ) : (
        <span className="text-ink-tertiary">Belum dinilai</span>
      ),
  },
  { header: 'Skor', cell: (row) => row.score ?? '-' },
]

export function PortalGradesPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Nilai"
        description="Seluruh nilai mata kuliah yang sudah diambil."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Nilai' }]}
      />

      <Card noPadding>
        <DataTable columns={GRADE_COLUMNS} data={GRADE_ROWS} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
