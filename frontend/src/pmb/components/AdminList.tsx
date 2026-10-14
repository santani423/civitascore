import { useEffect, useState, type ReactNode } from 'react'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { pmbAdmin, type Params } from '@/pmb/api/admin'
import type { Paginated } from '@/pmb/api/types'
import { describeError } from '@/pmb/components/useAction'
import type { NormalizedApiError } from '@/services/api'

export interface ListFilter {
  name: string
  label: string
  type?: 'text' | 'select' | 'date' | 'checkbox'
  options?: Array<{ value: string; label: string }>
}

export interface AdminListProps<T> {
  path: string
  columns: DataTableColumn<T>[]
  rowKey: (row: T) => string
  filters?: ListFilter[]
  search?: boolean
  baseParams?: Params
  emptyMessage?: string
  itemLabel?: string
  /** Ubah nilai ini untuk memuat ulang (mis. setelah aksi). */
  reloadKey?: number
  toolbar?: ReactNode
  onLoaded?: (result: Paginated<T>) => void
}

/** Daftar admin berpaginasi dengan filter datar (sesuai endpoint /pmb/admin). */
export function AdminList<T>({ path, columns, rowKey, filters = [], search = true, baseParams, emptyMessage = 'Belum ada data.', itemLabel = 'data', reloadKey = 0, toolbar, onLoaded }: AdminListProps<T>) {
  const [page, setPage] = useState(1)
  const [draft, setDraft] = useState<Record<string, string>>({})
  const [applied, setApplied] = useState<Record<string, string>>({})
  const [result, setResult] = useState<Paginated<T> | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const baseKey = JSON.stringify(baseParams ?? {})

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    pmbAdmin.list<T>(path, { ...(baseParams ?? {}), ...applied, page })
      .then((r) => {
        if (cancelled) return
        setResult(r)
        onLoaded?.(r)
      })
      .catch((err: NormalizedApiError) => !cancelled && setError(describeError(err)))
      .finally(() => !cancelled && setLoading(false))

    return () => {
      cancelled = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [path, page, applied, reloadKey, baseKey])

  const apply = () => {
    setPage(1)
    setApplied(Object.fromEntries(Object.entries(draft).filter(([, v]) => v !== '')))
  }

  return (
    <Card noPadding>
      {(filters.length > 0 || search || toolbar) && (
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          {search && <Input label="Cari" value={draft.search ?? ''} onChange={(e) => setDraft({ ...draft, search: e.target.value })} onKeyDown={(e) => e.key === 'Enter' && apply()} />}
          {filters.map((filter) =>
            filter.type === 'select' ? (
              <Select key={filter.name} label={filter.label} options={filter.options ?? []} placeholder="Semua" value={draft[filter.name] ?? ''} onChange={(e) => setDraft({ ...draft, [filter.name]: e.target.value })} />
            ) : (
              <Input key={filter.name} label={filter.label} type={filter.type === 'date' ? 'date' : 'text'} value={draft[filter.name] ?? ''} onChange={(e) => setDraft({ ...draft, [filter.name]: e.target.value })} />
            ),
          )}
          {(filters.length > 0 || search) && <Button variant="outline" onClick={apply}>Terapkan</Button>}
          {toolbar && <div className="ml-auto flex flex-wrap gap-2">{toolbar}</div>}
        </div>
      )}
      {error ? (
        <Alert variant="danger" className="m-4">{error}</Alert>
      ) : (
        <DataTable columns={columns} data={result?.data ?? []} rowKey={rowKey} emptyMessage={loading ? 'Memuat...' : emptyMessage} />
      )}
      <ListPagination meta={result?.meta ?? null} page={page} onPageChange={setPage} itemLabel={itemLabel} />
    </Card>
  )
}
