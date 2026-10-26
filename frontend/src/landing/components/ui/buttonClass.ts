import { cn } from '@/utils/cn'

export type ButtonVariant = 'primary' | 'secondary' | 'ghost' | 'band' | 'bandOutline'
export type ButtonSize = 'sm' | 'md' | 'lg'

/**
 * Shared button styling — also applied to <a>/<Link> elements that look like buttons.
 * `band` / `bandOutline` sit on charcoal or orange backgrounds (hero, CTA banner).
 */
export function buttonClass(variant: ButtonVariant = 'primary', size: ButtonSize = 'md', className?: string): string {
  return cn(
    'inline-flex select-none items-center justify-center gap-2 whitespace-nowrap rounded-lp-sm font-semibold',
    'transition-[background-color,border-color,color,box-shadow,transform] duration-200 ease-out active:translate-y-px',
    'disabled:pointer-events-none disabled:opacity-50',
    size === 'sm' && 'h-9 px-4 text-[13.5px]',
    size === 'md' && 'h-11 px-5 text-[14.5px]',
    size === 'lg' && 'h-12 px-6 text-[15px] sm:h-[3.25rem] sm:px-7',
    variant === 'primary' && 'bg-lp-brand text-lp-onbrand shadow-lp-sm hover:bg-lp-brand-hover hover:shadow-lp-md',
    variant === 'secondary' && 'border border-lp-strong bg-lp-surface text-lp-fg hover:border-lp-brand/40 hover:text-lp-brand-text',
    variant === 'ghost' && 'text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
    variant === 'band' && 'bg-lp-band-fg text-lp-band hover:bg-white',
    variant === 'bandOutline' && 'border border-lp-band-fg/60 text-lp-band-fg hover:border-lp-band-fg hover:bg-lp-band-fg/10',
    className,
  )
}
