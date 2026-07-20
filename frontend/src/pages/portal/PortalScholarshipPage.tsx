import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'
import { formatCurrencyIDR } from '@/utils/formatters'

interface OpenScholarshipRow {
  id: string
  name: string
  provider: string
  amount: number
  deadline: string
}

const OPEN_SCHOLARSHIPS: OpenScholarshipRow[] = [
  { id: '1', name: 'Beasiswa Unggulan', provider: 'Kemendikbudristek', amount: 5_000_000, deadline: '2026-08-15' },
  { id: '2', name: 'Beasiswa Prestasi Akademik', provider: 'Universitas', amount: 3_000_000, deadline: '2026-08-01' },
  { id: '3', name: 'Beasiswa Bidikmisi', provider: 'Yayasan Pendidikan Bangsa', amount: 4_500_000, deadline: '2026-08-20' },
]

const OPEN_SCHOLARSHIP_COLUMNS: DataTableColumn<OpenScholarshipRow>[] = [
  { header: 'Nama Beasiswa', cell: (row) => row.name },
  { header: 'Penyedia', cell: (row) => row.provider },
  { header: 'Besaran Bantuan', cell: (row) => formatCurrencyIDR(row.amount) },
  { header: 'Batas Pendaftaran', cell: (row) => row.deadline },
  {
    header: 'Aksi',
    cell: () => (
      <Button variant="primary" size="sm">
        Ajukan
      </Button>
    ),
  },
]

type ScholarshipApplicationStatus = 'Diajukan' | 'Ditinjau' | 'Disetujui' | 'Ditolak'

interface ScholarshipApplicationRow {
  id: string
  name: string
  submittedAt: string
  status: ScholarshipApplicationStatus
}

const SCHOLARSHIP_APPLICATIONS: ScholarshipApplicationRow[] = [
  { id: '1', name: 'Beasiswa Peningkatan Prestasi Akademik', submittedAt: '2026-06-10', status: 'Disetujui' },
  { id: '2', name: 'Beasiswa Unggulan', submittedAt: '2026-07-05', status: 'Ditinjau' },
]

const SCHOLARSHIP_APPLICATION_STATUS_VARIANT: Record<ScholarshipApplicationStatus, BadgeVariant> = {
  Diajukan: 'neutral',
  Ditinjau: 'info',
  Disetujui: 'success',
  Ditolak: 'danger',
}

const SCHOLARSHIP_APPLICATION_COLUMNS: DataTableColumn<ScholarshipApplicationRow>[] = [
  { header: 'Nama Beasiswa', cell: (row) => row.name },
  { header: 'Tanggal Pengajuan', cell: (row) => row.submittedAt },
  {
    header: 'Status',
    cell: (row) => (
      <Badge variant={SCHOLARSHIP_APPLICATION_STATUS_VARIANT[row.status]}>{row.status}</Badge>
    ),
  },
]

export function PortalScholarshipPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengajuan Beasiswa"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Pengajuan' },
          { label: 'Beasiswa' },
        ]}
      />

      <Card title="Beasiswa yang Sedang Dibuka" noPadding>
        <DataTable
          columns={OPEN_SCHOLARSHIP_COLUMNS}
          data={OPEN_SCHOLARSHIPS}
          rowKey={(row) => row.id}
          emptyMessage="Belum ada beasiswa yang dibuka."
        />
      </Card>

      <Card title="Riwayat Pengajuan Beasiswa Saya" noPadding>
        <DataTable
          columns={SCHOLARSHIP_APPLICATION_COLUMNS}
          data={SCHOLARSHIP_APPLICATIONS}
          rowKey={(row) => row.id}
          emptyMessage="Belum ada pengajuan beasiswa."
        />
      </Card>
    </div>
  )
}
