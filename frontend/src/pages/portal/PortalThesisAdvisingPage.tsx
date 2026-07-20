import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { ROUTES } from '@/constants/routes'
import type { ThesisStatus } from '@/types/thesis'

const THESIS = {
  title: 'Sistem Informasi Manajemen Perpustakaan Berbasis Web',
  supervisorName: 'Dr. Siti Nurhaliza, M.Kom.',
  status: 'bimbingan' as ThesisStatus,
  submittedAt: '2026-02-03',
  progress: '3 dari 8 sesi bimbingan selesai',
}

const THESIS_STATUS_LABEL: Record<ThesisStatus, string> = {
  proposal: 'Pengajuan Judul',
  bimbingan: 'Bimbingan',
  seminar_proposal: 'Seminar Proposal',
  penelitian: 'Penelitian',
  sidang: 'Sidang',
  selesai: 'Selesai',
}

const THESIS_STATUS_VARIANT: Record<ThesisStatus, BadgeVariant> = {
  proposal: 'neutral',
  bimbingan: 'info',
  seminar_proposal: 'info',
  penelitian: 'warning',
  sidang: 'warning',
  selesai: 'success',
}

const LOGBOOK = [
  {
    id: '1',
    date: '2026-03-14',
    session: 1,
    note: 'Diskusi rumusan masalah dan batasan sistem.',
    status: 'Selesai' as const,
  },
  {
    id: '2',
    date: '2026-04-18',
    session: 2,
    note: 'Review desain basis data dan diagram alur.',
    status: 'Selesai' as const,
  },
  {
    id: '3',
    date: '2026-06-02',
    session: 3,
    note: 'Konsultasi progres implementasi modul peminjaman.',
    status: 'Selesai' as const,
  },
  {
    id: '4',
    date: '2026-07-28',
    session: 4,
    note: 'Rencana pembahasan modul pengembalian dan denda.',
    status: 'Terjadwal' as const,
  },
]

const LOGBOOK_STATUS_VARIANT: Record<(typeof LOGBOOK)[number]['status'], BadgeVariant> = {
  Terjadwal: 'info',
  Selesai: 'success',
}

const LOGBOOK_COLUMNS: DataTableColumn<(typeof LOGBOOK)[number]>[] = [
  { header: 'Tanggal', cell: (row) => row.date },
  { header: 'Sesi Ke-', cell: (row) => row.session },
  { header: 'Catatan Bimbingan', cell: (row) => row.note },
  {
    header: 'Status',
    cell: (row) => <Badge variant={LOGBOOK_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
  },
]

export function PortalThesisAdvisingPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Bimbingan Skripsi"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Bimbingan' },
          { label: 'Bimbingan Skripsi' },
        ]}
      />

      <Card title={THESIS.title} description={`Dosen Pembimbing: ${THESIS.supervisorName}`}>
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
          <div>
            <p className="text-ink-tertiary">Status</p>
            <Badge variant={THESIS_STATUS_VARIANT[THESIS.status]}>{THESIS_STATUS_LABEL[THESIS.status]}</Badge>
          </div>
          <div>
            <p className="text-ink-tertiary">Tanggal Pengajuan</p>
            <p className="font-medium text-ink-primary">{THESIS.submittedAt}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Progres</p>
            <p className="font-medium text-ink-primary">{THESIS.progress}</p>
          </div>
        </div>
      </Card>

      <Card title="Logbook Bimbingan" noPadding>
        <DataTable columns={LOGBOOK_COLUMNS} data={LOGBOOK} rowKey={(row) => row.id} />
      </Card>

      <Card title="Ajukan Jadwal Bimbingan Baru">
        <form onSubmit={(e) => e.preventDefault()} className="flex flex-col gap-4">
          <Input label="Tanggal Diusulkan" type="date" />
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Agenda/Topik</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Jelaskan agenda atau topik bimbingan yang diusulkan..."
            />
          </div>
          <div>
            <Button type="submit" variant="primary">
              Ajukan Jadwal
            </Button>
          </div>
        </form>
      </Card>
    </div>
  )
}
