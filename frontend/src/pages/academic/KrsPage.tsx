import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { krsItemService } from '@/services/academicService'
import type { KrsItem, KrsItemStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const STATUS_LABEL: Record<KrsItemStatus, string> = {
  enrolled: 'Terdaftar',
  dropped: 'Dibatalkan',
}

const STATUS_VARIANT: Record<KrsItemStatus, BadgeVariant> = {
  enrolled: 'success',
  dropped: 'neutral',
}

export function KrsPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['student_id', 'class_section_id', 'academic_term_id', 'status']),
  )

  const list = usePaginatedList<KrsItem>({ fetcher: krsItemService.index, initialFilter })

  const columns: DataTableColumn<KrsItem>[] = [
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.student_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.student_nim ?? '-'}</p>
        </div>
      ),
    },
    {
      header: 'Mata Kuliah',
      cell: (row) => (
        <div>
          <p className="text-ink-primary">{row.course_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">
            {row.course_code ?? '-'} · {row.class_code ?? '-'}
          </p>
        </div>
      ),
    },
    { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
    { header: 'Nilai', cell: (row) => row.letter_grade ?? <span className="text-ink-tertiary">Belum dinilai</span> },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="KRS"
        description="Kartu Rencana Studi — daftar mata kuliah yang diambil mahasiswa per periode."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'KRS' }]}
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada data KRS.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="KRS" />
      </Card>
    </div>
  )
}
