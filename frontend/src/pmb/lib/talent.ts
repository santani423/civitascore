import { useEffect, useState } from 'react'
import { pmbAdmin, type Params } from '@/pmb/api/admin'
import { talentApi, type AssessmentTypeRow } from '@/pmb/api/talent'

/** Opsi periode PMB untuk filter (daftar admin berpaginasi, cukup untuk daftar periode kerja). */
export function usePeriodOptions(): Array<{ value: string; label: string }> {
  const [options, setOptions] = useState<Array<{ value: string; label: string }>>([])

  useEffect(() => {
    pmbAdmin.list<{ id: string; code: string; name: string }>('periods', { per_page: 100 })
      .then((page) => setOptions(page.data.map((p) => ({ value: p.id, label: `${p.code} · ${p.name}` }))))
      .catch(() => setOptions([]))
  }, [])

  return options
}

/** Jenis asesmen untuk filter dan daftar pilihan. */
export function useTypeOptions(): { types: AssessmentTypeRow[]; options: Array<{ value: string; label: string }> } {
  const [types, setTypes] = useState<AssessmentTypeRow[]>([])

  useEffect(() => {
    talentApi.types().then(setTypes).catch(() => setTypes([]))
  }, [])

  return { types, options: types.map((t) => ({ value: t.id, label: t.name })) }
}

/** Menghapus filter kosong agar tidak ikut ke query string. */
export function cleanParams(values: Record<string, string | undefined>): Params {
  return Object.fromEntries(Object.entries(values).filter(([, v]) => v !== undefined && v !== '')) as Params
}

/** Pesan galat yang sudah dinormalisasi klien API (422 dan 409 membawa kode di `errors`). */
export function errorText(err: unknown): string {
  return (err as { message?: string })?.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
}

export const LIKERT_LABEL: Record<number, string> = { 1: 'Sangat tidak sesuai', 2: 'Tidak sesuai', 3: 'Netral', 4: 'Sesuai', 5: 'Sangat sesuai' }
