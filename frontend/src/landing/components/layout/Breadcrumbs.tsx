import { Link } from 'react-router-dom'
import { ChevronRight } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useLandingI18n } from '../../i18n'

export interface Crumb {
  label: string
  /** Omitted for the current page (always the last crumb). */
  to?: string
  /** For content-language labels, e.g. an Indonesian activity title on the English site. */
  lang?: string
}

/** Breadcrumb trail; `band` for the charcoal heroes, `default` on light surfaces. */
export function Breadcrumbs({ items, tone = 'default', className }: { items: Crumb[]; tone?: 'default' | 'band'; className?: string }) {
  const { m } = useLandingI18n()
  const band = tone === 'band'

  return (
    <nav aria-label={m.common.breadcrumb} className={className}>
      <ol className="flex flex-wrap items-center gap-x-1.5 text-[0.8125rem] font-medium">
        {items.map((crumb, i) => {
          const last = i === items.length - 1
          return (
            <li key={`${i}-${crumb.label}`} className="flex min-w-0 items-center gap-1.5">
              {i > 0 && <ChevronRight size={14} aria-hidden className={cn('shrink-0', band ? 'text-lp-band-muted' : 'text-lp-subtle')} />}
              {last || !crumb.to ? (
                <span aria-current={last ? 'page' : undefined} lang={crumb.lang} className={cn('min-w-0 py-1 [overflow-wrap:anywhere]', band ? 'text-lp-band-fg' : 'text-lp-fg')}>
                  {crumb.label}
                </span>
              ) : (
                <Link
                  to={crumb.to}
                  lang={crumb.lang}
                  className={cn('inline-flex min-h-11 items-center transition-colors', band ? 'text-lp-band-muted hover:text-lp-band-fg' : 'text-lp-muted hover:text-lp-brand-text')}
                >
                  {crumb.label}
                </Link>
              )}
            </li>
          )
        })}
      </ol>
    </nav>
  )
}
