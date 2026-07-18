import type { LucideIcon } from 'lucide-react'
import { ArrowUp, ArrowDown, Minus } from 'lucide-react'
import { cn } from '@/utils/cn'
import type { TrendDirection } from '@/types/dashboard'

export interface StatCardProps {
  label: string
  value: string
  icon: LucideIcon
  changePercent?: number
  trend?: TrendDirection
  caption?: string
}

const TREND_CLASSES: Record<TrendDirection, string> = {
  up: 'text-primary',
  down: 'text-danger',
  flat: 'text-ink-tertiary',
}

const TREND_ICON: Record<TrendDirection, LucideIcon> = {
  up: ArrowUp,
  down: ArrowDown,
  flat: Minus,
}

export function StatCard({ label, value, icon: Icon, changePercent, trend = 'flat', caption }: StatCardProps) {
  const TrendIcon = TREND_ICON[trend]

  return (
    <div className="rounded-xl border border-border bg-surface p-5 shadow-card transition-shadow hover:shadow-popover">
      <div className="flex items-start justify-between">
        <p className="text-sm font-medium text-ink-secondary">{label}</p>
        <div className="flex size-9 items-center justify-center rounded-lg bg-primary-50 text-primary dark:bg-primary-950">
          <Icon className="size-4.5" aria-hidden />
        </div>
      </div>

      <p className="mt-3 text-2xl font-semibold tracking-tight text-ink-primary">{value}</p>

      <div className="mt-2 flex items-center gap-1.5 text-xs">
        {typeof changePercent === 'number' && (
          <span className={cn('inline-flex items-center gap-0.5 font-medium', TREND_CLASSES[trend])}>
            <TrendIcon className="size-3" aria-hidden />
            {Math.abs(changePercent).toFixed(1)}%
          </span>
        )}
        {caption && <span className="text-ink-tertiary">{caption}</span>}
      </div>
    </div>
  )
}
