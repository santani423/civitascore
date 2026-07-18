import type { ReactNode } from 'react'
import { CheckCircle2, Info, AlertTriangle, XCircle, X, type LucideIcon } from 'lucide-react'
import { cn } from '@/utils/cn'

export type AlertVariant = 'info' | 'success' | 'warning' | 'danger'

export interface AlertProps {
  variant?: AlertVariant
  title?: string
  children?: ReactNode
  onDismiss?: () => void
  className?: string
}

const VARIANT_CONFIG: Record<AlertVariant, { icon: LucideIcon; classes: string }> = {
  info: { icon: Info, classes: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200' },
  success: {
    icon: CheckCircle2,
    classes: 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200',
  },
  warning: {
    icon: AlertTriangle,
    classes: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200',
  },
  danger: { icon: XCircle, classes: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' },
}

export function Alert({ variant = 'info', title, children, onDismiss, className }: AlertProps) {
  const { icon: Icon, classes } = VARIANT_CONFIG[variant]

  return (
    <div role="alert" className={cn('flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', classes, className)}>
      <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
      <div className="flex-1">
        {title && <p className="font-medium">{title}</p>}
        {children && <p className={cn(title && 'mt-0.5 opacity-90')}>{children}</p>}
      </div>
      {onDismiss && (
        <button type="button" onClick={onDismiss} aria-label="Tutup peringatan" className="shrink-0 opacity-70 hover:opacity-100">
          <X className="size-4" />
        </button>
      )}
    </div>
  )
}
