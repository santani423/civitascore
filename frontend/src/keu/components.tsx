import { useEffect, useState, type ReactNode } from 'react'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { ListPagination } from '@/components/ui/ListPagination'
import { PageHeader } from '@/components/ui/PageHeader'
import { describeError } from '@/pmb/components/useAction'
import { usePermission } from '@/hooks/usePermission'
import { ROUTES } from '@/constants/routes'
import { keuAdmin, type Params } from '@/keu/api'
import { KEU } from '@/keu/routes'
import type { Option, Paginated, StudentRef } from '@/keu/types'
import type { NormalizedApiError } from '@/services/api'


export function Can({ permission, children, fallback = null }: { permission: string | string[]; children: ReactNode; fallback?: ReactNode }) {
  return usePermission(permission) ? <>{children}</> : <>{fallback}</>
}

/** Kerangka halaman admin KEU (breadcrumb Dashboard › Keuangan › ...). */
export function KeuPage({ title, description, actions, children, trail = [] }: { title: string; description?: string; actions?: ReactNode; children: ReactNode; trail?: Array<{ label: string; path?: string }> }) {
  return (
    <div className="flex flex-col gap-5">
      <PageHeader title={title} description={description} actions={actions} breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Keuangan', path: KEU.admin.dashboard }, ...trail]} />
      {children}
    </div>
  )
}

const VARIANTS: Record<string, BadgeVariant> = {
  paid: 'success', success: 'success', applied: 'success', approved: 'success', published: 'success', posted: 'success', completed: 'success', matched: 'success',
  verified: 'success', active: 'success', closed: 'success', resolved: 'success',
  partial: 'warning', pending: 'warning', pending_approval: 'warning', submitted: 'warning', awaiting_verification: 'info', accruing: 'warning', exempted: 'warning',
  amount_mismatch: 'warning', pending_deposit: 'warning', exception: 'warning', unreconciled: 'warning',
  simulated: 'info', scheduled: 'info', open: 'info', crystallized: 'info', waived: 'info', ready: 'info',
  unpaid: 'danger', failed: 'danger', rejected: 'danger', reversed: 'danger', unidentified: 'danger', duplicate: 'danger', missing_in_system: 'danger', missing_in_statement: 'danger',
  draft: 'neutral', cancelled: 'neutral', expired: 'neutral', revoked: 'neutral', archived: 'neutral', superseded: 'neutral', stopped: 'neutral', inactive: 'neutral', skipped: 'neutral',
}

/** Status KEU — label dari API (Bahasa Indonesia), warna dari kode status. */
export function KeuBadge({ status, label, overdue }: { status: string | null | undefined; label?: string | null; overdue?: boolean }) {
  if (overdue) return <Badge variant="danger">Menunggak</Badge>

  return <Badge variant={(status && VARIANTS[status]) || 'neutral'}>{label ?? status?.replace(/_/g, ' ') ?? '—'}</Badge>
}

export interface KeuFilter {
  name: string
  label: string
  type?: 'text' | 'select' | 'date'
  options?: Option[]
}

export interface KeuListProps<T> {
  path: string
  columns: DataTableColumn<T>[]
  rowKey: (row: T) => string
  filters?: KeuFilter[]
  search?: boolean
  baseParams?: Params
  emptyMessage?: string
  itemLabel?: string
  reloadKey?: number
  toolbar?: ReactNode
  onLoaded?: (result: Paginated<T>) => void
  /** Filter awal (mis. dari query string tautan dashboard). */
  initialFilters?: Record<string, string>
}

/** Daftar berpaginasi `/finance/*` dengan filter datar (pola AdminList PMB). */
export function KeuList<T>({ path, columns, rowKey, filters = [], search = true, baseParams, emptyMessage = 'Belum ada data.', itemLabel = 'data', reloadKey = 0, toolbar, onLoaded, initialFilters = {} }: KeuListProps<T>) {
  const [page, setPage] = useState(1)
  const [draft, setDraft] = useState<Record<string, string>>(initialFilters)
  const [applied, setApplied] = useState<Record<string, string>>(initialFilters)
  const [result, setResult] = useState<Paginated<T> | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const baseKey = JSON.stringify(baseParams ?? {})

  useEffect(() => {
    let cancelled = false
    setLoading(true)
    setError(null)
    keuAdmin.list<T>(path, { ...(baseParams ?? {}), ...applied, page })
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
          {search && <Input label="Cari" placeholder="NIM, nama, nomor" value={draft.search ?? ''} onChange={(e) => setDraft({ ...draft, search: e.target.value })} onKeyDown={(e) => e.key === 'Enter' && apply()} />}
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

/** Pencarian mahasiswa (NIM/nama) untuk kasir & formulir petugas — data mahasiswa dari SIAKAD, tidak diduplikasi. */
export function StudentPicker({ value, onChange, label = 'Mahasiswa' }: { value: StudentRef | null; onChange: (student: StudentRef | null) => void; label?: string }) {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<StudentRef[]>([])
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (query.trim().length < 3) return setResults([])
    const handle = setTimeout(() => {
      keuAdmin.get<StudentRef[]>('students-search', { q: query.trim() }).then((rows) => {
        setResults(rows)
        setError(null)
      }).catch((err: NormalizedApiError) => setError(describeError(err)))
    }, 300)

    return () => clearTimeout(handle)
  }, [query])

  if (value) {
    return (
      <div className="flex items-end gap-2">
        <div className="flex-1 rounded-lg border border-border px-3 py-2 text-sm">
          <span className="text-xs text-ink-tertiary">{label}</span>
          <p className="font-medium text-ink-primary">{value.nim} — {value.name}</p>
          <p className="text-xs text-ink-secondary">{value.study_program ?? '—'} · Angkatan {value.admission_year ?? '—'} · {value.status_label ?? value.status}</p>
        </div>
        <Button type="button" variant="outline" onClick={() => onChange(null)}>Ganti</Button>
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-1">
      {/* Dipakai juga di dalam <form> (FormDialog prepend): Enter tidak boleh mengirim formulir. */}
      <Input label={label} placeholder="Ketik minimal 3 karakter NIM atau nama" value={query} onChange={(e) => setQuery(e.target.value)} onKeyDown={(e) => e.key === 'Enter' && e.preventDefault()} />
      {error && <p className="text-xs text-danger">{error}</p>}
      {results.length > 0 && (
        <ul className="max-h-60 overflow-y-auto rounded-lg border border-border">
          {results.map((s) => (
            <li key={s.id}>
              <button type="button" className="w-full px-3 py-2 text-left text-sm hover:bg-surface-hover" onClick={() => onChange(s)}>
                <span className="font-medium">{s.nim}</span> — {s.name} <span className="text-xs text-ink-tertiary">({s.study_program ?? '—'}, {s.status_label ?? s.status})</span>
              </button>
            </li>
          ))}
        </ul>
      )}
      {query.trim().length >= 3 && results.length === 0 && !error && <p className="text-xs text-ink-tertiary">Tidak ada mahasiswa yang cocok.</p>}
    </div>
  )
}

/** Baris angka ringkas (label kecil + nilai besar) untuk kartu ringkasan. */
export function Figure({ label, value, tone }: { label: string; value: ReactNode; tone?: 'danger' | 'success' | 'warning' }) {
  const color = tone === 'danger' ? 'text-danger' : tone === 'success' ? 'text-primary' : tone === 'warning' ? 'text-amber-600' : 'text-ink-primary'

  return (
    <div>
      <p className="text-xs text-ink-tertiary">{label}</p>
      <p className={`mt-0.5 text-lg font-semibold ${color}`}>{value}</p>
    </div>
  )
}
