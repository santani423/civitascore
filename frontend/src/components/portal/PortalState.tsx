import type { ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { RefreshCw } from 'lucide-react'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { EmptyState } from '@/components/ui/EmptyState'
import { Skeleton, SkeletonCard } from '@/components/ui/Skeleton'

/** Kerangka pemuatan seragam untuk halaman Portal Mahasiswa. */
export function PortalLoading({ cards = 3, rows = 4 }: { cards?: number; rows?: number }) {
  return (
    <div className="flex flex-col gap-4" aria-busy="true" aria-label="Memuat data">
      {cards > 0 && (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: cards }, (_, index) => (
            <SkeletonCard key={index} />
          ))}
        </div>
      )}
      <div className="rounded-xl border border-border bg-surface p-5 shadow-card">
        {Array.from({ length: rows }, (_, index) => (
          <Skeleton key={index} className="mb-3 h-4 w-full last:mb-0" />
        ))}
      </div>
    </div>
  )
}

/** Pesan galat yang ramah + tombol coba lagi (pesan berasal dari backend, sudah bahasa Indonesia). */
export function PortalError({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <Alert variant="danger" title="Data tidak dapat dimuat">
      <span className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <span>{message}</span>
        {onRetry && (
          <Button size="sm" variant="outline" leftIcon={<RefreshCw className="size-3.5" />} onClick={onRetry}>
            Coba lagi
          </Button>
        )}
      </span>
    </Alert>
  )
}

export function PortalEmpty({
  icon,
  title,
  description,
  action,
}: {
  icon?: LucideIcon
  title: string
  description?: string
  action?: ReactNode
}) {
  return (
    <div className="rounded-xl border border-dashed border-border bg-surface">
      <EmptyState icon={icon} title={title} description={description} action={action} />
    </div>
  )
}

/** Pasangan label–nilai untuk kartu informasi (profil, ringkasan akademik). */
export function InfoList({ items, columns = 2 }: { items: { label: string; value: ReactNode }[]; columns?: 1 | 2 | 3 }) {
  const grid = columns === 1 ? 'sm:grid-cols-1' : columns === 3 ? 'sm:grid-cols-2 lg:grid-cols-3' : 'sm:grid-cols-2'

  return (
    <dl className={`grid grid-cols-1 gap-x-6 gap-y-3 text-sm ${grid}`}>
      {items.map((item) => (
        <div key={item.label} className="min-w-0">
          <dt className="text-xs text-ink-tertiary">{item.label}</dt>
          <dd className="mt-0.5 break-words font-medium text-ink-primary">{item.value ?? '-'}</dd>
        </div>
      ))}
    </dl>
  )
}

export function ProgressBar({ value, max = 100, tone = 'primary' }: { value: number; max?: number; tone?: 'primary' | 'warning' | 'danger' }) {
  const percent = max > 0 ? Math.min(100, Math.max(0, (value / max) * 100)) : 0
  const color = tone === 'danger' ? 'bg-danger' : tone === 'warning' ? 'bg-amber-500' : 'bg-primary'

  return (
    <div className="h-2 w-full overflow-hidden rounded-full bg-surface-hover" role="progressbar" aria-valuenow={Math.round(percent)} aria-valuemin={0} aria-valuemax={100}>
      <div className={`h-full rounded-full transition-all ${color}`} style={{ width: `${percent}%` }} />
    </div>
  )
}
