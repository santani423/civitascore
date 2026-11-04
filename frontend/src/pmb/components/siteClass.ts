import { cn } from '@/utils/cn'

/** Kartu bergaya landing page; `interactive` untuk kartu yang seluruhnya berupa tautan. */
export function cardClass(interactive = false, className?: string): string {
  return cn(
    'rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm',
    interactive && 'group block transition-[box-shadow,transform,border-color] duration-300 hover:-translate-y-0.5 hover:border-lp-brand/30 hover:shadow-lp-lg',
    className,
  )
}

/** Tautan teks oranye bergaya landing (mis. "Masuk", "Lupa kata sandi?"). */
export const linkClass = 'font-semibold text-lp-brand-text underline-offset-4 hover:underline'
