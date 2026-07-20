import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const STUDENT_PROFILE = {
  name: 'Muhammad Rizky Pratama',
  nim: '202400512',
  studyProgram: 'S1 Teknik Informatika',
  admissionYear: 2024,
  email: 'rizky.pratama@student.civitas.ac.id',
  phone: '0812-3456-7890',
  address: 'Jl. Merdeka No. 45, Bandung, Jawa Barat',
}

const CHANGE_TYPE_OPTIONS = [
  { value: 'alamat', label: 'Alamat' },
  { value: 'telepon', label: 'No. Telepon' },
  { value: 'email', label: 'Email' },
  { value: 'nama', label: 'Nama' },
]

const CHANGE_REQUESTS = [
  { id: '1', submittedAt: '2026-06-10', dataType: 'No. Telepon', status: 'Disetujui' as const },
  { id: '2', submittedAt: '2026-06-28', dataType: 'Alamat', status: 'Ditolak' as const },
  { id: '3', submittedAt: '2026-07-15', dataType: 'Email', status: 'Diajukan' as const },
]

const CHANGE_REQUEST_STATUS_VARIANT: Record<(typeof CHANGE_REQUESTS)[number]['status'], BadgeVariant> = {
  Diajukan: 'neutral',
  Disetujui: 'success',
  Ditolak: 'danger',
}

const CHANGE_REQUEST_COLUMNS: DataTableColumn<(typeof CHANGE_REQUESTS)[number]>[] = [
  { header: 'Tanggal Pengajuan', cell: (row) => row.submittedAt },
  { header: 'Jenis Data', cell: (row) => row.dataType },
  {
    header: 'Status',
    cell: (row) => <Badge variant={CHANGE_REQUEST_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
  },
]

function DetailField({ label, value }: { label: string; value: string | number }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value}</p>
    </div>
  )
}

export function PortalProfilePage() {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Profil Saya"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Profil Saya' }]}
      />

      <Card title="Data Pribadi" description={`NIM ${STUDENT_PROFILE.nim}`}>
        <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
          <DetailField label="Nama Lengkap" value={STUDENT_PROFILE.name} />
          <DetailField label="NIM" value={STUDENT_PROFILE.nim} />
          <DetailField label="Program Studi" value={STUDENT_PROFILE.studyProgram} />
          <DetailField label="Angkatan" value={STUDENT_PROFILE.admissionYear} />
          <DetailField label="Email" value={STUDENT_PROFILE.email} />
          <DetailField label="No. Telepon" value={STUDENT_PROFILE.phone} />
          <DetailField label="Alamat" value={STUDENT_PROFILE.address} />
          <div>
            <p className="text-ink-tertiary">Status Mahasiswa</p>
            <Badge variant="success">Aktif</Badge>
          </div>
        </div>
      </Card>

      <Card title="Ajukan Perubahan Data">
        <form onSubmit={(e) => e.preventDefault()} className="flex flex-col gap-4">
          <Select label="Jenis Data yang Diubah" options={CHANGE_TYPE_OPTIONS} placeholder="Pilih jenis data" />
          <Input label="Data Baru" placeholder="Masukkan data baru" />
          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Alasan Perubahan</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Jelaskan alasan pengajuan perubahan data..."
            />
          </div>
          <div>
            <Button type="submit" variant="primary">
              Ajukan Perubahan
            </Button>
          </div>
        </form>
      </Card>

      <Card title="Riwayat Pengajuan Perubahan Data" noPadding>
        <DataTable columns={CHANGE_REQUEST_COLUMNS} data={CHANGE_REQUESTS} rowKey={(row) => row.id} />
      </Card>
    </div>
  )
}
