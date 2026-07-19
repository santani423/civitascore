import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { approvalWorkflowService } from '@/services/approvalService'
import type { ApprovalWorkflow } from '@/types/approval'
import { ROUTES } from '@/constants/routes'
import { humanizeSlug } from '@/utils/formatters'

export function ApprovalWorkflowsPage() {
  const list = usePaginatedList<ApprovalWorkflow>({ fetcher: approvalWorkflowService.index })

  const columns: DataTableColumn<ApprovalWorkflow>[] = [
    {
      header: 'Alur Persetujuan',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          {row.description && <p className="text-xs text-ink-tertiary">{row.description}</p>}
        </div>
      ),
    },
    {
      header: 'Berlaku Untuk',
      cell: (row) => humanizeSlug(row.workflowable_type.split('\\').pop() ?? row.workflowable_type),
    },
    { header: 'Jumlah Langkah', cell: (row) => row.steps.length },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Alur Persetujuan"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Persetujuan' }, { label: 'Alur Persetujuan' }]}
      />

      <Card title="Daftar Alur" description="Konfigurasi alur persetujuan (baca saja pada tahap ini)" noPadding>
        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada alur persetujuan.'}
          />
        )}
      </Card>
    </div>
  )
}
