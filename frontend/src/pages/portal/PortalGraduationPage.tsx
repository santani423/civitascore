import { CheckCircle2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { ROUTES } from '@/constants/routes'

const GRADUATION_PERIOD_OPTIONS = [
  { value: 'periode-1-2027', label: 'Wisuda Periode I - Maret 2027' },
  { value: 'periode-2-2027', label: 'Wisuda Periode II - Agustus 2027' },
]

interface RegistrationRow {
  id: string
  period: string
  registeredAt: string
  status: 'Menunggu Verifikasi'
}

const REGISTRATIONS: RegistrationRow[] = [
  { id: 'reg-1', period: 'Wisuda Periode I - Maret 2027', registeredAt: '2026-07-19', status: 'Menunggu Verifikasi' },
]

const REGISTRATION_STATUS_VARIANT: Record<RegistrationRow['status'], BadgeVariant> = {
  'Menunggu Verifikasi': 'neutral',
}

function EligibilityField({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex items-start gap-2">
      <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-primary" />
      <div>
        <p className="text-ink-tertiary">{label}</p>
        <p className="font-medium text-ink-primary">{value}</p>
      </div>
    </div>
  )
}

export function PortalGraduationPage() {
  const columns: DataTableColumn<RegistrationRow>[] = [
    { header: 'Periode', cell: (row) => row.period },
    { header: 'Tanggal Daftar', cell: (row) => row.registeredAt },
    {
      header: 'Status',
      cell: (row) => <Badge variant={REGISTRATION_STATUS_VARIANT[row.status]}>{row.status}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pendaftaran Wisuda"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pendaftaran Wisuda' }]}
      />

      <Card title="Status Kelayakan Wisuda">
        <div className="flex flex-col gap-4">
          <Badge variant="success" className="w-fit">
            Memenuhi Syarat Wisuda
          </Badge>

          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <EligibilityField label="Total SKS Lulus" value="148/144 SKS" />
            <EligibilityField label="IPK Minimum" value="3.65 (min. 2.00)" />
            <div>
              <p className="text-ink-tertiary">Skripsi</p>
              <Badge variant="success">Selesai</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Bebas Tanggungan Perpustakaan</p>
              <Badge variant="success">Bebas</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Bebas Tanggungan Keuangan</p>
              <Badge variant="success">Lunas</Badge>
            </div>
          </div>
        </div>
      </Card>

      <Card title="Formulir Pendaftaran Wisuda">
        <div className="flex flex-col gap-4">
          <Select label="Periode Wisuda" options={GRADUATION_PERIOD_OPTIONS} placeholder="Pilih periode wisuda" />
          <Input label="Ukuran Toga" placeholder="Contoh: M, L, XL" />

          <div className="flex flex-col gap-1.5">
            <label className="text-sm font-medium text-ink-primary">Catatan Tambahan (opsional)</label>
            <textarea
              rows={4}
              className="w-full rounded-lg border border-border-strong bg-surface px-3 py-2 text-sm text-ink-primary placeholder:text-ink-tertiary transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
              placeholder="Tuliskan catatan tambahan jika ada..."
            />
          </div>

          <div>
            <Button variant="primary">Daftar Wisuda</Button>
          </div>
        </div>
      </Card>

      <Card title="Status Pendaftaran" noPadding>
        <DataTable columns={columns} data={REGISTRATIONS} rowKey={(row) => row.id} emptyMessage="Anda belum mendaftar wisuda periode ini." />
      </Card>
    </div>
  )
}
