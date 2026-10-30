import { ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'

/** 1 … 4 5 6 … 12: first, last and the current page with its neighbors. */
function pageItems(page: number, total: number): Array<number | 'gap'> {
  const wanted = [...new Set([1, total, page - 1, page, page + 1])].filter((n) => n >= 1 && n <= total).sort((a, b) => a - b)
  return wanted.flatMap((n, i): Array<number | 'gap'> => (i > 0 && n - wanted[i - 1] > 1 ? ['gap', n] : [n]))
}

const stepClass =
  'inline-flex h-11 min-w-11 items-center justify-center gap-1.5 rounded-lp-sm border border-lp-strong bg-lp-surface px-3 text-[0.875rem] font-semibold text-lp-fg transition-colors hover:border-lp-brand/40 hover:text-lp-brand-text disabled:pointer-events-none disabled:opacity-40'

/** Previous / numbers / next. Phones keep previous and next around a "Page 2 of 3" line. */
export function Pagination({ page, total, onChange, className }: { page: number; total: number; onChange: (page: number) => void; className?: string }) {
  const { m } = useLandingI18n()
  const p = m.alumni.pagination
  if (total <= 1) return null

  return (
    <nav aria-label={p.label} className={cn('flex items-center justify-between gap-3 sm:justify-center', className)}>
      <button type="button" className={stepClass} disabled={page <= 1} onClick={() => onChange(page - 1)}>
        <ChevronLeft size={17} aria-hidden />
        <span>{p.previous}</span>
      </button>

      <p className="text-[0.875rem] font-medium text-lp-muted sm:hidden">{fill(p.status, { page, total })}</p>

      <ul className="hidden items-center gap-1.5 sm:flex">
        {pageItems(page, total).map((item, i) =>
          item === 'gap' ? (
            <li key={`gap-${i}`} aria-hidden className="px-1 text-lp-subtle">
              …
            </li>
          ) : (
            <li key={item}>
              <button
                type="button"
                aria-label={fill(p.page, { page: item })}
                aria-current={item === page ? 'page' : undefined}
                onClick={() => onChange(item)}
                className={cn(
                  'lp-tabular inline-flex h-11 w-11 items-center justify-center rounded-lp-sm text-[0.875rem] font-semibold transition-colors',
                  item === page ? 'bg-lp-brand text-lp-onbrand' : 'text-lp-muted hover:bg-lp-sunken hover:text-lp-fg',
                )}
              >
                {item}
              </button>
            </li>
          ),
        )}
      </ul>

      <button type="button" className={stepClass} disabled={page >= total} onClick={() => onChange(page + 1)}>
        <span>{p.next}</span>
        <ChevronRight size={17} aria-hidden />
      </button>
    </nav>
  )
}
