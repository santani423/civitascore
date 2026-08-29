import { useEffect, useMemo, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { Link } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Checkbox } from '@/components/ui/Checkbox'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { classSectionService, examService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import { QUESTION_SELECTION_MODE_LABEL } from '@/utils/examValidation'
import { fromDatetimeLocalValue } from '@/utils/formatters'
import type { ClassSection, Exam } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

export function ExamsPage() {
  const canManage = usePermission(['exams.create', 'exams.update'])

  const list = usePaginatedList<Exam>({ fetcher: examService.index })
  const [showForm, setShowForm] = useState(false)

  const columns: DataTableColumn<Exam>[] = [
    {
      header: 'Ujian',
      cell: (row) => (
        <Link to={`${ROUTES.akademik.ujian}/${row.id}`} className="font-medium text-primary hover:underline">
          {row.title}
        </Link>
      ),
    },
    {
      header: 'Kelas',
      cell: (row) => (
        <div>
          <p className="text-ink-primary">{row.course_name ?? '-'}</p>
          <p className="text-xs text-ink-tertiary">{row.class_code ?? '-'}</p>
        </div>
      ),
    },
    { header: 'Soal', cell: (row) => `${row.questions_per_participant} / ${row.question_pool_size}` },
    { header: 'Pemilihan', cell: (row) => QUESTION_SELECTION_MODE_LABEL[row.question_selection_mode] },
    {
      header: 'Status',
      cell: (row) => (
        <Badge variant={row.is_published ? 'success' : 'neutral'}>{row.is_published ? 'Dipublikasikan' : 'Draft'}</Badge>
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Ujian"
        description="Konfigurasi ujian pilihan ganda — question pool, pemilihan soal, dan pengacakan."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Ujian' }]}
        actions={
          canManage && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowForm(true)}>
              Buat Ujian
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada ujian.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="ujian" />
      </Card>

      {showForm && (
        <ExamFormModal
          onClose={() => setShowForm(false)}
          onSaved={() => {
            setShowForm(false)
            list.refetch()
          }}
        />
      )}
    </div>
  )
}

const examSchema = z
  .object({
    course_id: z.string().min(1, 'Mata kuliah wajib dipilih.'),
    class_section_id: z.string().min(1, 'Kelas wajib dipilih.'),
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

type ExamFormValues = z.infer<typeof examSchema>

/** Dipakai untuk membuat ujian baru — sisi klien pemilihan kelas hanya untuk saat pembuatan (backend tidak mengizinkan class_section_id diubah setelahnya). */
function ExamFormModal({ onClose, onSaved }: { onClose: () => void; onSaved: (exam: Exam) => void }) {
  const [classSections, setClassSections] = useState<ClassSection[] | null>(null)
  const [loadError, setLoadError] = useState<string | null>(null)
  const [formError, setFormError] = useState<string | null>(null)

  useEffect(() => {
    classSectionService
      .index({ per_page: 200 })
      .then((result) => setClassSections(result.data))
      .catch((error: NormalizedApiError) => setLoadError(error.message))
  }, [])

  // Daftar mata kuliah diturunkan dari kelas yang benar-benar ditawarkan
  // (bukan dari seluruh katalog courseService) — supaya dosen tidak bisa
  // memilih mata kuliah yang kelasnya kosong (dead-end di dropdown Kelas).
  const courses = useMemo(() => {
    if (classSections === null) return null

    const seen = new Map<string, { id: string; name: string; code: string }>()
    for (const cs of classSections) {
      if (!seen.has(cs.course_id)) {
        seen.set(cs.course_id, { id: cs.course_id, name: cs.course_name ?? '-', code: cs.course_code ?? '-' })
      }
    }

    return Array.from(seen.values()).sort((a, b) => a.name.localeCompare(b.name))
  }, [classSections])

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<ExamFormValues>({
    resolver: zodResolver(examSchema),
    defaultValues: {
      course_id: '',
      class_section_id: '',
      title: '',
      duration_minutes: 90,
      starts_at: '',
      ends_at: '',
      questions_per_participant: 1,
      question_selection_mode: 'all',
      max_attempts: 1,
      randomize_questions: false,
      randomize_options: false,
    },
  })

  const selectedCourseId = watch('course_id')
  const filteredClassSections = useMemo(
    () => (classSections ?? []).filter((cs) => cs.course_id === selectedCourseId),
    [classSections, selectedCourseId],
  )

  const onSubmit = async (values: ExamFormValues) => {
    setFormError(null)

    try {
      const exam = await examService.create({
        class_section_id: values.class_section_id,
        title: values.title,
        duration_minutes: values.duration_minutes,
        starts_at: fromDatetimeLocalValue(values.starts_at),
        ends_at: fromDatetimeLocalValue(values.ends_at),
        questions_per_participant: values.questions_per_participant,
        question_selection_mode: values.question_selection_mode,
        max_attempts: values.max_attempts,
        randomize_questions: values.randomize_questions,
        randomize_options: values.randomize_options,
      })
      onSaved(exam)
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Buat Ujian" className="max-w-xl">
      {loadError && <Alert variant="danger">{loadError}</Alert>}
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      {courses === null || classSections === null ? (
        <p className="text-sm text-ink-secondary">Memuat data mata kuliah &amp; kelas...</p>
      ) : (
        <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
          <Select
            label="Mata Kuliah"
            placeholder="Pilih mata kuliah"
            error={errors.course_id?.message}
            options={courses.map((course) => ({ value: course.id, label: `${course.name} (${course.code})` }))}
            {...register('course_id', {
              onChange: () => setValue('class_section_id', ''),
            })}
          />
          <Select
            label="Kelas"
            placeholder={selectedCourseId ? 'Pilih kelas' : 'Pilih mata kuliah terlebih dahulu'}
            disabled={!selectedCourseId}
            error={errors.class_section_id?.message}
            options={filteredClassSections.map((cs) => ({ value: cs.id, label: cs.class_code }))}
            {...register('class_section_id')}
          />
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
              Buat Ujian
            </Button>
          </div>
        </form>
      )}
    </Modal>
  )
}
