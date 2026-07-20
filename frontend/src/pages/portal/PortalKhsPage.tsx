import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Select } from '@/components/ui/Select'
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

const SEMESTER_OPTIONS = [
  { value: 'ganjil-2026-2027', label: 'Ganjil 2026/2027' },
  { value: 'genap-2025-2026', label: 'Genap 2025/2026' },
  { value: 'ganjil-2025-2026', label: 'Ganjil 2025/2026' },
]

interface KhsRow {
  code: string
  course: string
  credits: number
  letterGrade: LetterGrade
  weight: number
  score: number
}

const KHS_ROWS: KhsRow[] = [
  { code: 'IF301', course: 'Struktur Data', credits: 3, letterGrade: 'A', weight: 4.0, score: 90 },
  { code: 'IF302', course: 'Basis Data', credits: 3, letterGrade: 'AB', weight: 3.7, score: 85 },
  { code: 'IF303', course: 'Pemrograman Berorientasi Objek', credits: 3, letterGrade: 'A', weight: 4.0, score: 92 },
  { code: 'UN201', course: 'Bahasa Inggris Akademik', credits: 2, letterGrade: 'B', weight: 3.0, score: 78 },
  { code: 'IF304', course: 'Sistem Operasi', credits: 3, letterGrade: 'BC', weight: 2.7, score: 74 },
  { code: 'IF305', course: 'Matematika Diskrit', credits: 3, letterGrade: 'AB', weight: 3.7, score: 86 },
]

const KHS_COLUMNS: DataTableColumn<KhsRow>[] = [
  { header: 'Kode', cell: (row) => row.code },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'SKS', cell: (row) => row.credits },
  {
    header: 'Nilai Huruf',
    cell: (row) => <Badge variant={LETTER_GRADE_VARIANT[row.letterGrade]}>{row.letterGrade}</Badge>,
  },
  { header: 'Bobot', cell: (row) => row.weight.toFixed(2) },
  { header: 'Nilai Angka', cell: (row) => row.score },
]

export function PortalKhsPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kartu Hasil Studi (KHS)"
        description="Ringkasan nilai Anda per semester."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'KHS' }]}
      />

      <div className="max-w-xs">
        <Select label="Semester" options={SEMESTER_OPTIONS} defaultValue={SEMESTER_OPTIONS[0].value} />
      </div>

      <Card title="Ringkasan Semester">
        <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <p className="text-ink-tertiary">IP Semester</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">3.72</p>
          </div>
          <div>
            <p className="text-ink-tertiary">SKS Diambil</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">21</p>
          </div>
          <div>
            <p className="text-ink-tertiary">SKS Lulus</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">21</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Status</p>
            <Badge variant="success" className="mt-1">
              Lulus Semua
            </Badge>
          </div>
        </div>
      </Card>

      <Card title="Rincian Nilai per Mata Kuliah" noPadding>
        <DataTable columns={KHS_COLUMNS} data={KHS_ROWS} rowKey={(row) => row.code} />
      </Card>
    </div>
  )
}
