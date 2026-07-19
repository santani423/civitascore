import { Button } from '@/components/ui/Button'
import type { ApiPaginationMeta } from '@/types/api'

export interface ListPaginationProps {
  meta: ApiPaginationMeta | null
  page: number
  onPageChange: (page: number) => void
  /** Label satuan data untuk teks ringkasan, mis. "mahasiswa" -> "(120 mahasiswa)". */
  itemLabel: string
}

/** Footer pagination standar untuk semua List page — render null kalau cuma satu halaman. */
export function ListPagination({ meta, page, onPageChange, itemLabel }: ListPaginationProps) {
  if (!meta || meta.last_page <= 1) return null

  return (
    <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
      <span>
        Halaman {meta.current_page} dari {meta.last_page} ({meta.total} {itemLabel})
      </span>
      <div className="flex gap-2">
        <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => onPageChange(page - 1)}>
          Sebelumnya
        </Button>
        <Button variant="outline" size="sm" disabled={page >= meta.last_page} onClick={() => onPageChange(page + 1)}>
          Berikutnya
        </Button>
      </div>
    </div>
  )
}
