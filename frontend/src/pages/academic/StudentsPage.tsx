import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { Link, useSearchParams } from 'react-router-dom'
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
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { classSectionService, studentService, studyProgramService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Student, StudentStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'
import { formatDateRange, humanizeSlug } from '@/utils/formatters'

// Angkatan tidak punya endpoint list tersendiri (kolom integer polos di
// `students`) — cukup rentang tahun berjalan turun beberapa tahun, tidak
// perlu agregasi dari backend.
const ADMISSION_YEAR_OPTIONS = Array.from({ length: 7 }, (_, index) => {
  const year = new Date().getFullYear() - index
  return { value: String(year), label: String(year) }
})

const STATUS_LABEL: Record<StudentStatus, string> = {
  active: 'Aktif',
  leave: 'Cuti',
  graduated: 'Lulus',
  inactive: 'Nonaktif',
  dropped_out: 'Drop Out',
}

const STATUS_VARIANT: Record<StudentStatus, BadgeVariant> = {
  active: 'success',
  leave: 'warning',
  graduated: 'info',
  inactive: 'neutral',
  dropped_out: 'danger',
}

export function StudentsPage() {
  const canCreate = usePermission('students.create')
  const [showForm, setShowForm] = useState(false)

  const [searchParams] = useSearchParams()
  // Filter awal dari link kartu/chart dashboard (mis. ?status=active) —
  // dibaca sekali saat mount, lihat utils/listInitial.ts.
  const [initialFilter] = useState(() =>
    pickFilterParams(searchParams, ['status', 'study_program_id', 'admission_year', 'class_section_id']),
  )

  const [searchInput, setSearchInput] = useState('')
  const [statusInput, setStatusInput] = useState(initialFilter.status ?? '')
  const [programInput, setProgramInput] = useState(initialFilter.study_program_id ?? '')
  const [admissionYearInput, setAdmissionYearInput] = useState(initialFilter.admission_year ?? '')
  const [classSectionInput, setClassSectionInput] = useState(initialFilter.class_section_id ?? '')

  const list = usePaginatedList<Student>({ fetcher: studentService.index, initialFilter })

  const getStudyPrograms = useCallback(async () => (await studyProgramService.index({ per_page: 100 })).data, [])
  const { data: studyPrograms } = useFetch(getStudyPrograms)

  const getClassSections = useCallback(
    async () => (await classSectionService.index({ per_page: 100, filter: { is_active: '1' } })).data,
    [],
  )
  const { data: classSections } = useFetch(getClassSections)

  const applyFilters = () => {
    list.setSearch(searchInput)
    const next: Record<string, string> = {}
    if (statusInput) next.status = statusInput
    if (programInput) next.study_program_id = programInput
    if (admissionYearInput) next.admission_year = admissionYearInput
    if (classSectionInput) next.class_section_id = classSectionInput
    list.setFilter(next)
  }

  const columns: DataTableColumn<Student>[] = [
    {
      header: 'Mahasiswa',
      cell: (row) => (
        <Link to={`${ROUTES.mahasiswa}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.nim}</p>
        </Link>
      ),
    },
    { header: 'Program Studi', cell: (row) => row.study_program_name ?? '-' },
    { header: 'Angkatan', cell: (row) => row.admission_year },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status] ?? humanizeSlug(row.status)}</Badge>,
    },
    { header: 'Email', cell: (row) => row.email ?? '-' },
    {
      header: 'Terdaftar',
      cell: (row) => (row.graduated_at ? formatDateRange(row.enrolled_at, row.graduated_at) : row.enrolled_at),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Mahasiswa"
        description="Daftar mahasiswa terdaftar pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Mahasiswa' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowForm(true)}>
              Tambah Mahasiswa
            </Button>
          )
        }
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama atau NIM..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Select
            label="Status"
            value={statusInput}
            onChange={(event) => setStatusInput(event.target.value)}
            options={Object.entries(STATUS_LABEL).map(([value, label]) => ({ value, label }))}
            placeholder="Semua status"
          />
          <Select
            label="Program Studi"
            value={programInput}
            onChange={(event) => setProgramInput(event.target.value)}
            options={(studyPrograms ?? []).map((program) => ({ value: program.id, label: program.name }))}
            placeholder="Semua program studi"
          />
          <Select
            label="Angkatan"
            value={admissionYearInput}
            onChange={(event) => setAdmissionYearInput(event.target.value)}
            options={ADMISSION_YEAR_OPTIONS}
            placeholder="Semua angkatan"
          />
          <Select
            label="Kelas"
            value={classSectionInput}
            onChange={(event) => setClassSectionInput(event.target.value)}
            options={(classSections ?? []).map((section) => ({
              value: section.id,
              label: `${section.course_name ?? '-'} (${section.class_code})`,
            }))}
            placeholder="Semua kelas"
          />
          <Button variant="outline" onClick={applyFilters}>
            Terapkan Filter
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada mahasiswa terdaftar.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="mahasiswa" />
      </Card>

      {showForm && (
        <StudentFormModal
          studyPrograms={studyPrograms ?? []}
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

const studentSchema = z.object({
  study_program_id: z.string().min(1, 'Program studi wajib dipilih.'),
  nim: z.string().min(1, 'NIM wajib diisi.'),
  name: z.string().min(1, 'Nama wajib diisi.'),
  email: z.string().email('Format email tidak valid.').optional().or(z.literal('')),
  tanggal_lahir: z.string().optional(),
  admission_year: z.number({ message: 'Angkatan wajib diisi.' }).min(2000).max(2100),
  status: z.enum(['active', 'leave', 'graduated', 'inactive', 'dropped_out']),
  enrolled_at: z.string().min(1, 'Tanggal terdaftar wajib diisi.'),
})

type StudentFormValues = z.infer<typeof studentSchema>

function StudentFormModal({
  studyPrograms,
  onClose,
  onSaved,
}: {
  studyPrograms: { id: string; name: string }[]
  onClose: () => void
  onSaved: () => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<StudentFormValues>({
    resolver: zodResolver(studentSchema),
    defaultValues: {
      study_program_id: '',
      nim: '',
      name: '',
      email: '',
      tanggal_lahir: '',
      admission_year: new Date().getFullYear(),
      status: 'active',
      enrolled_at: new Date().toISOString().slice(0, 10),
    },
  })

  const onSubmit = async (values: StudentFormValues) => {
    setFormError(null)

    try {
      await studentService.create({
        ...values,
        email: values.email || null,
        tanggal_lahir: values.tanggal_lahir || null,
      })
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Tambah Mahasiswa" className="max-w-xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Select
          label="Program Studi"
          error={errors.study_program_id?.message}
          options={studyPrograms.map((program) => ({ value: program.id, label: program.name }))}
          placeholder="Pilih program studi"
          {...register('study_program_id')}
        />
        <div className="grid grid-cols-2 gap-4">
          <Input label="NIM" error={errors.nim?.message} {...register('nim')} />
          <Input label="Nama Lengkap" error={errors.name?.message} {...register('name')} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <Input type="email" label="Email" error={errors.email?.message} {...register('email')} />
          <Input type="date" label="Tanggal Lahir" error={errors.tanggal_lahir?.message} {...register('tanggal_lahir')} />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <Input
            type="number"
            label="Angkatan"
            error={errors.admission_year?.message}
            {...register('admission_year', { valueAsNumber: true })}
          />
          <Select
            label="Status"
            error={errors.status?.message}
            options={Object.entries(STATUS_LABEL).map(([value, label]) => ({ value, label }))}
            {...register('status')}
          />
        </div>
        <Input type="date" label="Tanggal Terdaftar" error={errors.enrolled_at?.message} {...register('enrolled_at')} />

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
