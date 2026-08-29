import { useCallback, useEffect, useState } from 'react'
import { useForm, useFieldArray } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Library, Plus, Trash2 } from 'lucide-react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Textarea } from '@/components/ui/Textarea'
import { Select } from '@/components/ui/Select'
import { Checkbox } from '@/components/ui/Checkbox'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { examQuestionService, examService, questionBankService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import { examDistributionSummary, examValidationErrors, QUESTION_SELECTION_MODE_LABEL } from '@/utils/examValidation'
import { fromDatetimeLocalValue, toDatetimeLocalValue } from '@/utils/formatters'
import type { Exam, ExamQuestion, QuestionBankItem } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

export function ExamDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canManage = usePermission(['exams.create', 'exams.update'])
  const canPublish = usePermission('exams.publish')

  const getExam = useCallback(() => examService.show(id ?? ''), [id])
  const { data: exam, isLoading: examLoading, error: examError, refetch: refetchExam, setData: setExam } = useFetch(getExam)

  const getQuestions = useCallback(() => examQuestionService.index(id ?? ''), [id])
  const {
    data: questions,
    isLoading: questionsLoading,
    error: questionsError,
    refetch: refetchQuestions,
  } = useFetch(getQuestions)

  const [showExamForm, setShowExamForm] = useState(false)
  const [questionModalTarget, setQuestionModalTarget] = useState<ExamQuestion | 'new' | null>(null)
  const [showBankPicker, setShowBankPicker] = useState(false)
  const [publishError, setPublishError] = useState<string | null>(null)
  const [isPublishing, setIsPublishing] = useState(false)

  const manuallySelectedCount = (questions ?? []).filter((q) => q.is_selected).length
  const validationErrors = exam
    ? examValidationErrors({
        questionPoolSize: exam.question_pool_size,
        questionsPerParticipant: exam.questions_per_participant,
        selectionMode: exam.question_selection_mode,
        manuallySelectedCount,
      })
    : []

  const handlePublish = async () => {
    if (!exam) return
    setPublishError(null)
    setIsPublishing(true)

    try {
      const published = await examService.publish(exam.id)
      setExam(published)
    } catch (error) {
      setPublishError((error as NormalizedApiError).message)
    } finally {
      setIsPublishing(false)
    }
  }

  const questionColumns: DataTableColumn<ExamQuestion>[] = [
    {
      header: 'Pertanyaan',
      cell: (row) => (
        <div>
          <span className="line-clamp-2">{row.question_text}</span>
          {row.question_bank_item_id && (
            <span className="mt-1 inline-flex items-center gap-1 text-xs text-ink-tertiary">
              <Library className="size-3" />
              Dari Bank Soal
            </span>
          )}
        </div>
      ),
    },
    { header: 'Poin', cell: (row) => row.points },
    { header: 'Opsi', cell: (row) => row.options.length },
    {
      header: 'Jawaban Benar',
      cell: (row) => row.options.find((o) => o.is_correct)?.option_text ?? <span className="text-danger">-</span>,
    },
    ...(exam?.question_selection_mode === 'manual'
      ? [
          {
            header: 'Dipilih',
            cell: (row: ExamQuestion) => (
              <Checkbox
                checked={row.is_selected}
                disabled={exam?.is_published}
                onChange={async (event) => {
                  await examQuestionService.update(row.id, { is_selected: event.target.checked })
                  refetchQuestions()
                }}
              />
            ),
          } satisfies DataTableColumn<ExamQuestion>,
        ]
      : []),
    ...(canManage && !exam?.is_published
      ? [
          {
            header: 'Aksi',
            cell: (row: ExamQuestion) => (
              <div className="flex gap-2">
                <Button variant="ghost" size="sm" onClick={() => setQuestionModalTarget(row)}>
                  Ubah
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={async () => {
                    await examQuestionService.remove(row.id)
                    refetchQuestions()
                  }}
                >
                  <Trash2 className="size-4 text-danger" />
                </Button>
              </div>
            ),
          } satisfies DataTableColumn<ExamQuestion>,
        ]
      : []),
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title={exam?.title ?? 'Detail Ujian'}
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Ujian', path: ROUTES.akademik.ujian },
          { label: 'Detail' },
        ]}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => navigate(ROUTES.akademik.ujian)}>
              Kembali
            </Button>
            {canManage && exam && !exam.is_published && <Button onClick={() => setShowExamForm(true)}>Ubah Ujian</Button>}
          </div>
        }
      />

      {examLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {examError && <Alert variant="danger">{examError}</Alert>}

      {exam && (
        <>
          <Card title="Distribusi Soal" description="Ringkasan konfigurasi dan validasi sebelum publikasi.">
            <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
              <div>
                <p className="text-ink-tertiary">Question Pool</p>
                <p className="font-medium text-ink-primary">{exam.question_pool_size}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Soal per Peserta</p>
                <p className="font-medium text-ink-primary">{exam.questions_per_participant}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Pemilihan</p>
                <p className="font-medium text-ink-primary">{QUESTION_SELECTION_MODE_LABEL[exam.question_selection_mode]}</p>
              </div>
              <div>
                <p className="text-ink-tertiary">Status</p>
                <Badge variant={exam.is_published ? 'success' : 'neutral'}>
                  {exam.is_published ? 'Dipublikasikan' : 'Draft'}
                </Badge>
              </div>
              <div>
                <p className="text-ink-tertiary">Waktu Mulai</p>
                <p className="font-medium text-ink-primary">
                  {exam.starts_at ? new Date(exam.starts_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Tidak dijadwalkan'}
                </p>
              </div>
              <div>
                <p className="text-ink-tertiary">Waktu Selesai</p>
                <p className="font-medium text-ink-primary">
                  {exam.ends_at ? new Date(exam.ends_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Tidak dijadwalkan'}
                </p>
              </div>
            </div>

            <Alert variant="info" className="mt-4">
              {examDistributionSummary({
                questionPoolSize: exam.question_pool_size,
                questionsPerParticipant: exam.questions_per_participant,
                selectionMode: exam.question_selection_mode,
                randomizeQuestions: exam.randomize_questions,
                randomizeOptions: exam.randomize_options,
              })}
            </Alert>

            {validationErrors.map((message) => (
              <Alert key={message} variant="danger" className="mt-2">
                {message}
              </Alert>
            ))}
            {publishError && (
              <Alert variant="danger" className="mt-2" onDismiss={() => setPublishError(null)}>
                {publishError}
              </Alert>
            )}

            {canPublish && !exam.is_published && (
              <div className="mt-4 flex justify-end">
                <Button onClick={handlePublish} isLoading={isPublishing} disabled={validationErrors.length > 0}>
                  Publish Ujian
                </Button>
              </div>
            )}
          </Card>

          <Card
            title="Soal Ujian"
            description="Soal dan opsi jawaban ujian ini."
            actions={
              canManage &&
              !exam.is_published && (
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    leftIcon={<Library className="size-4" />}
                    onClick={() => setShowBankPicker(true)}
                  >
                    Terapkan dari Bank Soal
                  </Button>
                  <Button size="sm" leftIcon={<Plus className="size-4" />} onClick={() => setQuestionModalTarget('new')}>
                    Tambah Soal
                  </Button>
                </div>
              )
            }
            noPadding
          >
            {questionsError ? (
              <Alert variant="danger" className="m-4">
                {questionsError}
              </Alert>
            ) : (
              <DataTable
                columns={questionColumns}
                data={questions ?? []}
                rowKey={(row) => row.id}
                emptyMessage={questionsLoading ? 'Memuat...' : 'Belum ada soal.'}
              />
            )}
          </Card>
        </>
      )}

      {showExamForm && exam && (
        <ExamEditModal
          exam={exam}
          onClose={() => setShowExamForm(false)}
          onSaved={(updated) => {
            setShowExamForm(false)
            setExam(updated)
          }}
        />
      )}

      {questionModalTarget && exam && (
        <QuestionFormModal
          examId={exam.id}
          question={questionModalTarget === 'new' ? null : questionModalTarget}
          onClose={() => setQuestionModalTarget(null)}
          onSaved={() => {
            setQuestionModalTarget(null)
            refetchQuestions()
            refetchExam()
          }}
        />
      )}

      {showBankPicker && exam && (
        <QuestionBankPickerModal
          examId={exam.id}
          onClose={() => setShowBankPicker(false)}
          onApplied={() => {
            setShowBankPicker(false)
            refetchQuestions()
            refetchExam()
          }}
        />
      )}
    </div>
  )
}

const examEditSchema = z
  .object({
    title: z.string().min(1, 'Judul wajib diisi.'),
    duration_minutes: z.number({ message: 'Durasi wajib diisi.' }).min(1, 'Durasi minimal 1 menit.'),
    starts_at: z.string().optional(),
    ends_at: z.string().optional(),
    questions_per_participant: z
      .number({ message: 'Jumlah soal wajib diisi.' })
      .min(1, 'Jumlah soal harus lebih dari 0.'),
    question_selection_mode: z.enum(['all', 'random', 'manual']),
    max_attempts: z.number().min(1, 'Batas percobaan minimal 1.'),
    randomize_questions: z.boolean(),
    randomize_options: z.boolean(),
  })
  .refine((data) => !data.starts_at || !data.ends_at || new Date(data.ends_at) > new Date(data.starts_at), {
    message: 'Waktu selesai harus setelah waktu mulai.',
    path: ['ends_at'],
  })

type ExamEditValues = z.infer<typeof examEditSchema>

function ExamEditModal({ exam, onClose, onSaved }: { exam: Exam; onClose: () => void; onSaved: (exam: Exam) => void }) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<ExamEditValues>({
    resolver: zodResolver(examEditSchema),
    defaultValues: {
      title: exam.title,
      duration_minutes: exam.duration_minutes,
      starts_at: toDatetimeLocalValue(exam.starts_at),
      ends_at: toDatetimeLocalValue(exam.ends_at),
      questions_per_participant: exam.questions_per_participant,
      question_selection_mode: exam.question_selection_mode,
      max_attempts: exam.max_attempts,
      randomize_questions: exam.randomize_questions,
      randomize_options: exam.randomize_options,
    },
  })

  const onSubmit = async (values: ExamEditValues) => {
    setFormError(null)

    try {
      const updated = await examService.update(exam.id, {
        ...values,
        starts_at: fromDatetimeLocalValue(values.starts_at),
        ends_at: fromDatetimeLocalValue(values.ends_at),
      })
      onSaved(updated)
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Ubah Ujian" className="max-w-xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input label="Judul Ujian" error={errors.title?.message} {...register('title')} />
        <div className="grid grid-cols-2 gap-4">
          <Input
            type="number"
            min={1}
            label="Durasi (menit)"
            error={errors.duration_minutes?.message}
            {...register('duration_minutes', { valueAsNumber: true })}
          />
          <Input
            type="number"
            min={1}
            label="Batas Percobaan"
            error={errors.max_attempts?.message}
            {...register('max_attempts', { valueAsNumber: true })}
          />
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <Input
            type="datetime-local"
            label="Waktu Mulai"
            hint="Kosongkan jika tidak ingin membatasi jadwal mulai."
            error={errors.starts_at?.message}
            {...register('starts_at')}
          />
          <Input
            type="datetime-local"
            label="Waktu Selesai"
            hint="Kosongkan jika tidak ingin membatasi jadwal berakhir."
            error={errors.ends_at?.message}
            {...register('ends_at')}
          />
        </div>
        <Input
          type="number"
          min={1}
          label="Jumlah Soal per Peserta"
          error={errors.questions_per_participant?.message}
          {...register('questions_per_participant', { valueAsNumber: true })}
        />
        <Select
          label="Pemilihan Soal"
          options={[
            { value: 'all', label: 'Semua Soal' },
            { value: 'random', label: 'Acak dari Question Pool' },
            { value: 'manual', label: 'Pilih Soal Secara Manual' },
          ]}
          {...register('question_selection_mode')}
        />
        <div className="flex flex-col gap-2">
          <Checkbox label="Acak urutan soal" {...register('randomize_questions')} />
          <Checkbox label="Acak pilihan jawaban" {...register('randomize_options')} />
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

const questionSchema = z.object({
  question_text: z.string().min(1, 'Pertanyaan wajib diisi.'),
  points: z.number().min(0, 'Poin tidak boleh negatif.'),
  options: z
    .array(z.object({ option_text: z.string().min(1, 'Opsi wajib diisi.'), is_correct: z.boolean() }))
    .min(2, 'Minimal 2 opsi jawaban.')
    .refine((options) => options.filter((o) => o.is_correct).length === 1, {
      message: 'Setiap soal harus memiliki tepat satu jawaban benar.',
    }),
})

type QuestionFormValues = z.infer<typeof questionSchema>

function QuestionFormModal({
  examId,
  question,
  onClose,
  onSaved,
}: {
  examId: string
  question: ExamQuestion | null
  onClose: () => void
  onSaved: () => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    control,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<QuestionFormValues>({
    resolver: zodResolver(questionSchema),
    defaultValues: {
      question_text: question?.question_text ?? '',
      points: question ? Number(question.points) : 1,
      options: question?.options?.length
        ? question.options.map((o) => ({ option_text: o.option_text, is_correct: o.is_correct }))
        : [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
          ],
    },
  })

  const { fields, append, remove } = useFieldArray({ control, name: 'options' })
  const options = watch('options')

  const onSubmit = async (values: QuestionFormValues) => {
    setFormError(null)

    try {
      if (question) {
        await examQuestionService.update(question.id, values)
      } else {
        await examQuestionService.create(examId, values)
      }
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={question ? 'Ubah Soal' : 'Tambah Soal'} className="max-w-xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
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

/** Memilih soal dari bank soal (spec §7) untuk disalin ke pool ujian ini. */
function QuestionBankPickerModal({
  examId,
  onClose,
  onApplied,
}: {
  examId: string
  onClose: () => void
  onApplied: () => void
}) {
  const [items, setItems] = useState<QuestionBankItem[] | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [search, setSearch] = useState('')
  const [selectedIds, setSelectedIds] = useState<string[]>([])
  const [applyError, setApplyError] = useState<string | null>(null)
  const [isApplying, setIsApplying] = useState(false)

  useEffect(() => {
    const timeout = setTimeout(() => {
      questionBankService
        .index({ per_page: 100, search: search || undefined })
        .then((result) => setItems(result.data))
        .catch((error: NormalizedApiError) => setLoadError(error.message))
    }, 250)

    return () => clearTimeout(timeout)
  }, [search])

  const toggle = (id: string) => {
    setSelectedIds((current) => (current.includes(id) ? current.filter((i) => i !== id) : [...current, id]))
  }

  const handleApply = async () => {
    setApplyError(null)
    setIsApplying(true)

    try {
      const applied = await examQuestionService.applyBank(examId, selectedIds)
      if (applied.length < selectedIds.length) {
        setApplyError(
          `${applied.length} dari ${selectedIds.length} soal diterapkan — sisanya sudah ada sebelumnya di ujian ini.`,
        )
        setTimeout(onApplied, 1200)
      } else {
        onApplied()
      }
    } catch (error) {
      setApplyError((error as NormalizedApiError).message)
    } finally {
      setIsApplying(false)
    }
  }

  return (
    <Modal open onClose={onClose} title="Terapkan dari Bank Soal" className="max-w-2xl">
      {loadError && <Alert variant="danger">{loadError}</Alert>}
      {applyError && (
        <Alert variant="warning" className="mb-3" onDismiss={() => setApplyError(null)}>
          {applyError}
        </Alert>
      )}

      <Input
        placeholder="Cari soal..."
        value={search}
        onChange={(event) => setSearch(event.target.value)}
        className="mb-3"
      />

      <div className="max-h-96 overflow-y-auto rounded-lg border border-border">
        {items === null ? (
          <p className="p-4 text-sm text-ink-secondary">Memuat...</p>
        ) : items.length === 0 ? (
          <p className="p-4 text-sm text-ink-secondary">Tidak ada soal ditemukan di bank soal.</p>
        ) : (
          <div className="divide-y divide-border">
            {items.map((item) => (
              <label
                key={item.id}
                className="flex cursor-pointer items-start gap-3 p-3 text-sm hover:bg-surface-hover"
              >
                <Checkbox
                  checked={selectedIds.includes(item.id)}
                  onChange={() => toggle(item.id)}
                  className="mt-0.5"
                />
                <div>
                  <p className="text-ink-primary">{item.question_text}</p>
                  <p className="text-xs text-ink-tertiary">
                    {item.course_name ?? 'Tanpa mata kuliah'} • {item.options.length} opsi • {item.points} poin
                  </p>
                </div>
              </label>
            ))}
          </div>
        )}
      </div>

      <div className="mt-4 flex items-center justify-between">
        <p className="text-sm text-ink-secondary">{selectedIds.length} soal dipilih</p>
        <div className="flex gap-2">
          <Button type="button" variant="outline" onClick={onClose}>
            Batal
          </Button>
          <Button type="button" disabled={selectedIds.length === 0} isLoading={isApplying} onClick={handleApply}>
            Terapkan
          </Button>
        </div>
      </div>
    </Modal>
  )
}
