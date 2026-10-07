import type { ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'
import { tone as tones, statusTone, type UiTone } from '../../lib/tones'
import { useI18n, type TKey } from '../../i18n'

interface BadgeProps {
  tone?: UiTone
  icon?: LucideIcon
  dot?: boolean
  size?: 'sm' | 'md'
  className?: string
  children: ReactNode
}

export function Badge({ tone = 'neutral', icon: Icon, dot, size = 'sm', className, children }: BadgeProps) {
  const t = tones[tone]
  return (
    <span
      className={cn(
        'inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full font-medium ring-1 ring-inset',
        size === 'sm' ? 'h-6 px-2.5 text-[12px]' : 'h-7 px-3 text-[13px]',
        t.soft,
        t.ring,
        className,
      )}
    >
      {dot && <span aria-hidden className={cn('h-1.5 w-1.5 rounded-full', t.dot)} />}
      {Icon && <Icon size={12} aria-hidden />}
      {children}
    </span>
  )
}

/** Translated status pill — color is never the only signal (always has a label + dot). */
export function StatusBadge({ status, className }: { status: string; className?: string }) {
  const { t } = useI18n()
  return (
    <Badge tone={statusTone[status] ?? 'neutral'} dot className={className}>
      {t(`status.${status}` as TKey)}
    </Badge>
  )
}
