import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import type { TableFilter } from '@/pmb/components/useTableFilters'

/** Baris filter tabel sisi klien; pasangan dari useTableFilters. */
export function TableFilters<T>({ filters, values, onChange, shown, total }: { filters: TableFilter<T>[]; values: Record<string, string>; onChange: (values: Record<string, string>) => void; shown: number; total: number }) {
  const filtered = Object.values(values).some((v) => v.trim())

  return (
    <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
      {filters.map((f) => f.options ? (
        <Select key={f.name} label={f.label} options={f.options} placeholder="Semua" value={values[f.name] ?? ''} onChange={(e) => onChange({ ...values, [f.name]: e.target.value })} />
      ) : (
        <Input key={f.name} label={f.label} value={values[f.name] ?? ''} onChange={(e) => onChange({ ...values, [f.name]: e.target.value })} />
      ))}
      {filtered && <Button variant="ghost" onClick={() => onChange({})}>Reset</Button>}
      <span className="ml-auto pb-2 text-xs text-ink-tertiary">{filtered ? `${shown} dari ${total} baris` : `${total} baris`}</span>
    </div>
  )
}
