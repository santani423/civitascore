import { Download } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
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

interface TranscriptRow {
  code: string
  course: string
  credits: number
  letterGrade: LetterGrade
}

interface TranscriptSemester {
  semester: number
  gpa: number
  rows: TranscriptRow[]
}

const TRANSCRIPT_SEMESTERS: TranscriptSemester[] = [
  {
    semester: 1,
    gpa: 3.55,
    rows: [
      { code: 'UN101', course: 'Pendidikan Pancasila', credits: 2, letterGrade: 'A' },
      { code: 'IF101', course: 'Algoritma dan Pemrograman', credits: 3, letterGrade: 'AB' },
      { code: 'IF102', course: 'Matematika Dasar', credits: 3, letterGrade: 'B' },
      { code: 'UN102', course: 'Bahasa Indonesia', credits: 2, letterGrade: 'A' },
    ],
  },
  {
    semester: 2,
    gpa: 3.68,
    rows: [
      { code: 'IF201', course: 'Struktur Data', credits: 3, letterGrade: 'A' },
      { code: 'IF202', course: 'Basis Data', credits: 3, letterGrade: 'AB' },
      { code: 'IF203', course: 'Aljabar Linear', credits: 3, letterGrade: 'B' },
      { code: 'UN201', course: 'Bahasa Inggris Akademik', credits: 2, letterGrade: 'AB' },
    ],
  },
  {
    semester: 3,
    gpa: 3.7,
    rows: [
      { code: 'IF301', course: 'Sistem Operasi', credits: 3, letterGrade: 'A' },
      { code: 'IF302', course: 'Pemrograman Berorientasi Objek', credits: 3, letterGrade: 'A' },
      { code: 'IF303', course: 'Matematika Diskrit', credits: 3, letterGrade: 'AB' },
      { code: 'IF304', course: 'Jaringan Komputer', credits: 3, letterGrade: 'B' },
    ],
  },
  {
    semester: 4,
    gpa: 3.72,
    rows: [
      { code: 'IF401', course: 'Rekayasa Perangkat Lunak', credits: 3, letterGrade: 'A' },
      { code: 'IF402', course: 'Pemrograman Web', credits: 3, letterGrade: 'A' },
      { code: 'IF403', course: 'Sistem Basis Data Lanjut', credits: 3, letterGrade: 'AB' },
      { code: 'UN401', course: 'Kewarganegaraan', credits: 2, letterGrade: 'A' },
    ],
  },
]

const TRANSCRIPT_COLUMNS: DataTableColumn<TranscriptRow>[] = [
  { header: 'Kode', cell: (row) => row.code },
  { header: 'Mata Kuliah', cell: (row) => row.course },
  { header: 'SKS', cell: (row) => row.credits },
  {
    header: 'Nilai Huruf',
    cell: (row) => <Badge variant={LETTER_GRADE_VARIANT[row.letterGrade]}>{row.letterGrade}</Badge>,
  },
]

export function PortalTranscriptPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Transkrip Sementara"
        description="Dokumen ini bersifat sementara dan belum resmi — untuk keperluan resmi, unduh transkrip resmi dari bagian akademik."
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Akademik' },
          { label: 'Transkrip Sementara' },
        ]}
        actions={
          <Button variant="outline" leftIcon={<Download className="size-4" />}>
            Unduh PDF
          </Button>
        }
      />

      <Card title="Ringkasan Akademik">
        <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <p className="text-ink-tertiary">Total SKS Lulus</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">96</p>
          </div>
          <div>
            <p className="text-ink-tertiary">IPK</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">3.65</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Semester Berjalan</p>
            <p className="mt-0.5 text-lg font-semibold text-ink-primary">5</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Status Akademik</p>
            <Badge variant="success" className="mt-1">
              Aktif
            </Badge>
          </div>
        </div>
      </Card>

      {TRANSCRIPT_SEMESTERS.map((semester) => (
        <Card key={semester.semester} title={`Semester ${semester.semester}`}>
          <DataTable columns={TRANSCRIPT_COLUMNS} data={semester.rows} rowKey={(row) => row.code} />
          <p className="mt-3 text-right text-sm font-medium text-ink-primary">IP Semester: {semester.gpa.toFixed(2)}</p>
        </Card>
      ))}
    </div>
  )
}
