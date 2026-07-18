import { cn } from '@/utils/cn'

export interface AvatarProps {
  name: string
  src?: string | null
  size?: 'sm' | 'md' | 'lg'
  className?: string
}

const SIZE_CLASSES = {
  sm: 'size-8 text-xs',
  md: 'size-10 text-sm',
  lg: 'size-14 text-lg',
} as const

function getInitials(name: string): string {
  const parts = name.trim().split(/\s+/)
  const initials = parts.slice(0, 2).map((part) => part[0]?.toUpperCase() ?? '')
  return initials.join('')
}

export function Avatar({ name, src, size = 'md', className }: AvatarProps) {
  if (src) {
    return (
      <img
        src={src}
        alt={name}
        className={cn('rounded-full object-cover ring-1 ring-border', SIZE_CLASSES[size], className)}
      />
    )
  }

  return (
    <span
      role="img"
      aria-label={name}
      className={cn(
        'inline-flex items-center justify-center rounded-full bg-primary-100 font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-200',
        SIZE_CLASSES[size],
        className,
      )}
    >
      {getInitials(name)}
    </span>
  )
}
