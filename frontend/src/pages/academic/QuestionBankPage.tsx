import { useEffect, useState } from 'react'
import { useForm, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, Trash2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { courseService, questionBankService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Course, QuestionBankItem } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

/**
 * Bank soal (spec §7) — kumpulan soal pilihan ganda yang dapat dipakai ulang
 * lintas ujian, terlepas dari satu ujian tertentu. Soal di sini diterapkan
 * ke ujian tertentu lewat tombol "Terapkan dari Bank Soal" di halaman detail
 * ujian (ExamDetailPage) — lihat examQuestionService.applyBank.
 */
export function QuestionBankPage() {
  const canManage = usePermission(['question_bank.create', 'question_bank.update'])
  const canDelete = usePermission('question_bank.delete')

  const list = usePaginatedList<QuestionBankItem>({ fetcher: questionBankService.index })
  const [editTarget, setEditTarget] = useState<QuestionBankItem | 'new' | null>(null)

  const columns: DataTableColumn<QuestionBankItem>[] = [
    { header: 'Pertanyaan', cell: (row) => <span className="line-clamp-2">{row.question_text}</span> },
    { header: 'Mata Kuliah', cell: (row) => row.course_name ?? '-' },
    { header: 'Poin', cell: (row) => row.points },
    {
      header: 'Dipakai',
      cell: (row) => (
        <Badge variant={row.usage_count && row.usage_count > 0 ? 'info' : 'neutral'}>
          {row.usage_count ?? 0} ujian
        </Badge>
      ),
    },
    ...(canManage || canDelete
      ? [
          {
            header: 'Aksi',
            cell: (row: QuestionBankItem) => (
              <div className="flex gap-2">
                {canManage && (
                  <Button variant="ghost" size="sm" onClick={() => setEditTarget(row)}>
                    Ubah
                  </Button>
                )}
                {canDelete && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={async () => {
                      await questionBankService.remove(row.id)
                      list.refetch()
                    }}
                  >
                    <Trash2 className="size-4 text-danger" />
                  </Button>
                )}
              </div>
            ),
          } satisfies DataTableColumn<QuestionBankItem>,
        ]
      : []),
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Bank Soal"
        description="Kumpulan soal pilihan ganda yang dapat dipakai ulang di berbagai ujian."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Bank Soal' }]}
        actions={
          canManage && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setEditTarget('new')}>
              Tambah Soal
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Bank soal masih kosong.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="soal" />
      </Card>

      {editTarget && (
        <QuestionBankItemFormModal
          item={editTarget === 'new' ? null : editTarget}
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

const itemSchema = z.object({
  course_id: z.string(),
  question_text: z.string().min(1, 'Pertanyaan wajib diisi.'),
  points: z.number().min(0, 'Poin tidak boleh negatif.'),
  options: z
    .array(z.object({ option_text: z.string().min(1, 'Opsi wajib diisi.'), is_correct: z.boolean() }))
    .min(2, 'Minimal 2 opsi jawaban.')
    .refine((options) => options.filter((o) => o.is_correct).length === 1, {
      message: 'Setiap soal harus memiliki tepat satu jawaban benar.',
    }),
})

type ItemFormValues = z.infer<typeof itemSchema>

function QuestionBankItemFormModal({
  item,
  onClose,
  onSaved,
}: {
  item: QuestionBankItem | null
  onClose: () => void
  onSaved: () => void
}) {
  const [courses, setCourses] = useState<Course[] | null>(null)
  const [formError, setFormError] = useState<string | null>(null)

  useEffect(() => {
    courseService
      .index({ per_page: 200 })
      .then((result) => setCourses(result.data))
      .catch(() => setCourses([]))
  }, [])

  const {
    register,
    control,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<ItemFormValues>({
    resolver: zodResolver(itemSchema),
    defaultValues: {
      course_id: item?.course_id ?? '',
      question_text: item?.question_text ?? '',
      points: item ? Number(item.points) : 1,
      options: item?.options?.length
        ? item.options.map((o) => ({ option_text: o.option_text, is_correct: o.is_correct }))
        : [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
          ],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'options' })
  const options = watch('options')

  const onSubmit = async (values: ItemFormValues) => {
    setFormError(null)

    const payload = { ...values, course_id: values.course_id || null }

    try {
      if (item) {
        await questionBankService.update(item.id, payload)
      } else {
        await questionBankService.create(payload)
      }
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={item ? 'Ubah Soal Bank' : 'Tambah Soal ke Bank'} className="max-w-xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Select
          label="Mata Kuliah (opsional)"
          placeholder={courses === null ? 'Memuat...' : 'Tidak terkait mata kuliah tertentu'}
          disabled={courses === null}
          options={(courses ?? []).map((course) => ({ value: course.id, label: `${course.name} (${course.code})` }))}
          {...register('course_id')}
        />
        <Textarea
          label="Pertanyaan"
          rows={3}
          placeholder="Tulis pertanyaan soal di sini..."
          error={errors.question_text?.message}
          {...register('question_text')}
        />
        <Input
          type="number"
          step="0.01"
          min={0}
          label="Poin"
          error={errors.points?.message}
          {...register('points', { valueAsNumber: true })}
        />

        <div className="flex flex-col gap-2">
          <p className="text-sm font-medium text-ink-primary">Opsi Jawaban</p>
          {errors.options?.root?.message && <p className="text-xs text-danger">{errors.options.root.message}</p>}
          {errors.options?.message && <p className="text-xs text-danger">{errors.options.message}</p>}

          {fields.map((field, index) => (
            <div key={field.id} className="flex items-center gap-2">
              <input
                type="radio"
                name="correct-option"
                checked={options[index]?.is_correct ?? false}
                onChange={() => {
                  options.forEach((_, i) => setValue(`options.${i}.is_correct`, i === index))
                }}
                className="size-4 accent-[var(--color-primary)]"
              />
              <Input
                className="flex-1"
                placeholder={`Opsi ${index + 1}`}
                error={errors.options?.[index]?.option_text?.message}
                {...register(`options.${index}.option_text`)}
              />
              {fields.length > 2 && (
                <Button type="button" variant="ghost" size="sm" onClick={() => remove(index)}>
                  <Trash2 className="size-4 text-danger" />
                </Button>
              )}
            </div>
          ))}

          <Button
            type="button"
            variant="outline"
            size="sm"
            leftIcon={<Plus className="size-4" />}
            className="self-start"
            onClick={() => append({ option_text: '', is_correct: false })}
          >
            Tambah Opsi
          </Button>
        </div>

        <div className="mt-2 flex justify-end gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="submit" isLoading={isSubmitting}>
            Simpan
          </Button>
        </div>
      </form>
    </Modal>
  )
}
