import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { Pin } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { announcementService } from '@/services/announcementService'
import type { Announcement, AnnouncementTargetScope } from '@/types/announcement'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const TARGET_SCOPE_LABEL: Record<AnnouncementTargetScope, string> = {
  universitas: 'Universitas',
  fakultas: 'Fakultas',
  program_studi: 'Program Studi',
}

export function AnnouncementsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['target_scope', 'is_pinned']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Announcement>({ fetcher: announcementService.index, initialFilter })

  const columns: DataTableColumn<Announcement>[] = [
    {
      header: 'Judul',
      cell: (row) => (
        <Link to={`${ROUTES.pengumuman}/${row.id}`} className="font-medium text-primary hover:underline">
          {row.is_pinned && <Pin className="mr-1 inline size-3.5 text-primary" />}
          {row.title}
        </Link>
      ),
    },
    {
      header: 'Cakupan',
      cell: (row) => <Badge variant="neutral">{TARGET_SCOPE_LABEL[row.target_scope]}</Badge>,
    },
    { header: 'Dibuat Oleh', cell: (row) => row.creator_name ?? '-' },
    { header: 'Tanggal Terbit', cell: (row) => row.published_at.slice(0, 10) },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengumuman"
        description="Daftar pengumuman untuk universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengumuman' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Judul..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada pengumuman.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="pengumuman" />
      </Card>
    </div>
  )
}
