import { useMemo, useState, type ReactNode } from 'react'
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight } from 'lucide-react'
import { cn } from '@/utils/cn'
import { useI18n } from '../../i18n'

export interface Column<T> {
  key: string
  header: string
  render: (row: T) => ReactNode
  /** Provide to make the column sortable. */
  sortValue?: (row: T) => string | number
  align?: 'left' | 'right' | 'center'
  className?: string
  /** On mobile the first column is the card title; others become label/value rows unless hidden. */
  hideOnMobile?: boolean
}

interface DataTableProps<T> {
  columns: Column<T>[]
  rows: T[]
  rowKey: (row: T) => string
  caption: string
  pageSize?: number
  initialSort?: { key: string; dir: 'asc' | 'desc' }
  onRowClick?: (row: T) => void
  empty?: ReactNode
  footer?: ReactNode
}

export function DataTable<T>({ columns, rows, rowKey, caption, pageSize = 8, initialSort, onRowClick, empty, footer }: DataTableProps<T>) {
  const { t } = useI18n()
  const [sort, setSort] = useState(initialSort)
  const [page, setPage] = useState(1)

  const sorted = useMemo(() => {
    const col = columns.find((c) => c.key === sort?.key)
    if (!col?.sortValue || !sort) return rows
    const getter = col.sortValue
    return [...rows].sort((a, b) => {
      const va = getter(a)
      const vb = getter(b)
      const cmp = typeof va === 'number' && typeof vb === 'number' ? va - vb : String(va).localeCompare(String(vb))
      return sort.dir === 'asc' ? cmp : -cmp
    })
  }, [rows, columns, sort])

  const pages = Math.max(1, Math.ceil(sorted.length / pageSize))
  const current = Math.min(page, pages)
  const visible = sorted.slice((current - 1) * pageSize, current * pageSize)

  const toggleSort = (key: string) => {
    setPage(1)
    setSort((s) => (s?.key === key ? { key, dir: s.dir === 'asc' ? 'desc' : 'asc' } : { key, dir: 'asc' }))
  }

  if (rows.length === 0 && empty) return <>{empty}</>

  const [titleCol, ...restCols] = columns

  return (
    <div>
      {/* Desktop / tablet: real table */}
      <div className="hidden overflow-x-auto md:block">
        <table className="w-full border-collapse text-left">
          <caption className="sr-only">{caption}</caption>
          <thead>
            <tr className="border-b border-one-line bg-one-sunken/60">
              {columns.map((col) => {
                const active = sort?.key === col.key
                const ariaSort = active ? (sort.dir === 'asc' ? 'ascending' : 'descending') : col.sortValue ? 'none' : undefined
                return (
                  <th
                    key={col.key}
                    scope="col"
                    aria-sort={ariaSort}
                    className={cn(
                      'whitespace-nowrap px-5 py-3 text-[12px] font-semibold uppercase tracking-wide text-one-subtle',
                      col.align === 'right' && 'text-right',
                      col.align === 'center' && 'text-center',
                    )}
                  >
                    {col.sortValue ? (
                      <button
                        type="button"
                        onClick={() => toggleSort(col.key)}
                        className={cn('inline-flex items-center gap-1 rounded uppercase hover:text-one-fg', active && 'text-one-fg')}
                        aria-label={`${col.header} — ${active && sort.dir === 'asc' ? t('common.sortDesc') : t('common.sortAsc')}`}
                      >
                        {col.header}
                        {active ? (
                          sort.dir === 'asc' ? <ArrowUp size={13} aria-hidden /> : <ArrowDown size={13} aria-hidden />
                        ) : (
                          <ArrowUpDown size={13} aria-hidden className="opacity-50" />
                        )}
                      </button>
                    ) : (
                      col.header
                    )}
                  </th>
                )
              })}
            </tr>
          </thead>
          <tbody>
            {visible.map((row) => (
              <tr
                key={rowKey(row)}
                onClick={onRowClick ? () => onRowClick(row) : undefined}
                className={cn('border-b border-one-line transition-colors last:border-0', onRowClick && 'cursor-pointer hover:bg-one-sunken/50')}
              >
                {columns.map((col) => (
                  <td
                    key={col.key}
                    className={cn(
                      'px-5 py-3.5 align-middle text-[14px] text-one-fg',
                      col.align === 'right' && 'text-right tabular',
                      col.align === 'center' && 'text-center',
                      col.className,
                    )}
                  >
                    {col.render(row)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Mobile: stacked cards, same data */}
      <ul className="divide-y divide-one-line md:hidden" aria-label={caption}>
        {visible.map((row) => (
          <li key={rowKey(row)}>
            <div
              role={onRowClick ? 'button' : undefined}
              tabIndex={onRowClick ? 0 : undefined}
              onClick={onRowClick ? () => onRowClick(row) : undefined}
              onKeyDown={onRowClick ? (e) => e.key === 'Enter' && onRowClick(row) : undefined}
              className="space-y-2 px-4 py-4"
            >
              <div className="text-[14px] font-medium text-one-fg">{titleCol.render(row)}</div>
              <dl className="grid grid-cols-2 gap-x-4 gap-y-1.5">
                {restCols
                  .filter((c) => !c.hideOnMobile)
                  .map((col) => (
                    <div key={col.key} className="min-w-0">
                      <dt className="text-[11.5px] text-one-subtle">{col.header}</dt>
                      <dd className="truncate text-[13.5px] text-one-fg">{col.render(row)}</dd>
                    </div>
                  ))}
              </dl>
            </div>
          </li>
        ))}
      </ul>

      {(pages > 1 || footer) && (
        <div className="flex flex-col gap-3 border-t border-one-line px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
          <p className="text-[13px] text-one-subtle tabular">
            {t('common.showing', {
              from: sorted.length === 0 ? 0 : (current - 1) * pageSize + 1,
              to: Math.min(current * pageSize, sorted.length),
              total: sorted.length,
            })}
          </p>
          {footer}
          {pages > 1 && <Pagination page={current} pages={pages} onChange={setPage} />}
        </div>
      )}
    </div>
  )
}

export function Pagination({ page, pages, onChange }: { page: number; pages: number; onChange: (p: number) => void }) {
  const { t } = useI18n()
  const btn =
    'inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-[13px] font-medium tabular transition-colors disabled:pointer-events-none disabled:opacity-40'
  return (
    <nav aria-label={t('common.pagination')} className="flex items-center gap-1">
      <button type="button" className={cn(btn, 'text-one-muted hover:bg-one-sunken')} disabled={page <= 1} onClick={() => onChange(page - 1)} aria-label={t('common.previous')}>
        <ChevronLeft size={16} aria-hidden />
      </button>
      {Array.from({ length: pages }, (_, i) => i + 1).map((p) => (
        <button
          key={p}
          type="button"
          onClick={() => onChange(p)}
          aria-current={p === page ? 'page' : undefined}
          aria-label={t('common.pageOf', { page: p, total: pages })}
          className={cn(btn, p === page ? 'bg-one-brand text-one-onbrand' : 'text-one-muted hover:bg-one-sunken')}
        >
          {p}
        </button>
      ))}
      <button type="button" className={cn(btn, 'text-one-muted hover:bg-one-sunken')} disabled={page >= pages} onClick={() => onChange(page + 1)} aria-label={t('common.next')}>
        <ChevronRight size={16} aria-hidden />
      </button>
    </nav>
  )
}
