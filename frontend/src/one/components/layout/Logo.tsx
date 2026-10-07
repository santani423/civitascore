import { cn } from '@/utils/cn'

/** Civitas One mark: an open ring (the campus ecosystem) completed by a gold point (you). */
export function LogoMark({ size = 36, className }: { size?: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 40 40" aria-hidden className={cn('shrink-0', className)}>
      <rect width="40" height="40" rx="11" className="fill-one-brand" />
      <path d="M27.8 13.2A10 10 0 1 0 30 20" fill="none" stroke="white" strokeWidth="3.2" strokeLinecap="round" />
      <path d="M20 14.5v11" stroke="white" strokeWidth="3.2" strokeLinecap="round" opacity="0.9" />
      <circle cx="29.6" cy="15.4" r="2.6" className="fill-one-spark" />
    </svg>
  )
}

export function Wordmark({ className, inverse }: { className?: string; inverse?: boolean }) {
  return (
    <span className={cn('font-display text-[15px] font-extrabold tracking-[0.14em]', inverse ? 'text-white' : 'text-one-fg', className)}>
      CIVITAS <span className={inverse ? 'text-one-spark' : 'text-one-royal'}>ONE</span>
    </span>
  )
}
