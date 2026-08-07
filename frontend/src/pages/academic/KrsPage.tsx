import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { useSearchParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { classSectionService, krsItemService, studentService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { ClassSection, KrsItem, KrsItemStatus, Student } from '@/types/academic'
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
  const canCreate = usePermission('krs.create')
  const canDrop = usePermission('krs.update')

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['student_id', 'class_section_id', 'academic_term_id', 'status']),
  )

  const list = usePaginatedList<KrsItem>({ fetcher: krsItemService.index, initialFilter })

  const [showEnrollModal, setShowEnrollModal] = useState(false)
  const [dropTarget, setDropTarget] = useState<KrsItem | null>(null)
  const [dropError, setDropError] = useState<string | null>(null)
  const [isDropping, setIsDropping] = useState(false)

  const handleDrop = async () => {
    if (!dropTarget) return
    setDropError(null)
    setIsDropping(true)

    try {
      await krsItemService.drop(dropTarget.id)
      setDropTarget(null)
      list.refetch()
    } catch (error) {
      setDropError((error as NormalizedApiError).message ?? 'Gagal membatalkan KRS.')
    } finally {
      setIsDropping(false)
    }
  }

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
    ...(canDrop
      ? [
          {
            header: 'Aksi',
            cell: (row: KrsItem) =>
              row.status === 'enrolled' ? (
                <Button variant="ghost" size="sm" className="text-danger" onClick={() => setDropTarget(row)}>
                  Batalkan
                </Button>
              ) : null,
          } satisfies DataTableColumn<KrsItem>,
        ]
      : []),
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="KRS"
        description="Kartu Rencana Studi — daftar mata kuliah yang diambil mahasiswa per periode."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'KRS' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowEnrollModal(true)}>
              Tambah KRS
            </Button>
          )
        }
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

      {showEnrollModal && (
        <EnrollKrsModal
          onClose={() => setShowEnrollModal(false)}
          onSaved={() => {
            setShowEnrollModal(false)
            list.refetch()
          }}
        />
      )}

      <Modal open={dropTarget !== null} onClose={() => setDropTarget(null)} title="Batalkan KRS">
        {dropError && (
          <Alert variant="danger" className="mb-4" onDismiss={() => setDropError(null)}>
            {dropError}
          </Alert>
        )}
        <p className="text-sm text-ink-secondary">
          Batalkan pendaftaran <span className="font-medium text-ink-primary">{dropTarget?.student_name}</span> pada{' '}
          <span className="font-medium text-ink-primary">{dropTarget?.course_name}</span>?
        </p>
        <div className="mt-5 flex justify-end gap-2">
          <Button variant="outline" onClick={() => setDropTarget(null)}>
            Batal
          </Button>
          <Button variant="danger" isLoading={isDropping} onClick={handleDrop}>
            Batalkan KRS
          </Button>
        </div>
      </Modal>
    </div>
  )
}

const enrollSchema = z.object({
  student_id: z.string().min(1, 'Pilih mahasiswa.'),
  class_section_id: z.string().min(1, 'Pilih kelas.'),
})

type EnrollFormValues = z.infer<typeof enrollSchema>

function EnrollKrsModal({ onClose, onSaved }: { onClose: () => void; onSaved: () => void }) {
  const [students, setStudents] = useState<Student[] | null>(null)
  const [classSections, setClassSections] = useState<ClassSection[] | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [formError, setFormError] = useState<string | null>(null)

  useEffect(() => {
    Promise.all([studentService.index({ per_page: 100 }), classSectionService.index({ per_page: 100 })])
      .then(([studentResult, classSectionResult]) => {
        setStudents(studentResult.data)
        setClassSections(classSectionResult.data)
      })
      .catch((error: NormalizedApiError) => setLoadError(error.message))
  }, [])

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<EnrollFormValues>({ resolver: zodResolver(enrollSchema), defaultValues: { student_id: '', class_section_id: '' } })

  const onSubmit = async (values: EnrollFormValues) => {
    setFormError(null)

    try {
      await krsItemService.enroll(values)
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  const isLoading = students === null || classSections === null

  return (
    <Modal open onClose={onClose} title="Tambah KRS">
      {loadError && <Alert variant="danger">{loadError}</Alert>}
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      {isLoading ? (
        <p className="text-sm text-ink-secondary">Memuat data mahasiswa dan kelas...</p>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
          <Select
            label="Mahasiswa"
            placeholder="Pilih mahasiswa"
            error={errors.student_id?.message}
            options={students.map((student) => ({ value: student.id, label: `${student.name} — ${student.nim}` }))}
            {...register('student_id')}
          />
          <Select
            label="Kelas"
            placeholder="Pilih kelas"
            error={errors.class_section_id?.message}
            options={classSections.map((classSection) => ({
              value: classSection.id,
              label: `${classSection.course_name ?? '-'} (${classSection.class_code})`,
            }))}
            {...register('class_section_id')}
          />

          <div className="mt-2 flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>
              Batal
            </Button>
            <Button type="submit" isLoading={isSubmitting}>
              Daftarkan
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
