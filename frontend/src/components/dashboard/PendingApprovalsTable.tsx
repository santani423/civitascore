import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { PENDING_APPROVALS } from '@/data/mock/approvals'
import type { ApprovalCategory, PendingApprovalItem } from '@/types/dashboard'
import { formatRelativeTime } from '@/utils/formatters'

const CATEGORY_LABEL: Record<ApprovalCategory, string> = {
  krs: 'KRS',
  leave: 'Cuti Akademik',
  grade_change: 'Perubahan Nilai',
  letter: 'Pengajuan Surat',
  scholarship: 'Beasiswa',
}

const CATEGORY_VARIANT: Record<ApprovalCategory, BadgeVariant> = {
  krs: 'primary',
  leave: 'warning',
  grade_change: 'info',
  letter: 'neutral',
  scholarship: 'success',
}

const columns: DataTableColumn<PendingApprovalItem>[] = [
  {
    header: 'Pengajuan',
    cell: (row) => (
      <div>
        <p className="font-medium text-ink-primary">{row.title}</p>
        <Badge variant={CATEGORY_VARIANT[row.category]} className="mt-1">
          {CATEGORY_LABEL[row.category]}
        </Badge>
      </div>
    ),
  },
  { header: 'Pemohon', cell: (row) => row.requester },
  { header: 'Diajukan', cell: (row) => formatRelativeTime(row.submittedAt) },
  {
    header: 'Sisa Langkah',
    cell: (row) => <span className="text-ink-secondary">{row.waitingSteps} langkah</span>,
  },
]

export function PendingApprovalsTable() {
  return (
    <Card title="Menunggu Persetujuan" description="Pengajuan yang membutuhkan tindakan Anda" noPadding>
      <DataTable columns={columns} data={PENDING_APPROVALS} rowKey={(row) => row.id} emptyMessage="Tidak ada pengajuan yang menunggu." />
    </Card>
  )
}
