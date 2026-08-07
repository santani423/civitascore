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
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { gradeService, krsItemService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Grade, KrsItem, LetterGrade } from '@/types/academic'
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

const LETTER_GRADES: LetterGrade[] = ['A', 'AB', 'B', 'BC', 'C', 'D', 'E']

interface GradeTarget {
  krsItemId: string
  studentLabel: string
  courseLabel: string
  score: string | null
  letterGrade: LetterGrade | null
}

function gradeTargetFromRow(row: Grade): GradeTarget {
  return {
    krsItemId: row.krs_item_id,
    studentLabel: `${row.student_name ?? '-'} — ${row.student_nim ?? '-'}`,
    courseLabel: row.course_name ?? '-',
    score: row.score,
    letterGrade: row.letter_grade,
  }
}

export function GradesPage() {
  const canManage = usePermission(['grades.create', 'grades.update'])

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['student_id', 'letter_grade']))

  const list = usePaginatedList<Grade>({ fetcher: gradeService.index, initialFilter })

  const [showPicker, setShowPicker] = useState(false)
  const [editTarget, setEditTarget] = useState<GradeTarget | null>(null)

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
    ...(canManage
      ? [
          {
            header: 'Aksi',
            cell: (row: Grade) => (
              <Button variant="ghost" size="sm" onClick={() => setEditTarget(gradeTargetFromRow(row))}>
                Ubah Nilai
              </Button>
            ),
          } satisfies DataTableColumn<Grade>,
        ]
      : []),
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Penilaian"
        description="Nilai mahasiswa per mata kuliah yang sudah diinput dosen."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Penilaian' }]}
        actions={
          canManage && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowPicker(true)}>
              Input Nilai
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada nilai.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="nilai" />
      </Card>

      {showPicker && (
        <PickKrsItemModal
          onClose={() => setShowPicker(false)}
          onPicked={(krsItem) => {
            setShowPicker(false)
            setEditTarget({
              krsItemId: krsItem.id,
              studentLabel: `${krsItem.student_name ?? '-'} — ${krsItem.student_nim ?? '-'}`,
              courseLabel: krsItem.course_name ?? '-',
              score: null,
              letterGrade: null,
            })
          }}
        />
      )}

      {editTarget && (
        <GradeFormModal
          target={editTarget}
          onClose={() => setEditTarget(null)}
          onSaved={() => {
            setEditTarget(null)
            list.refetch()
          }}
        />
      )}
    </div>
  )
}

function PickKrsItemModal({ onClose, onPicked }: { onClose: () => void; onPicked: (krsItem: KrsItem) => void }) {
  const [options, setOptions] = useState<KrsItem[] | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [selectedId, setSelectedId] = useState('')

  useEffect(() => {
    krsItemService
      .index({ per_page: 100, filter: { status: 'enrolled' } })
      .then((result) => setOptions(result.data))
      .catch((error: NormalizedApiError) => setLoadError(error.message))
  }, [])

  return (
    <Modal open onClose={onClose} title="Pilih Mahasiswa">
      {loadError && <Alert variant="danger">{loadError}</Alert>}

      {options === null ? (
        <p className="text-sm text-ink-secondary">Memuat daftar KRS aktif...</p>
      ) : (
        <div className="flex flex-col gap-4">
          <Select
            label="Mahasiswa · Mata Kuliah"
            placeholder="Pilih peserta kelas"
            value={selectedId}
            onChange={(event) => setSelectedId(event.target.value)}
            options={options.map((krsItem) => ({
              value: krsItem.id,
              label: `${krsItem.student_name ?? '-'} — ${krsItem.course_name ?? '-'} (${krsItem.class_code ?? '-'})`,
            }))}
          />

          <div className="mt-2 flex justify-end gap-2">
            <Button type="button" variant="outline" onClick={onClose}>
              Batal
            </Button>
            <Button
              type="button"
              disabled={!selectedId}
              onClick={() => {
                const krsItem = options.find((item) => item.id === selectedId)
                if (krsItem) onPicked(krsItem)
              }}
            >
              Lanjut
            </Button>
          </div>
        </div>
      )}
    </Modal>
  )
}

const gradeSchema = z.object({
  score: z.number({ message: 'Skor wajib diisi.' }).min(0, 'Skor minimal 0.').max(100, 'Skor maksimal 100.'),
  letter_grade: z.union([z.enum(['A', 'AB', 'B', 'BC', 'C', 'D', 'E']), z.literal('')]).optional(),
})

type GradeFormValues = z.infer<typeof gradeSchema>

function GradeFormModal({ target, onClose, onSaved }: { target: GradeTarget; onClose: () => void; onSaved: () => void }) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<GradeFormValues>({
    resolver: zodResolver(gradeSchema),
    defaultValues: { score: Number(target.score ?? 0), letter_grade: target.letterGrade ?? '' },
  })

  const onSubmit = async (values: GradeFormValues) => {
    setFormError(null)

    try {
      await gradeService.upsert(target.krsItemId, {
        score: values.score,
        letter_grade: values.letter_grade || null,
      })
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Input Nilai">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <div className="mb-4 rounded-lg border border-border bg-surface-subtle p-3 text-sm">
        <p className="font-medium text-ink-primary">{target.studentLabel}</p>
        <p className="text-ink-tertiary">{target.courseLabel}</p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input
          type="number"
          step="0.01"
          min={0}
          max={100}
          label="Skor (0–100)"
          error={errors.score?.message}
          {...register('score', { valueAsNumber: true })}
        />
        <Select
          label="Nilai Huruf (opsional — otomatis dari skor kalau dikosongkan)"
          placeholder="Otomatis dari skor"
          error={errors.letter_grade?.message}
          options={LETTER_GRADES.map((grade) => ({ value: grade, label: grade }))}
          {...register('letter_grade')}
        />

        <div className="mt-2 flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" isLoading={isSubmitting}>
            Simpan Nilai
          </Button>
        </div>
      </form>
    </Modal>
  )
}
