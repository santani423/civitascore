import { useCallback } from 'react'
import { Link } from 'react-router-dom'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { useFetch } from '@/hooks/useFetch'
import { approvalRequestService } from '@/services/approvalService'
import type { ApprovalRequest, ApprovalRequestStatus } from '@/types/approval'
import { ROUTES } from '@/constants/routes'
import { formatRelativeTime, humanizeSlug } from '@/utils/formatters'

const STATUS_LABEL: Record<ApprovalRequestStatus, string> = {
  submitted: 'Diajukan',
  in_progress: 'Diproses',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const STATUS_VARIANT: Record<ApprovalRequestStatus, BadgeVariant> = {
  submitted: 'info',
  in_progress: 'warning',
  approved: 'success',
  rejected: 'danger',
}

const columns: DataTableColumn<ApprovalRequest>[] = [
  {
    header: 'Pengajuan',
    cell: (row) => (
      <Link to={`${ROUTES.persetujuan}/${row.id}`} className="block">
        <p className="font-medium text-primary hover:underline">
          {humanizeSlug(row.requestable_type.split('\\').pop() ?? row.requestable_type)}
        </p>
        <Badge variant={STATUS_VARIANT[row.status]} className="mt-1">
          {STATUS_LABEL[row.status]}
        </Badge>
      </Link>
    ),
  },
  {
    header: 'Diajukan',
    cell: (row) => (row.submitted_at ? formatRelativeTime(row.submitted_at) : '-'),
  },
]

export function PendingApprovalsTable() {
  const fetchPending = useCallback(
    () => approvalRequestService.index({ per_page: 5, filter: { status: 'submitted' } }),
    [],
  )
  const { data } = useFetch(fetchPending)

  return (
    <Card title="Menunggu Persetujuan" description="Pengajuan yang membutuhkan tindakan Anda" noPadding>
      <DataTable
        columns={columns}
        data={data?.data ?? []}
        rowKey={(row) => row.id}
        emptyMessage="Tidak ada pengajuan yang menunggu."
      />
    </Card>
  )
}
