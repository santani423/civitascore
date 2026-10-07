import { useCallback, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { Link, useSearchParams } from 'react-router-dom'
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
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { facultyService, lecturerService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type {
  Lecturer,
  LecturerAccountCredentials,
  LecturerEducationLevel,
  LecturerEmploymentStatus,
  LecturerFunctionalRank,
  LecturerWithCredentials,
} from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import {
  EDUCATION_LEVEL_LABEL,
  EMPLOYMENT_STATUS_LABEL,
  FUNCTIONAL_RANK_LABEL,
  lecturerAccountBadge,
  toOptions,
} from '@/constants/lecturer'
import { pickFilterParams } from '@/utils/listInitial'
import { LecturerCredentialsModal } from './LecturerCredentialsModal'

const ACTIVE_OPTIONS = [
  { value: '1', label: 'Aktif' },
  { value: '0', label: 'Nonaktif' },
]

const ACCOUNT_OPTIONS = [
  { value: '1', label: 'Sudah punya akun' },
  { value: '0', label: 'Belum punya akun' },
]

const FILTER_KEYS = ['faculty_id', 'is_active', 'employment_status', 'functional_rank', 'has_account']

function useFacultyOptions() {
  const getFaculties = useCallback(() => facultyService.options(), [])
  const { data } = useFetch(getFaculties)
  return (data ?? []).map((faculty) => ({ value: faculty.id, label: faculty.name }))
}

export function LecturersPage() {
  const canCreate = usePermission('lecturers.create')
  const [showForm, setShowForm] = useState(false)
  const [credentials, setCredentials] = useState<LecturerAccountCredentials | null>(null)

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, FILTER_KEYS))
  const [searchInput, setSearchInput] = useState('')
  const [filterInput, setFilterInput] = useState<Record<string, string>>(initialFilter)
  const facultyOptions = useFacultyOptions()

  const list = usePaginatedList<Lecturer>({ fetcher: lecturerService.index, initialFilter })

  const setFilterValue = (key: string, value: string) => setFilterInput((current) => ({ ...current, [key]: value }))

  const applyFilters = () => {
    list.setSearch(searchInput)
    list.setFilter(Object.fromEntries(Object.entries(filterInput).filter(([, value]) => value !== '')))
  }

  const columns: DataTableColumn<Lecturer>[] = [
    {
      header: 'Dosen',
      cell: (row) => (
        <Link to={`${ROUTES.dosen}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">
            NIDN {row.nidn}
            {row.nip ? ` · NIP ${row.nip}` : ''}
          </p>
        </Link>
      ),
    },
    { header: 'Fakultas', cell: (row) => row.faculty_name ?? '-' },
    {
      header: 'Kepegawaian',
      cell: (row) => (
        <div>
          <p>{row.employment_status ? EMPLOYMENT_STATUS_LABEL[row.employment_status] : '-'}</p>
          {row.functional_rank && (
            <p className="text-xs text-ink-tertiary">{FUNCTIONAL_RANK_LABEL[row.functional_rank]}</p>
          )}
        </div>
      ),
    },
    { header: 'Email', cell: (row) => row.email ?? '-' },
    {
      header: 'Akun Login',
      cell: (row) => {
        const badge = lecturerAccountBadge(row)
        return <Badge variant={badge.variant}>{badge.label}</Badge>
      },
    },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Dosen"
        description="Data induk dosen beserta akun login mereka pada universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Dosen' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setShowForm(true)}>
              Tambah Dosen
            </Button>
          )
        }
      />

      <Card noPadding>
        <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
          <Input
            label="Cari"
            placeholder="Nama, NIDN, NIP, atau email..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
            onKeyDown={(event) => {
              if (event.key === 'Enter') applyFilters()
            }}
          />
          <Select
            label="Fakultas"
            value={filterInput.faculty_id ?? ''}
            onChange={(event) => setFilterValue('faculty_id', event.target.value)}
            options={facultyOptions}
            placeholder="Semua fakultas"
          />
          <Select
            label="Status Kepegawaian"
            value={filterInput.employment_status ?? ''}
            onChange={(event) => setFilterValue('employment_status', event.target.value)}
            options={toOptions(EMPLOYMENT_STATUS_LABEL)}
            placeholder="Semua"
          />
          <Select
            label="Jabatan Fungsional"
            value={filterInput.functional_rank ?? ''}
            onChange={(event) => setFilterValue('functional_rank', event.target.value)}
            options={toOptions(FUNCTIONAL_RANK_LABEL)}
            placeholder="Semua"
          />
          <Select
            label="Akun Login"
            value={filterInput.has_account ?? ''}
            onChange={(event) => setFilterValue('has_account', event.target.value)}
            options={ACCOUNT_OPTIONS}
            placeholder="Semua"
          />
          <Select
            label="Status"
            value={filterInput.is_active ?? ''}
            onChange={(event) => setFilterValue('is_active', event.target.value)}
            options={ACTIVE_OPTIONS}
            placeholder="Semua status"
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada dosen terdaftar.'}
          />
        )}

        <ListPagination meta={list.meta} page={list.page} onPageChange={list.setPage} itemLabel="dosen" />
      </Card>

      {showForm && (
        <LecturerFormModal
          onClose={() => setShowForm(false)}
          onCreated={(result) => {
            setShowForm(false)
            setCredentials(result.credentials)
            list.refetch()
          }}
          onSaved={() => {
            setShowForm(false)
            list.refetch()
          }}
        />
      )}

      {credentials && <LecturerCredentialsModal credentials={credentials} onClose={() => setCredentials(null)} />}
    </div>
  )
}

const optionalEnum = <T extends [string, ...string[]]>(values: T) => z.enum(values).or(z.literal(''))

const lecturerSchema = z
  .object({
    nidn: z.string().trim().min(1, 'NIDN wajib diisi.').max(50, 'NIDN maksimal 50 karakter.'),
    nip: z.string().trim().max(50, 'NIP maksimal 50 karakter.'),
    name: z.string().trim().min(1, 'Nama wajib diisi.'),
    email: z.string().trim().email('Format email tidak valid.').or(z.literal('')),
    phone: z.string().trim().max(30, 'Nomor telepon maksimal 30 karakter.'),
    faculty_id: z.string(),
    employment_status: optionalEnum(['permanent', 'contract', 'honorary']),
    functional_rank: optionalEnum(['none', 'asisten_ahli', 'lektor', 'lektor_kepala', 'guru_besar']),
    highest_education: optionalEnum(['s1', 's2', 's3']),
    hired_at: z.string(),
    is_active: z.boolean(),
    create_account: z.boolean(),
  })
  .refine((values) => !values.create_account || values.email !== '', {
    message: 'Email wajib diisi untuk membuat akun login dosen.',
    path: ['email'],
  })

type LecturerFormValues = z.infer<typeof lecturerSchema>

const emptyToNull = (value: string): string | null => (value === '' ? null : value)

export function LecturerFormModal({
  lecturer,
  onClose,
  onSaved,
  onCreated,
}: {
  lecturer?: Lecturer
  onClose: () => void
  onSaved: (lecturer: Lecturer) => void
  /** Dipanggil saat menambah dosen baru — membawa kredensial akun bila dibuat. */
  onCreated?: (result: LecturerWithCredentials) => void
}) {
  const [formError, setFormError] = useState<string | null>(null)
  const facultyOptions = useFacultyOptions()
  const isEdit = lecturer !== undefined
  const hasLinkedAccount = lecturer?.has_account ?? false

  const {
    register,
    handleSubmit,
    setError,
    watch,
    formState: { errors, isSubmitting },
  } = useForm<LecturerFormValues>({
    resolver: zodResolver(lecturerSchema),
    defaultValues: {
      nidn: lecturer?.nidn ?? '',
      nip: lecturer?.nip ?? '',
      name: lecturer?.name ?? '',
      email: lecturer?.email ?? '',
      phone: lecturer?.phone ?? '',
      faculty_id: lecturer?.faculty_id ?? '',
      employment_status: lecturer?.employment_status ?? '',
      functional_rank: lecturer?.functional_rank ?? '',
      highest_education: lecturer?.highest_education ?? '',
      hired_at: lecturer?.hired_at ?? '',
      is_active: lecturer?.is_active ?? true,
      create_account: !isEdit,
    },
  })

  const createAccount = watch('create_account')

  const onSubmit = async (values: LecturerFormValues) => {
    setFormError(null)
    const payload = {
      nidn: values.nidn,
      nip: emptyToNull(values.nip),
      name: values.name,
      email: emptyToNull(values.email),
      phone: emptyToNull(values.phone),
      faculty_id: emptyToNull(values.faculty_id),
      employment_status: emptyToNull(values.employment_status) as LecturerEmploymentStatus | null,
      functional_rank: emptyToNull(values.functional_rank) as LecturerFunctionalRank | null,
      highest_education: emptyToNull(values.highest_education) as LecturerEducationLevel | null,
      hired_at: emptyToNull(values.hired_at),
      is_active: values.is_active,
    }

    try {
      if (lecturer) {
        onSaved(await lecturerService.update(lecturer.id, payload))
      } else {
        const result = await lecturerService.create({ ...payload, create_account: values.create_account })
        if (onCreated) onCreated(result)
        else onSaved(result.lecturer)
      }
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={isEdit ? 'Ubah Dosen' : 'Tambah Dosen'} className="max-w-2xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5" noValidate>
        <fieldset className="flex flex-col gap-4">
          <legend className="mb-2 text-sm font-semibold text-ink-primary">Identitas</legend>
          <Input label="Nama Lengkap (dengan gelar)" error={errors.name?.message} {...register('name')} />
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Input label="NIDN" error={errors.nidn?.message} {...register('nidn')} />
            <Input label="NIP / Nomor Induk Pegawai" error={errors.nip?.message} {...register('nip')} />
            <Input
              type="email"
              label="Email"
              error={errors.email?.message}
              hint={hasLinkedAccount ? 'Juga dipakai sebagai email login akun dosen.' : undefined}
              {...register('email')}
            />
            <Input label="No. Telepon" error={errors.phone?.message} {...register('phone')} />
          </div>
        </fieldset>

        <fieldset className="flex flex-col gap-4">
          <legend className="mb-2 text-sm font-semibold text-ink-primary">Kepegawaian & Akademik</legend>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Select
              label="Fakultas"
              options={facultyOptions}
              placeholder="Pilih fakultas"
              error={errors.faculty_id?.message}
              {...register('faculty_id')}
            />
            <Select
              label="Status Kepegawaian"
              options={toOptions(EMPLOYMENT_STATUS_LABEL)}
              placeholder="Pilih status"
              error={errors.employment_status?.message}
              {...register('employment_status')}
            />
            <Select
              label="Jabatan Fungsional"
              options={toOptions(FUNCTIONAL_RANK_LABEL)}
              placeholder="Pilih jabatan"
              error={errors.functional_rank?.message}
              {...register('functional_rank')}
            />
            <Select
              label="Pendidikan Terakhir"
              options={toOptions(EDUCATION_LEVEL_LABEL)}
              placeholder="Pilih jenjang"
              error={errors.highest_education?.message}
              {...register('highest_education')}
            />
            <Input type="date" label="Mulai Bertugas" error={errors.hired_at?.message} {...register('hired_at')} />
          </div>
          <Checkbox label="Dosen aktif" {...register('is_active')} />
          {isEdit && hasLinkedAccount && (
            <p className="text-xs text-ink-tertiary">
              Menonaktifkan dosen juga menonaktifkan akun loginnya; mengaktifkan kembali memulihkan akses login.
            </p>
          )}
        </fieldset>

        {!isEdit && (
          <fieldset className="flex flex-col gap-2 rounded-lg border border-border p-4">
            <legend className="px-1 text-sm font-semibold text-ink-primary">Akun Login</legend>
            <Checkbox label="Buatkan akun login dosen (role Dosen)" {...register('create_account')} />
            <p className="text-xs text-ink-tertiary">
              {createAccount
                ? 'Akun dibuat dengan email di atas dan password sementara yang ditampilkan setelah disimpan. Dosen wajib menggantinya saat login pertama. Bila email sudah terdaftar sebagai pengguna, dosen ditautkan ke akun tersebut.'
                : 'Akun login dapat dibuat nanti dari halaman detail dosen.'}
            </p>
          </fieldset>
        )}

        <div className="flex justify-end gap-2">
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
