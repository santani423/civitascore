import { useEffect, useState } from 'react'
import { keuAdmin } from '@/keu/api'
import type { Lookups, Option } from '@/keu/types'

let lookupsCache: Promise<Lookups> | null = null

/** Data rujukan form & filter KEU (GET /finance/lookups), di-cache per sesi halaman. */
export function useKeuLookups(refreshKey = 0): Lookups | null {
  const [data, setData] = useState<Lookups | null>(null)

  useEffect(() => {
    if (refreshKey > 0) lookupsCache = null
    lookupsCache ??= keuAdmin.get<Lookups>('lookups')
    lookupsCache.then(setData).catch(() => {
      lookupsCache = null
    })
  }, [refreshKey])

  return data
}

export function invalidateKeuLookups(): void {
  lookupsCache = null
}

export const opts = <T extends { id: string }>(rows: T[] | undefined, label: (row: T) => string): Option[] => (rows ?? []).map((row) => ({ value: row.id, label: label(row) }))
