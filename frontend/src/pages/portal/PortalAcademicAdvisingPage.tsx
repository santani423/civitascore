import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { ROUTES } from '@/constants/routes'

const ACADEMIC_ADVISOR = {
  name: 'Dr. Bambang Sutrisno, M.Kom.',
  nidn: '0012048501',
  email: 'bambang.sutrisno@civitas.ac.id',
}

const NEXT_SCHEDULE = {
  date: '2026-07-25',
  time: '10:00',
  topic: 'Konsultasi Rencana Studi Semester Genap',
}

type AdvisingStatus = 'Terjadwal' | 'Selesai' | 'Dibatalkan'

interface AdvisingHistoryEntry {
  id: string
  date: string
  topic: string
  note: string
  status: AdvisingStatus
}

const ADVISING_HISTORY: AdvisingHistoryEntry[] = [
  {
    id: '1',
    date: '2026-02-12',
    topic: 'Konsultasi Pengisian KRS Semester Genap',
    note: 'Disarankan mengambil maksimal 20 SKS.',
    status: 'Selesai',
  },
  {
    id: '2',
    date: '2026-05-06',
    topic: 'Evaluasi Nilai Tengah Semester',
    note: 'IP semester berjalan baik, pertahankan.',
    status: 'Selesai',
  },
  {
    id: '3',
    date: '2026-07-25',
    topic: 'Konsultasi Rencana Studi Semester Genap',
    note: 'Menunggu jadwal pertemuan.',
    status: 'Terjadwal',
  },
]

const ADVISING_STATUS_VARIANT: Record<AdvisingStatus, BadgeVariant> = {
  Terjadwal: 'info',
  Selesai: 'success',
  Dibatalkan: 'danger',
}

const ADVISING_COLUMNS: DataTableColumn<AdvisingHistoryEntry>[] = [
  { header: 'Tanggal', cell: (row) => row.date },
  { header: 'Topik', cell: (row) => row.topic },
  { header: 'Catatan Dosen', cell: (row) => row.note },
  {
    header: 'Status',
    cell: (row) => <Badge variant={ADVISING_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
  },
]

export function PortalAcademicAdvisingPage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Bimbingan Akademik"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Bimbingan' },
          { label: 'Bimbingan Akademik' },
        ]}
      />

      <Card title="Dosen Pembimbing Akademik">
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-ink-tertiary">Nama</p>
            <p className="font-medium text-ink-primary">{ACADEMIC_ADVISOR.name}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">NIDN</p>
            <p className="font-medium text-ink-primary">{ACADEMIC_ADVISOR.nidn}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Email</p>
            <p className="font-medium text-ink-primary">{ACADEMIC_ADVISOR.email}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Status</p>
            <Badge variant="success">Aktif</Badge>
          </div>
        </div>
      </Card>

      <Card
        title="Jadwal Bimbingan Berikutnya"
        actions={
          <Button variant="outline" size="sm">
            Ajukan Jadwal Baru
          </Button>
        }
      >
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
          <div>
            <p className="text-ink-tertiary">Tanggal</p>
            <p className="font-medium text-ink-primary">{NEXT_SCHEDULE.date}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Waktu</p>
            <p className="font-medium text-ink-primary">{NEXT_SCHEDULE.time}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Agenda</p>
            <p className="font-medium text-ink-primary">{NEXT_SCHEDULE.topic}</p>
          </div>
        </div>
      </Card>

      <Card title="Riwayat Bimbingan" noPadding>
        <DataTable columns={ADVISING_COLUMNS} data={ADVISING_HISTORY} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
