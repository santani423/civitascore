import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { supportSessionService } from '@/services/tenancyService'
import type { SupportSession } from '@/types/tenancy'
import { ROUTES } from '@/constants/routes'
import { formatRelativeTime } from '@/utils/formatters'

export function SupportSessionsPage() {
  const list = usePaginatedList<SupportSession>({ fetcher: supportSessionService.index })

  const columns: DataTableColumn<SupportSession>[] = [
    {
      header: 'Universitas',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.university_name ?? row.university_id}</p>
          <p className="text-xs text-ink-tertiary">oleh {row.super_admin_name ?? row.super_admin_id}</p>
        </div>
      ),
    },
    { header: 'Alasan', cell: (row) => <span className="line-clamp-2 max-w-xs">{row.reason}</span> },
    { header: 'Mulai', cell: (row) => (row.started_at ? formatRelativeTime(row.started_at) : '-') },
    {
      header: 'Status',
      cell: (row) =>
        row.ended_at ? (
          <Badge variant="neutral">Berakhir {formatRelativeTime(row.ended_at)}</Badge>
        ) : (
          <Badge variant="warning">Sedang berlangsung</Badge>
        ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Keamanan Platform"
        description="Riwayat Support Session — setiap kali Super Admin masuk ke konteks satu universitas."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keamanan Platform' }]}
      />

      <Card title="Riwayat Support Session" noPadding>
        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada support session.'}
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
