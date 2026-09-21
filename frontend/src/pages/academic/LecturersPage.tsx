import { useState } from 'react'
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
import { Checkbox } from '@/components/ui/Checkbox'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { ListPagination } from '@/components/ui/ListPagination'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { lecturerService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Lecturer } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { pickFilterParams } from '@/utils/listInitial'

export function LecturersPage() {
  const canCreate = usePermission('lecturers.create')
  const [showForm, setShowForm] = useState(false)

  const [searchParams] = useSearchParams()
  const [initialFilter] = useState(() => pickFilterParams(searchParams, ['faculty_id', 'is_active']))
  const [searchInput, setSearchInput] = useState('')

  const list = usePaginatedList<Lecturer>({ fetcher: lecturerService.index, initialFilter })

  const columns: DataTableColumn<Lecturer>[] = [
    {
      header: 'Dosen',
      cell: (row) => (
        <Link to={`${ROUTES.dosen}/${row.id}`} className="block">
          <p className="font-medium text-primary hover:underline">{row.name}</p>
          <p className="text-xs text-ink-tertiary">NIDN {row.nidn}</p>
        </Link>
      ),
    },
    { header: 'Fakultas', cell: (row) => row.faculty_name ?? '-' },
    { header: 'Email', cell: (row) => row.email ?? '-' },
    {
      header: 'Status',
      cell: (row) => <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Nonaktif'}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Dosen"
        description="Daftar dosen pada universitas Anda."
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
            placeholder="Nama atau NIDN..."
            value={searchInput}
            onChange={(event) => setSearchInput(event.target.value)}
          />
          <Button variant="outline" onClick={() => list.setSearch(searchInput)}>
            Cari
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
          onSaved={() => {
            setShowForm(false)
            list.refetch()
          }}
        />
      )}
    </div>
  )
}

const lecturerSchema = z.object({
  nidn: z.string().min(1, 'NIDN wajib diisi.'),
  name: z.string().min(1, 'Nama wajib diisi.'),
  email: z.string().email('Format email tidak valid.').optional().or(z.literal('')),
  is_active: z.boolean(),
})

type LecturerFormValues = z.infer<typeof lecturerSchema>

export function LecturerFormModal({
  lecturer,
  onClose,
  onSaved,
}: {
  lecturer?: Lecturer
  onClose: () => void
  onSaved: (lecturer: Lecturer) => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<LecturerFormValues>({
    resolver: zodResolver(lecturerSchema),
    defaultValues: {
      nidn: lecturer?.nidn ?? '',
      name: lecturer?.name ?? '',
      email: lecturer?.email ?? '',
      is_active: lecturer?.is_active ?? true,
    },
  })

  const onSubmit = async (values: LecturerFormValues) => {
    setFormError(null)
    const payload = { ...values, email: values.email || null }

    try {
      const saved = lecturer
        ? await lecturerService.update(lecturer.id, payload)
        : await lecturerService.create(payload)
      onSaved(saved)
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={lecturer ? 'Ubah Dosen' : 'Tambah Dosen'} className="max-w-xl">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <div className="grid grid-cols-2 gap-4">
          <Input label="NIDN" error={errors.nidn?.message} {...register('nidn')} />
          <Input label="Nama Lengkap" error={errors.name?.message} {...register('name')} />
        </div>
        <Input type="email" label="Email" error={errors.email?.message} {...register('email')} />
        <Checkbox label="Aktif" {...register('is_active')} />

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
