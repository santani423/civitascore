import { useCallback, useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { universityService } from '@/services/tenancyService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { University, UniversityStatus } from '@/types/tenancy'
import { ROUTES } from '@/constants/routes'

const STATUS_LABEL: Record<UniversityStatus, string> = {
  draft: 'Draft',
  trial: 'Trial',
  active: 'Aktif',
  suspended: 'Suspend',
  expired: 'Kedaluwarsa',
  terminated: 'Terminasi',
}

const STATUS_VARIANT: Record<UniversityStatus, BadgeVariant> = {
  draft: 'neutral',
  trial: 'info',
  active: 'success',
  suspended: 'danger',
  expired: 'warning',
  terminated: 'neutral',
}

const universitySchema = z.object({
  code: z.string().min(1, 'Kode wajib diisi.').max(50),
  name: z.string().min(1, 'Nama wajib diisi.').max(255),
  short_name: z.string().max(50).optional().or(z.literal('')),
  education_institution_type: z.string().max(100).optional().or(z.literal('')),
  accreditation: z.string().max(20).optional().or(z.literal('')),
  email: z.string().email('Format email tidak valid.').max(255).optional().or(z.literal('')),
  phone: z.string().max(30).optional().or(z.literal('')),
  website: z.string().max(255).optional().or(z.literal('')),
})

type UniversityFormValues = z.infer<typeof universitySchema>

export function UniversitiesPage() {
  const canCreate = usePermission('platform_universities.create')
  const canUpdate = usePermission('platform_universities.update')

  const list = usePaginatedList<University>({ fetcher: universityService.index })

  const [formModal, setFormModal] = useState<{ university: University | null } | null>(null)
  const [detailId, setDetailId] = useState<string | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)

  const columns: DataTableColumn<University>[] = [
    {
      header: 'Universitas',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.code}</p>
        </div>
      ),
    },
    { header: 'Jenis', cell: (row) => row.education_institution_type ?? '-' },
    { header: 'Status', cell: (row) => <Badge variant={STATUS_VARIANT[row.status]}>{STATUS_LABEL[row.status]}</Badge> },
    {
      header: '',
      cell: (row) => (
        <div className="flex items-center gap-2">
          <Button variant="ghost" size="sm" onClick={() => setDetailId(row.id)}>
            Detail
          </Button>
          {canUpdate && (
            <Button variant="ghost" size="sm" onClick={() => setFormModal({ university: row })}>
              Ubah
            </Button>
          )}
        </div>
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Manajemen Universitas"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Manajemen Universitas' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setFormModal({ university: null })}>
              Tambah Universitas
            </Button>
          )
        }
      />

      {actionError && (
        <Alert variant="danger" onDismiss={() => setActionError(null)}>
          {actionError}
        </Alert>
      )}

      <Card noPadding>
        <div className="flex items-center gap-3 border-b border-border p-3">
          <Input
            placeholder="Cari nama, kode, atau slug..."
            value={list.search}
            onChange={(event) => list.setSearch(event.target.value)}
            className="max-w-xs"
          />
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Belum ada universitas.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page} ({list.meta.total} universitas)
            </span>
            <div className="flex gap-2">
              <Button variant="outline" size="sm" disabled={list.page <= 1} onClick={() => list.setPage(list.page - 1)}>
                Sebelumnya
              </Button>
              <Button
                variant="outline"
                size="sm"
                disabled={list.page >= list.meta.last_page}
                onClick={() => list.setPage(list.page + 1)}
              >
                Berikutnya
              </Button>
            </div>
          </div>
        )}
      </Card>

      {formModal && (
        <UniversityFormModal
          university={formModal.university}
          onClose={() => setFormModal(null)}
          onSaved={() => {
            setFormModal(null)
            list.refetch()
          }}
        />
      )}

      {detailId && (
        <UniversityDetailModal
          universityId={detailId}
          canUpdate={canUpdate}
          onClose={() => setDetailId(null)}
          onChanged={() => list.refetch()}
          onError={setActionError}
        />
      )}
    </div>
  )
}

function UniversityFormModal({
  university,
  onClose,
  onSaved,
}: {
  university: University | null
  onClose: () => void
  onSaved: () => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<UniversityFormValues>({
    resolver: zodResolver(universitySchema),
    defaultValues: {
      code: university?.code ?? '',
      name: university?.name ?? '',
      short_name: university?.short_name ?? '',
      education_institution_type: university?.education_institution_type ?? '',
      accreditation: university?.accreditation ?? '',
      email: university?.email ?? '',
      phone: university?.phone ?? '',
      website: university?.website ?? '',
    },
  })

  const onSubmit = async (values: UniversityFormValues) => {
    setFormError(null)
    const payload = {
      ...values,
      short_name: values.short_name || null,
      education_institution_type: values.education_institution_type || null,
      accreditation: values.accreditation || null,
      email: values.email || null,
      phone: values.phone || null,
      website: values.website || null,
    }

    try {
      if (university) {
        await universityService.update(university.id, payload)
      } else {
        await universityService.create(payload)
      }
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={university ? 'Ubah Universitas' : 'Tambah Universitas'} className="max-w-lg">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <div className="grid grid-cols-2 gap-4">
          <Input label="Kode" error={errors.code?.message} {...register('code')} />
          <Input label="Nama Singkat" error={errors.short_name?.message} {...register('short_name')} />
        </div>
        <Input label="Nama Universitas" error={errors.name?.message} {...register('name')} />
        <div className="grid grid-cols-2 gap-4">
          <Input label="Jenis Institusi" placeholder="Universitas / Institut / Sekolah Tinggi" error={errors.education_institution_type?.message} {...register('education_institution_type')} />
          <Input label="Akreditasi" placeholder="A / B / C" error={errors.accreditation?.message} {...register('accreditation')} />
        </div>
        <Input label="Email Institusi (kontak)" error={errors.email?.message} {...register('email')} />
        <div className="grid grid-cols-2 gap-4">
          <Input label="Telepon" error={errors.phone?.message} {...register('phone')} />
          <Input label="Website" error={errors.website?.message} {...register('website')} />
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

function UniversityDetailModal({
  universityId,
  canUpdate,
  onClose,
  onChanged,
  onError,
}: {
  universityId: string
  canUpdate: boolean
  onClose: () => void
  onChanged: () => void
  onError: (message: string) => void
}) {
  const fetchUniversity = useCallback(() => universityService.show(universityId), [universityId])
  const { data: university, isLoading, error, setData } = useFetch(fetchUniversity)
  const [isActing, setIsActing] = useState(false)

  useEffect(() => {
    if (error) onError(error)
  }, [error, onError])

  const handleActivate = async () => {
    setIsActing(true)
    try {
      const updated = await universityService.activate(universityId)
      setData(updated)
      onChanged()
    } catch (activateError) {
      onError((activateError as NormalizedApiError).message ?? 'Gagal mengaktifkan universitas.')
    } finally {
      setIsActing(false)
    }
  }

  const handleSuspend = async () => {
    setIsActing(true)
    try {
      const updated = await universityService.suspend(universityId)
      setData(updated)
      onChanged()
    } catch (suspendError) {
      onError((suspendError as NormalizedApiError).message ?? 'Gagal men-suspend universitas.')
    } finally {
      setIsActing(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={university?.name ?? 'Detail Universitas'} className="max-w-lg">
      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}

      {university && (
        <div className="flex flex-col gap-4 text-sm">
          <div className="grid grid-cols-2 gap-3">
            <div>
              <p className="text-ink-tertiary">Kode</p>
              <p className="font-medium text-ink-primary">{university.code}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={STATUS_VARIANT[university.status]}>{STATUS_LABEL[university.status]}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Jenis Institusi</p>
              <p className="font-medium text-ink-primary">{university.education_institution_type ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Akreditasi</p>
              <p className="font-medium text-ink-primary">{university.accreditation ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Email Institusi (kontak, bukan akun login)</p>
              <p className="font-medium text-ink-primary">{university.email ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Domain</p>
              <p className="font-medium text-ink-primary">{university.domains?.join(', ') || '-'}</p>
            </div>
          </div>

          {university.active_subscription && (
            <div className="rounded-lg border border-border p-3">
              <p className="mb-1 font-medium text-ink-primary">Langganan Aktif</p>
              <p className="text-ink-secondary">
                Paket {university.active_subscription.plan ?? '-'} — {university.active_subscription.status}
              </p>
              {university.active_subscription.current_period_ends_at && (
                <p className="text-xs text-ink-tertiary">
                  Berakhir {new Date(university.active_subscription.current_period_ends_at).toLocaleDateString('id-ID')}
                </p>
              )}
            </div>
          )}

          {canUpdate && (
            <div className="flex justify-end gap-2 border-t border-border pt-4">
              {university.status !== 'active' && (
                <Button isLoading={isActing} onClick={handleActivate}>
                  Aktifkan
                </Button>
              )}
              {university.status !== 'suspended' && (
                <Button variant="danger" isLoading={isActing} onClick={handleSuspend}>
                  Suspend
                </Button>
              )}
            </div>
          )}
        </div>
      )}
    </Modal>
  )
}
