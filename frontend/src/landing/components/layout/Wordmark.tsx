import logoAtmaJaya from '@/assets/images/logo-atmajaya.gif'
import { cn } from '@/utils/cn'

/** The official Atma Jaya crest (same asset as the application sidebar and sign-in page), never redrawn or recolored. */
export function Crest({ className }: { className?: string }) {
  return <img src={logoAtmaJaya} alt="" width={308} height={529} decoding="async" className={cn('w-auto shrink-0 select-none', className)} draggable={false} />
}

/**
 * Crest + "One Atma Jaya" product name + the university's full name.
 * Decorative (`aria-hidden` at the call site); the link around it carries the accessible name.
 */
export function Wordmark({ className, size = 'md', tone = 'default' }: { className?: string; size?: 'md' | 'lg'; tone?: 'default' | 'band' }) {
  const band = tone === 'band'
  return (
    <span className={cn('inline-flex min-w-0 items-center gap-3', className)}>
      <Crest className={size === 'lg' ? 'h-14' : 'h-11'} />
      <span className="flex min-w-0 flex-col leading-none">
        <span
          className={cn(
            'whitespace-nowrap font-display font-extrabold uppercase tracking-[-0.01em]',
            size === 'lg' ? 'text-[21px]' : 'text-[17px]',
            band ? 'text-lp-band-fg' : 'text-lp-fg',
          )}
        >
          <span className={band ? 'text-lp-accent' : 'text-lp-brand-text'}>One</span> Atma Jaya
        </span>
        <span className={cn('mt-1 truncate font-medium', size === 'lg' ? 'text-[12.5px]' : 'text-[11px]', band ? 'text-lp-band-muted' : 'text-lp-subtle')}>
          Universitas Katolik Indonesia Atma Jaya
        </span>
      </span>
    </span>
  )
}
