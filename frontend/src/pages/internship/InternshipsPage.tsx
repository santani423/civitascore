import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { internshipService } from '@/services/internshipService'
import type { Internship, InternshipProgramType, InternshipStatus } from '@/types/internship'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const PROGRAM_TYPE_LABEL: Record<InternshipProgramType, string> = {
  magang: 'Magang',
  kkn: 'KKN',
  mbkm: 'MBKM',
}

const STATUS_LABEL: Record<InternshipStatus, string> = {
  terdaftar: 'Terdaftar',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}

const STATUS_VARIANT: Record<InternshipStatus, BadgeVariant> = {
  terdaftar: 'neutral',
  berlangsung: 'info',
  selesai: 'success',
  dibatalkan: 'danger',
}

export function InternshipsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['status', 'program_type']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Internship>({ fetcher: internshipService.index, initialFilter })

  const columns: DataTableColumn<Internship>[] = [
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <Link to={`${ROUTES.magangMbkm}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.student_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.student_nim ?? '-'}</p>
        </Link>
      ),
    },
    {
      header: 'Instansi',
      cell: (row) => (
        <>
          <p>{row.institution_name}</p>
          {row.position && <p className="text-xs text-ink-tertiary">{row.position}</p>}
        </>
      ),
    },
    {
      header: 'Jenis Program',
      cell: (row) => <Badge variant="info">{PROGRAM_TYPE_LABEL[row.program_type]}</Badge>,
    },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Magang dan MBKM"
        description="Daftar magang, KKN, dan program MBKM mahasiswa pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Magang dan MBKM' }]}
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama instansi..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada data magang atau MBKM.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="data magang" />
      </Card>
    </div>
  )
}
