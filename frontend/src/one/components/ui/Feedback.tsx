import type { ReactNode } from 'react'
import { CircleAlert, CircleCheck, Info, Inbox, RefreshCw, TriangleAlert, X, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'
import { useToastStore, type ToastTone } from '../../store/toast'
import { Button } from './Button'

/* ---------- Empty ---------- */

interface EmptyStateProps {
  icon?: LucideIcon
  title: string
  body?: string
  action?: ReactNode
  compact?: boolean
  className?: string
}

export function EmptyState({ icon: Icon = Inbox, title, body, action, compact, className }: EmptyStateProps) {
  return (
    <div className={cn('flex flex-col items-center justify-center text-center', compact ? 'px-4 py-8' : 'px-6 py-14', className)}>
      <div className="relative mb-4">
        <div aria-hidden className="absolute inset-0 scale-150 rounded-full bg-one-royal/5 blur-xl" />
        <div className="relative flex h-14 w-14 items-center justify-center rounded-2xl border border-one-line bg-one-card text-one-royal shadow-soft">
          <Icon size={24} aria-hidden strokeWidth={1.75} />
        </div>
      </div>
      <h3 className="t-h3 text-one-fg">{title}</h3>
      {body && <p className="t-small mt-1 max-w-sm text-one-subtle">{body}</p>}
      {action && <div className="mt-5">{action}</div>}
    </div>
  )
}

/* ---------- Error ---------- */

export function ErrorState({ onRetry, className }: { onRetry?: () => void; className?: string }) {
  const { t } = useI18n()
  return (
    <div role="alert" className={cn('flex flex-col items-center justify-center px-6 py-14 text-center', className)}>
      <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-one-bad/10 text-one-bad">
        <TriangleAlert size={24} aria-hidden strokeWidth={1.75} />
      </div>
      <h3 className="t-h3 text-one-fg">{t('states.errorTitle')}</h3>
      <p className="t-small mt-1 max-w-sm text-one-subtle">{t('states.errorBody')}</p>
      {onRetry && (
        <Button variant="secondary" icon={RefreshCw} onClick={onRetry} className="mt-5">
          {t('common.tryAgain')}
        </Button>
      )}
    </div>
  )
}

/* ---------- Skeletons ---------- */

export function Skeleton({ className }: { className?: string }) {
  return (
    <div aria-hidden className={cn('relative overflow-hidden rounded-lg bg-one-sunken', className)}>
      <div className="absolute inset-0 -translate-x-full animate-shimmer bg-gradient-to-r from-transparent via-one-line/60 to-transparent" />
    </div>
  )
}

function SkeletonFrame({ children, className }: { children: ReactNode; className?: string }) {
  const { t } = useI18n()
  return (
    <div role="status" aria-live="polite" className={className}>
      <span className="sr-only">{t('common.loading')}</span>
      {children}
    </div>
  )
}

export function CardGridSkeleton({ count = 6, media = true, className }: { count?: number; media?: boolean; className?: string }) {
  return (
    <SkeletonFrame className={cn('grid gap-4 sm:grid-cols-2 xl:grid-cols-3', className)}>
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="overflow-hidden rounded-2xl border border-one-line bg-one-card">
          {media && <Skeleton className="h-36 rounded-none" />}
          <div className="space-y-3 p-5">
            <Skeleton className="h-5 w-20 rounded-full" />
            <Skeleton className="h-5 w-4/5" />
            <Skeleton className="h-4 w-3/5" />
          </div>
        </div>
      ))}
    </SkeletonFrame>
  )
}

export function TableSkeleton({ rows = 6, cols = 5 }: { rows?: number; cols?: number }) {
  return (
    <SkeletonFrame className="overflow-hidden rounded-2xl border border-one-line bg-one-card">
      <div className="flex gap-4 border-b border-one-line bg-one-sunken/60 px-5 py-3.5">
        {Array.from({ length: cols }, (_, i) => (
          <Skeleton key={i} className="h-3.5 flex-1" />
        ))}
      </div>
      {Array.from({ length: rows }, (_, r) => (
        <div key={r} className="flex gap-4 border-b border-one-line px-5 py-4 last:border-0">
          {Array.from({ length: cols }, (_, c) => (
            <Skeleton key={c} className={cn('h-4 flex-1', c === 1 && 'flex-[2]')} />
          ))}
        </div>
      ))}
    </SkeletonFrame>
  )
}

export function StatsSkeleton({ count = 4 }: { count?: number }) {
  return (
    <SkeletonFrame className="grid grid-cols-2 gap-4 lg:grid-cols-4">
      {Array.from({ length: count }, (_, i) => (
        <div key={i} className="space-y-3 rounded-2xl border border-one-line bg-one-card p-5">
          <Skeleton className="h-9 w-9 rounded-xl" />
          <Skeleton className="h-3.5 w-20" />
          <Skeleton className="h-7 w-16" />
        </div>
      ))}
    </SkeletonFrame>
  )
}

export function DashboardSkeleton() {
  return (
    <SkeletonFrame className="space-y-6">
      <Skeleton className="h-44 rounded-3xl" />
      <StatsSkeleton />
      <div className="grid gap-6 lg:grid-cols-3">
        <Skeleton className="h-80 rounded-2xl lg:col-span-2" />
        <Skeleton className="h-80 rounded-2xl" />
      </div>
    </SkeletonFrame>
  )
}

export function NewsSkeleton() {
  return (
    <SkeletonFrame className="space-y-6">
      <div className="grid gap-6 lg:grid-cols-5">
        <Skeleton className="h-80 rounded-3xl lg:col-span-3" />
        <div className="space-y-4 lg:col-span-2">
          {Array.from({ length: 3 }, (_, i) => (
            <div key={i} className="flex gap-4">
              <Skeleton className="h-20 w-28 shrink-0 rounded-xl" />
              <div className="flex-1 space-y-2">
                <Skeleton className="h-3.5 w-16" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-2/3" />
              </div>
            </div>
          ))}
        </div>
      </div>
      <CardGridSkeleton count={3} />
    </SkeletonFrame>
  )
}

export function ProfileSkeleton() {
  return (
    <SkeletonFrame className="space-y-6">
      <div className="flex items-center gap-5 rounded-3xl border border-one-line bg-one-card p-6">
        <Skeleton className="h-24 w-24 rounded-full" />
        <div className="flex-1 space-y-3">
          <Skeleton className="h-6 w-48" />
          <Skeleton className="h-4 w-72 max-w-full" />
          <Skeleton className="h-4 w-56 max-w-full" />
        </div>
      </div>
      <Skeleton className="h-11 w-full max-w-lg" />
      <div className="grid gap-4 sm:grid-cols-2">
        {Array.from({ length: 6 }, (_, i) => (
          <Skeleton key={i} className="h-16" />
        ))}
      </div>
    </SkeletonFrame>
  )
}

export function ListSkeleton({ rows = 5 }: { rows?: number }) {
  return (
    <SkeletonFrame className="divide-y divide-one-line">
      {Array.from({ length: rows }, (_, i) => (
        <div key={i} className="flex items-center gap-4 py-4">
          <Skeleton className="h-10 w-10 shrink-0 rounded-xl" />
          <div className="flex-1 space-y-2">
            <Skeleton className="h-4 w-1/2" />
            <Skeleton className="h-3.5 w-1/3" />
          </div>
        </div>
      ))}
    </SkeletonFrame>
  )
}

/* ---------- Async boundary ---------- */

interface AsyncProps<T> {
  query: { status: 'loading' | 'error' | 'ready'; data: T; retry: () => void }
  skeleton: ReactNode
  isEmpty?: (data: T) => boolean
  empty?: ReactNode
  children: (data: T) => ReactNode
}

/** One place that decides loading → error → empty → content, for every page. */
export function Async<T>({ query, skeleton, isEmpty, empty, children }: AsyncProps<T>) {
  if (query.status === 'loading') return <>{skeleton}</>
  if (query.status === 'error') return <ErrorState onRetry={query.retry} />
  if (isEmpty?.(query.data) && empty) return <>{empty}</>
  return <>{children(query.data)}</>
}

/* ---------- Toasts ---------- */

const toastIcon: Record<ToastTone, { icon: LucideIcon; cls: string }> = {
  success: { icon: CircleCheck, cls: 'text-one-ok' },
  info: { icon: Info, cls: 'text-one-royal' },
  warning: { icon: TriangleAlert, cls: 'text-one-warn' },
  error: { icon: CircleAlert, cls: 'text-one-bad' },
}

export function Toaster() {
  const { t } = useI18n()
  const toasts = useToastStore((s) => s.toasts)
  const dismiss = useToastStore((s) => s.dismiss)
  return (
    <div
      aria-live="polite"
      aria-atomic="false"
      className="pointer-events-none fixed inset-x-4 bottom-20 z-[80] flex flex-col items-center gap-2 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:items-end lg:bottom-6"
    >
      {toasts.map((toast) => {
        const { icon: Icon, cls } = toastIcon[toast.tone]
        return (
          <div
            key={toast.id}
            role="status"
            className="pointer-events-auto flex w-full max-w-sm animate-rise-in items-start gap-3 rounded-2xl border border-one-line bg-one-elev px-4 py-3 shadow-overlay"
          >
            <Icon size={18} aria-hidden className={cn('mt-0.5 shrink-0', cls)} />
            <p className="flex-1 text-[14px] text-one-fg">{toast.message}</p>
            <button
              type="button"
              onClick={() => dismiss(toast.id)}
              aria-label={t('common.close')}
              className="-mr-1 rounded-lg p-1 text-one-subtle hover:bg-one-sunken hover:text-one-fg"
            >
              <X size={15} aria-hidden />
            </button>
          </div>
        )
      })}
    </div>
  )
}
