import { useState } from 'react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { auditLogService } from '@/services/auditLogService'
import type { AuditAction, AuditLogEntry } from '@/types/auditLog'
import { ROUTES } from '@/constants/routes'
import { formatRelativeTime, humanizeSlug } from '@/utils/formatters'

const ACTION_OPTIONS: AuditAction[] = ['created', 'updated', 'deleted', 'restored', 'exported', 'imported']

const ACTION_VARIANT: Record<AuditAction, BadgeVariant> = {
  created: 'success',
  updated: 'info',
  deleted: 'danger',
  restored: 'warning',
  exported: 'neutral',
  imported: 'neutral',
}

export function AuditLogPage() {
  const [actionFilter, setActionFilter] = useState('')
  const [entityFilter, setEntityFilter] = useState('')
  const [detail, setDetail] = useState<AuditLogEntry | null>(null)

  const list = usePaginatedList<AuditLogEntry>({ fetcher: auditLogService.index })

  const applyFilters = () => {
    const filter: Record<string, string> = {}
    if (actionFilter) filter.action = actionFilter
    if (entityFilter) filter.auditable_type = entityFilter
    list.setFilter(filter)
  }

  const columns: DataTableColumn<AuditLogEntry>[] = [
    {
      header: 'Waktu',
      cell: (row) => formatRelativeTime(row.created_at),
    },
    { header: 'Pengguna', cell: (row) => row.user_name ?? 'Sistem' },
    { header: 'Aksi', cell: (row) => <Badge variant={ACTION_VARIANT[row.action]}>{row.action}</Badge> },
    { header: 'Entitas', cell: (row) => humanizeSlug(row.auditable_type.split('\\').pop() ?? row.auditable_type) },
    {
      header: '',
      cell: (row) => (
        <Button variant="ghost" size="sm" onClick={() => setDetail(row)}>
          Detail
        </Button>
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Audit Log"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Audit Log' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Select
            label="Aksi"
            value={actionFilter}
            onChange={(event) => setActionFilter(event.target.value)}
            options={ACTION_OPTIONS.map((value) => ({ value, label: value }))}
            placeholder="Semua aksi"
          />
          <Input
            label="Tipe Entitas"
            placeholder="mis. Modules\\UserManagement\\Models\\Role"
            value={entityFilter}
            onChange={(event) => setEntityFilter(event.target.value)}
          />
          <Button variant="outline" onClick={applyFilters}>
            Terapkan Filter
          </Button>
        </div>

        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada entri audit log.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page} ({list.meta.total} entri)
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

      <Modal open={detail !== null} onClose={() => setDetail(null)} title="Detail Audit Log" className="max-w-lg">
        {detail && (
          <div className="flex flex-col gap-3 text-sm">
            <div>
              <span className="font-medium text-ink-primary">Pengguna:</span> {detail.user_name ?? 'Sistem'}
            </div>
            <div>
              <span className="font-medium text-ink-primary">Aksi:</span> {detail.action}
            </div>
            <div>
              <span className="font-medium text-ink-primary">Entitas:</span> {detail.auditable_type} #{detail.auditable_id}
            </div>
            <div>
              <span className="font-medium text-ink-primary">IP:</span> {detail.ip_address ?? '-'}
            </div>
            {detail.old_values && (
              <div>
                <p className="mb-1 font-medium text-ink-primary">Nilai Lama</p>
                <pre className="max-h-40 overflow-auto rounded-lg bg-surface-hover p-3 text-xs">
                  {JSON.stringify(detail.old_values, null, 2)}
                </pre>
              </div>
            )}
            {detail.new_values && (
              <div>
                <p className="mb-1 font-medium text-ink-primary">Nilai Baru</p>
                <pre className="max-h-40 overflow-auto rounded-lg bg-surface-hover p-3 text-xs">
                  {JSON.stringify(detail.new_values, null, 2)}
                </pre>
              </div>
            )}
          </div>
        )}
      </Modal>
    </div>
  )
}
