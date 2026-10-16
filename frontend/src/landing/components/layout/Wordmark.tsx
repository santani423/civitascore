import { cn } from '@/utils/cn'

/**
 * Interim typographic wordmark for Civitas One Atma (not an official
 * university logo): an open ring for the campus ecosystem, a stroke for
 * "One", and a gold point that nods to the crest's dove and rays.
 */
export function LogoMark({ size = 34, className }: { size?: number; className?: string }) {
  return (
    <svg width={size} height={size} viewBox="0 0 40 40" aria-hidden className={cn('shrink-0', className)}>
      <rect width="40" height="40" rx="11" className="fill-lp-brand" />
      <path d="M27.66 13.57A10 10 0 1 0 27.66 26.43" fill="none" className="stroke-lp-onbrand" strokeWidth="3.2" strokeLinecap="round" />
      <path d="M20 15.2v9.6" className="stroke-lp-onbrand" strokeWidth="3.2" strokeLinecap="round" />
      <circle cx="29.7" cy="20" r="2.6" className="fill-lp-gold" />
    </svg>
  )
}

export function Wordmark({ className, size = 'md', tone = 'default' }: { className?: string; size?: 'md' | 'lg'; tone?: 'default' | 'band' }) {
  return (
    <span className={cn('inline-flex items-center gap-2.5', className)}>
      <LogoMark size={size === 'lg' ? 40 : 34} />
      <span
        className={cn(
          'whitespace-nowrap font-display font-bold tracking-[-0.02em]',
          size === 'lg' ? 'text-[19px]' : 'text-[16.5px]',
          tone === 'band' ? 'text-lp-band-fg' : 'text-lp-fg',
        )}
      >
        Civitas One <span className={cn('font-semibold', tone === 'band' ? 'text-lp-gold' : 'text-lp-brand-text')}>Atma</span>
      </span>
    </span>
  )
}
