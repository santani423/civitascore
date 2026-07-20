import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

type EvaluationStatus = 'Belum Diisi' | 'Sudah Diisi'

interface EvaluationRow {
  id: string
  course: string
  lecturer: string
  status: EvaluationStatus
}

const EVALUATIONS: EvaluationRow[] = [
  { id: 'ev-1', course: 'Struktur Data', lecturer: 'Dr. Ahmad Fauzi', status: 'Belum Diisi' },
  { id: 'ev-2', course: 'Basis Data', lecturer: 'Dr. Siti Nurhaliza', status: 'Belum Diisi' },
  { id: 'ev-3', course: 'Bahasa Inggris Akademik', lecturer: 'Rina Marlina, M.Pd.', status: 'Sudah Diisi' },
  { id: 'ev-4', course: 'Pemrograman Web', lecturer: 'Ir. Bambang Sutrisno, M.Kom.', status: 'Belum Diisi' },
]

const STATUS_VARIANT: Record<EvaluationStatus, BadgeVariant> = {
  'Belum Diisi': 'warning',
  'Sudah Diisi': 'success',
}

const RATING_QUESTIONS = [
  'Penguasaan materi',
  'Kejelasan penyampaian',
  'Ketepatan waktu mengajar',
  'Kesediaan membantu mahasiswa',
]

const RATING_OPTIONS = [1, 2, 3, 4, 5]

export function PortalLecturerEvaluationPage() {
  const columns: DataTableColumn<EvaluationRow>[] = [
    { header: 'Mata Kuliah', cell: (row) => row.course },
    { header: 'Dosen Pengampu', cell: (row) => row.lecturer },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{row.status}</Badge>,
    },
    {
      header: 'Aksi',
      cell: (row) =>
        row.status === 'Belum Diisi' ? (
          <Button variant="primary" size="sm">
            Isi Evaluasi
          </Button>
        ) : (
          <span className="text-sm text-ink-tertiary">Terima kasih atas partisipasi Anda</span>
        ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Evaluasi Dosen"
        description="Semester Ganjil 2026/2027 — evaluasi bersifat anonim."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Evaluasi Dosen' }]}
      />

      <Card noPadding>
        <DataTable columns={columns} data={EVALUATIONS} rowKey={(row) => row.id} emptyMessage="Belum ada evaluasi." />
      </Card>

      <Card
        title="Contoh Formulir Evaluasi"
        description="Pratinjau — pilih salah satu mata kuliah di atas untuk mengisi evaluasi sesungguhnya."
      >
        <div className="flex flex-col gap-6">
          {RATING_QUESTIONS.map((question) => (
            <div key={question} className="flex flex-col gap-2">
              <p className="text-sm font-medium text-ink-primary">{question}</p>
              <div className="flex items-center gap-2">
                {RATING_OPTIONS.map((option) => (
                  <button
                    key={option}
                    type="button"
                    className="flex size-9 items-center justify-center rounded-full border border-border-strong text-sm font-medium text-ink-secondary transition-colors hover:border-primary hover:text-primary"
                  >
                    {option}
                  </button>
                ))}
              </div>
            </div>
          ))}

          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Saran dan Masukan Tambahan</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Tuliskan saran dan masukan Anda untuk dosen pengampu..."
            />
          </div>

          <div>
            <Button variant="primary">Kirim Evaluasi</Button>
          </div>
        </div>
      </Card>
    </div>
  )
}
