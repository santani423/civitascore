import { useCallback } from 'react'
import { Download, Pencil, Plus, RotateCcw, Trash2, Upload, type LucideIcon } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { useFetch } from '@/hooks/useFetch'
import { auditLogService } from '@/services/auditLogService'
import type { AuditAction } from '@/types/auditLog'
import { formatRelativeTime } from '@/utils/formatters'

const ACTIVITY_LIMIT = 6

const ACTION_ICON: Record<AuditAction, LucideIcon> = {
  created: Plus,
  updated: Pencil,
  deleted: Trash2,
  restored: RotateCcw,
  exported: Download,
  imported: Upload,
}

const ACTION_LABEL: Record<AuditAction, string> = {
  created: 'Data ditambahkan',
  updated: 'Data diubah',
  deleted: 'Data dihapus',
  restored: 'Data dipulihkan',
  exported: 'Data diekspor',
  imported: 'Data diimpor',
}

/** `Modules\Finance\Models\InvoiceAdjustment` -> "Invoice Adjustment". */
function entityLabel(auditableType: string): string {
  const shortName = auditableType.split('\\').pop() ?? auditableType

  return shortName.replace(/([a-z])([A-Z])/g, '$1 $2')
}

/** Aktivitas terbaru diambil dari log audit (satu-satunya feed kejadian di backend). */
export function RecentActivityList() {
  const fetchActivities = useCallback(() => auditLogService.index({ per_page: ACTIVITY_LIMIT }), [])
  const { data, isLoading, error } = useFetch(fetchActivities)
  const items = data?.data ?? []

  return (
    <Card title="Aktivitas Terbaru" description="Perubahan data terbaru di seluruh sistem" noPadding>
      {error ? (
        <p className="px-5 py-3.5 text-sm text-danger">{error}</p>
      ) : isLoading ? (
        <p className="px-5 py-3.5 text-sm text-ink-secondary">Memuat...</p>
      ) : items.length === 0 ? (
        <p className="px-5 py-3.5 text-sm text-ink-secondary">Belum ada aktivitas tercatat.</p>
      ) : (
        <ul className="divide-y divide-border">
          {items.map((entry) => {
            const Icon = ACTION_ICON[entry.action] ?? Pencil

            return (
              <li key={entry.id} className="flex items-start gap-3 px-5 py-3.5">
                <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-950">
                  <Icon className="size-4" aria-hidden />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-ink-primary">
                    {ACTION_LABEL[entry.action] ?? entry.action} · {entityLabel(entry.auditable_type)}
                  </p>
                  {entry.reason && <p className="text-xs text-ink-secondary">{entry.reason}</p>}
                  <p className="mt-1 text-xs text-ink-tertiary">
                    {entry.user_name ?? 'Sistem'} &middot; {formatRelativeTime(entry.created_at)}
                  </p>
                </div>
              </li>
            )
          })}
        </ul>
      )}
    </Card>
  )
}
