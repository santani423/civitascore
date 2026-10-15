import { useState } from 'react'
import type { SelectOption } from '@/components/ui/Select'

export interface TableFilter<T> {
  name: string
  label: string
  /** Ada → dropdown (kosong = Semua); tidak ada → kotak teks. */
  options?: SelectOption[]
  match: (row: T, value: string) => boolean
}

/** Cocok tanpa membedakan huruf besar/kecil terhadap salah satu teks. */
export const textMatch = (value: string, ...texts: Array<string | null | undefined>) => texts.some((t) => t?.toLowerCase().includes(value.trim().toLowerCase()))

/**
 * Filter sisi klien untuk tabel yang datanya sudah dimuat penuh (tanpa
 * paginasi server) — berlaku langsung tanpa tombol Terapkan. Tampilkan
 * barisnya dengan <TableFilters {...filterProps} />.
 */
export function useTableFilters<T>(rows: T[], filters: TableFilter<T>[]) {
  const [values, setValues] = useState<Record<string, string>>({})
  const active = filters.filter((f) => values[f.name]?.trim())
  const shown = active.length ? rows.filter((row) => active.every((f) => f.match(row, values[f.name]))) : rows

  return { shown, filterProps: { filters, values, onChange: setValues, shown: shown.length, total: rows.length } }
}
