import { Link } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
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

function requestableTypeLabel(requestableType: string): string {
  return humanizeSlug(requestableType.split('\\').pop() ?? requestableType)
}

export function ApprovalRequestsPage() {
  const list = usePaginatedList<ApprovalRequest>({ fetcher: approvalRequestService.index })

  const columns: DataTableColumn<ApprovalRequest>[] = [
    { header: 'Jenis Pengajuan', cell: (row) => requestableTypeLabel(row.requestable_type) },
    { header: 'Status', cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge> },
    {
      header: 'Diajukan',
      cell: (row) => (row.submitted_at ? formatRelativeTime(row.submitted_at) : '-'),
    },
    {
      header: '',
      cell: (row) => (
        <Link to={`${ROUTES.persetujuan}/${row.id}`}>
          <Button variant="ghost" size="sm">
            Lihat Detail
          </Button>
        </Link>
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader title="Persetujuan" breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Persetujuan' }]} />

      <Card
        title="Daftar Pengajuan"
        description="Pengajuan yang Anda buat, atau seluruh pengajuan bila Anda punya akses lebih luas"
        noPadding
      >
        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada pengajuan.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page}
            </span>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" disabled={list.page <= 1} onClick={() => list.setPage(list.page - 1)}>
                Sebelumnya
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={list.page >= list.meta.last_page}
                onClick={() => list.setPage(list.page + 1)}
              >
                Berikutnya
              </Button>
            </div>
          </div>
        )}
      </Card>
    </div>
  )
}
