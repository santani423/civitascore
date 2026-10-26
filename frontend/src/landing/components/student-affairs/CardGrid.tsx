import { useEffect, useRef, useState, type ReactNode } from 'react'
import { ChevronDown, RotateCcw, SearchX } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import { Button } from '../ui/Button'

interface CardGridProps<T> {
  items: T[]
  getKey: (item: T) => string
  render: (item: T) => ReactNode
  pageSize: number
  /** Changes whenever the filters change, so the list starts again from the first page. */
  resetKey: string
}

/**
 * Responsive card grid (1 / 2 / 3 columns) that reveals `pageSize` more
 * cards per "Muat Lebih Banyak". Focus moves to the first newly shown card,
 * so keyboard and screen-reader users continue where the new cards begin.
 * Cards mark their main link with `data-card-link`.
 */
export function CardGrid<T>({ items, getKey, render, pageSize, resetKey }: CardGridProps<T>) {
  const { m } = useLandingI18n()
  const [page, setPage] = useState({ key: resetKey, count: pageSize })
  const count = page.key === resetKey ? page.count : pageSize
  const visible = items.slice(0, count)
  const remaining = items.length - visible.length
  const listRef = useRef<HTMLUListElement>(null)
  const focusIndex = useRef<number | null>(null)

  useEffect(() => {
    if (focusIndex.current === null) return
    listRef.current?.querySelectorAll<HTMLElement>('[data-card-link]')[focusIndex.current]?.focus()
    focusIndex.current = null
  }, [count])

  return (
    <>
      <ul ref={listRef} className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        {visible.map((item) => (
          <li key={getKey(item)} className="flex">
            {render(item)}
          </li>
        ))}
      </ul>
      {remaining > 0 && (
        <div className="mt-10 flex justify-center">
          <Button
            variant="secondary"
            size="lg"
            onClick={() => {
              focusIndex.current = visible.length
              setPage({ key: resetKey, count: count + pageSize })
            }}
          >
            {m.affairs.loadMore}
            <span className="font-medium text-lp-subtle">{fill(m.affairs.loadMoreCount, { n: Math.min(remaining, pageSize) })}</span>
            <ChevronDown size={17} aria-hidden />
          </Button>
        </div>
      )}
    </>
  )
}

export function EmptyResults({ noun, onReset }: { noun: string; onReset: () => void }) {
  const { m } = useLandingI18n()
  const e = m.affairs.empty
  return (
    <div className="flex flex-col items-center rounded-lp-lg border border-dashed border-lp-strong bg-lp-surface px-6 py-14 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-full bg-lp-brand-soft text-lp-brand-text">
        <SearchX size={24} aria-hidden />
      </span>
      <h3 className="lp-h3 mt-5 text-lp-fg">{e.title}</h3>
      <p className="lp-small mt-2 max-w-md text-lp-muted">{fill(e.body, { noun })}</p>
      <Button variant="primary" className="mt-6" onClick={onReset}>
        <RotateCcw size={16} aria-hidden />
        {m.affairs.filters.reset}
      </Button>
    </div>
  )
}
