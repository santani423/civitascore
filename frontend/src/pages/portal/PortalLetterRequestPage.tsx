import { Download } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const LETTER_TYPE_OPTIONS = [
  { value: 'aktif-kuliah', label: 'Surat Keterangan Aktif Kuliah' },
  { value: 'bebas-perpustakaan', label: 'Surat Keterangan Bebas Perpustakaan' },
  { value: 'pengantar-penelitian', label: 'Surat Pengantar Penelitian' },
  { value: 'lulus-sementara', label: 'Surat Keterangan Lulus Sementara' },
]

type LetterStatus = 'Diajukan' | 'Diproses' | 'Selesai' | 'Ditolak'

interface LetterHistoryRow {
  id: string
  type: string
  submittedAt: string
  status: LetterStatus
}

const LETTER_HISTORY: LetterHistoryRow[] = [
  { id: '1', type: 'Surat Keterangan Aktif Kuliah', submittedAt: '2026-07-14', status: 'Selesai' },
  { id: '2', type: 'Surat Keterangan Bebas Perpustakaan', submittedAt: '2026-07-19', status: 'Diproses' },
  { id: '3', type: 'Surat Pengantar Penelitian', submittedAt: '2026-06-20', status: 'Ditolak' },
]

const LETTER_STATUS_VARIANT: Record<LetterStatus, BadgeVariant> = {
  Diajukan: 'neutral',
  Diproses: 'info',
  Selesai: 'success',
  Ditolak: 'danger',
}

const LETTER_HISTORY_COLUMNS: DataTableColumn<LetterHistoryRow>[] = [
  { header: 'Jenis Surat', cell: (row) => row.type },
  { header: 'Tanggal Pengajuan', cell: (row) => row.submittedAt },
  {
    header: 'Status',
    cell: (row) => <Badge variant={LETTER_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
  },
  {
    header: 'Aksi',
    cell: (row) =>
      row.status === 'Selesai' ? (
        <Button variant="outline" size="sm" leftIcon={<Download className="size-4" />}>
          Unduh
        </Button>
      ) : (
        <span className="text-sm text-ink-tertiary">-</span>
      ),
  },
]

export function PortalLetterRequestPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengajuan Surat"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Pengajuan' },
          { label: 'Surat' },
        ]}
      />

      <Card title="Ajukan Surat Baru">
        <form onSubmit={(e) => e.preventDefault()} className="flex flex-col gap-4">
          <Select label="Jenis Surat" options={LETTER_TYPE_OPTIONS} placeholder="Pilih jenis surat" />

          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Keperluan</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Jelaskan keperluan pengajuan surat ini..."
            />
          </div>

          <div className="flex justify-end">
            <Button type="submit" variant="primary">
              Ajukan Surat
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Riwayat Pengajuan Surat" noPadding>
        <DataTable
          columns={LETTER_HISTORY_COLUMNS}
          data={LETTER_HISTORY}
          rowKey={(row) => row.id}
          emptyMessage="Belum ada pengajuan surat."
        />
      </Card>
    </div>
  )
}
