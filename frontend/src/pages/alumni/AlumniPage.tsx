import { useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { alumniService } from '@/services/alumniService'
import type { Alumni, AlumniEmploymentStatus } from '@/types/alumni'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const EMPLOYMENT_STATUS_LABEL: Record<AlumniEmploymentStatus, string> = {
  bekerja: 'Bekerja',
  wirausaha: 'Wirausaha',
  melanjutkan_studi: 'Melanjutkan Studi',
  mencari_kerja: 'Mencari Kerja',
  belum_bekerja: 'Belum Bekerja',
}

const EMPLOYMENT_STATUS_VARIANT: Record<AlumniEmploymentStatus, BadgeVariant> = {
  bekerja: 'success',
  wirausaha: 'success',
  melanjutkan_studi: 'info',
  mencari_kerja: 'warning',
  belum_bekerja: 'neutral',
}

export function AlumniPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['employment_status', 'graduation_year']))

  const list = usePaginatedList<Alumni>({ fetcher: alumniService.index, initialFilter })

  const columns: DataTableColumn<Alumni>[] = [
    {
      header: 'Alumni',
      cell: (row) => (
        <Link to={`${ROUTES.alumni}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.student_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.student_nim ?? '-'}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Tahun Lulus', cell: (row) => row.graduation_year },
    {
      header: 'Status Pekerjaan',
      cell: (row) => (
        <Badge variant={EMPLOYMENT_STATUS_VARIANT[row.employment_status]}>{EMPLOYMENT_STATUS_LABEL[row.employment_status]}</Badge>
      ),
    },
    {
      header: 'Terverifikasi',
      cell: (row) => <Badge variant={row.is_verified ? 'success' : 'neutral'}>{row.is_verified ? 'Ya' : 'Belum'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Alumni"
        description="Profil alumni yang sudah lulus dari universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Alumni' }]}
      />

      <Card noPadding>
        {list.error ? (
          <Alert variant="danger" className="m-4">
            {list.error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={list.data}
            rowKey={(row) => row.id}
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada data alumni.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="alumni" />
      </Card>
    </div>
  )
}
