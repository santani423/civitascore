import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { bookService } from '@/services/libraryService'
import type { Book } from '@/types/library'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatNumber } from '@/utils/formatters'

export function BooksPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['category', 'is_active']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Book>({ fetcher: bookService.index, initialFilter })

  const columns: DataTableColumn<Book>[] = [
    {
      header: 'Buku',
      cell: (row) => (
        <Link to={`${ROUTES.perpustakaan}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.title}</p>
          <p className="text-xs text-ink-tertiary">{row.author}</p>
        </Link>
      ),
    },
    { header: 'Kategori', cell: (row) => row.category ?? '-' },
    { header: 'Stok', cell: (row) => formatNumber(row.stock) },
    { header: 'Dipinjam', cell: (row) => formatNumber(row.loans_count ?? 0) },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Perpustakaan"
        description="Katalog buku pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perpustakaan' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Judul, penulis, atau ISBN..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Button variant="outline" onClick={() => list.setSearch(searchInput)}>
            Cari
          </Button>
        </div>

        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada buku.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="buku" />
      </Card>
    </div>
  )
}
