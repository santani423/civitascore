import { useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { gradeService } from '@/services/academicService'
import type { Grade, LetterGrade } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

const LETTER_GRADE_VARIANT: Record<LetterGrade, BadgeVariant> = {
  A: 'success',
  AB: 'success',
  B: 'info',
  BC: 'info',
  C: 'warning',
  D: 'danger',
  E: 'danger',
}

export function GradesPage() {
  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['student_id', 'letter_grade']))

  const list = usePaginatedList<Grade>({ fetcher: gradeService.index, initialFilter })

  const columns: DataTableColumn<Grade>[] = [
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
          <p className="text-xs text-ink-tertiary">{row.course_code ?? '-'}</p>
        </div>
      ),
    },
    { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
    {
      header: 'Nilai Huruf',
      cell: (row) =>
        row.letter_grade ? (
          <Badge variant={LETTER_GRADE_VARIANT[row.letter_grade]}>{row.letter_grade}</Badge>
        ) : (
          <span className="text-ink-tertiary">Belum dinilai</span>
        ),
    },
    { header: 'Skor', cell: (row) => row.score ?? '-' },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Penilaian"
        description="Nilai mahasiswa per mata kuliah yang sudah diinput dosen."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Penilaian' }]}
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada nilai.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="nilai" />
      </Card>
    </div>
  )
}
