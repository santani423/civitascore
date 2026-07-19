import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { permissionService } from '@/services/userManagementService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Permission, PermissionAction, PermissionScope } from '@/types/userManagement'
import { ROUTES } from '@/constants/routes'

const SCOPE_OPTIONS: PermissionScope[] = ['menu', 'module', 'endpoint', 'data']
const ACTION_OPTIONS: PermissionAction[] = [
  'create',
  'read',
  'update',
  'delete',
  'approve',
  'reject',
  'export',
  'import',
  'publish',
  'finalize',
]

const createSchema = z.object({
  name: z.string().min(1, 'Nama wajib diisi.').max(150),
  resource: z.string().min(1, 'Resource wajib diisi.').max(100),
  scope: z.enum(SCOPE_OPTIONS as [PermissionScope, ...PermissionScope[]]),
  action: z.enum(ACTION_OPTIONS as [PermissionAction, ...PermissionAction[]]),
  description: z.string().max(500).optional().or(z.literal('')),
})

const updateSchema = z.object({
  name: z.string().min(1, 'Nama wajib diisi.').max(150),
  description: z.string().max(500).optional().or(z.literal('')),
})

type CreateFormValues = z.infer<typeof createSchema>
type UpdateFormValues = z.infer<typeof updateSchema>

export function PermissionsPage() {
  const canCreate = usePermission('permissions.create')
  const canUpdate = usePermission('permissions.update')
  const canDelete = usePermission('permissions.delete')

  const list = usePaginatedList<Permission>({ fetcher: permissionService.index })

  const [creating, setCreating] = useState(false)
  const [editing, setEditing] = useState<Permission | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Permission | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const columns: DataTableColumn<Permission>[] = [
    {
      header: 'Permission',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.slug}</p>
        </div>
      ),
    },
    { header: 'Resource', cell: (row) => row.resource },
    { header: 'Scope', cell: (row) => <Badge variant="info">{row.scope}</Badge> },
    { header: 'Aksi', cell: (row) => <Badge variant="primary">{row.action}</Badge> },
    {
      header: '',
      cell: (row) => (
        <div className="flex items-center gap-2">
          {canUpdate && (
            <Button variant="ghost" size="sm" onClick={() => setEditing(row)}>
              Ubah
            </Button>
          )}
          {canDelete && !row.is_system && (
            <Button variant="ghost" size="sm" className="text-danger" onClick={() => setDeleteTarget(row)}>
              Hapus
            </Button>
          )}
        </div>
      ),
    },
  ]

  const handleDelete = async () => {
    if (!deleteTarget) return
    setActionError(null)
    setIsDeleting(true)

    try {
      await permissionService.destroy(deleteTarget.id)
      setDeleteTarget(null)
      list.refetch()
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal menghapus permission.')
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Permission"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Permission' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setCreating(true)}>
              Tambah Permission
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
            placeholder="Cari nama, slug, atau resource..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada permission.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page} ({list.meta.total} permission)
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

      {creating && (
        <CreatePermissionModal
          onClose={() => setCreating(false)}
          onSaved={() => {
            setCreating(false)
            list.refetch()
          }}
        />
      )}

      {editing && (
        <EditPermissionModal
          permission={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.refetch()
          }}
        />
      )}

      <Modal open={deleteTarget !== null} onClose={() => setDeleteTarget(null)} title="Hapus Permission">
        <p className="text-sm text-ink-secondary">
          Yakin ingin menghapus permission <span className="font-medium text-ink-primary">{deleteTarget?.name}</span>?
        </p>
        <div className="mt-5 flex justify-end gap-2">
          <Button variant="outline" onClick={() => setDeleteTarget(null)}>
            Batal
          </Button>
          <Button variant="danger" isLoading={isDeleting} onClick={handleDelete}>
            Hapus
          </Button>
        </div>
      </Modal>
    </div>
  )
}

function CreatePermissionModal({ onClose, onSaved }: { onClose: () => void; onSaved: () => void }) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<CreateFormValues>({
    resolver: zodResolver(createSchema),
    defaultValues: { name: '', resource: '', scope: 'menu', action: 'read', description: '' },
  })

  const onSubmit = async (values: CreateFormValues) => {
    setFormError(null)

    try {
      await permissionService.create({ ...values, description: values.description || null })
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Tambah Permission">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input label="Nama" error={errors.name?.message} {...register('name')} />
        <Input label="Resource" error={errors.resource?.message} {...register('resource')} />
        <Select
          label="Scope"
          options={SCOPE_OPTIONS.map((value) => ({ value, label: value }))}
          error={errors.scope?.message}
          {...register('scope')}
        />
        <Select
          label="Aksi"
          options={ACTION_OPTIONS.map((value) => ({ value, label: value }))}
          error={errors.action?.message}
          {...register('action')}
        />
        <Input label="Deskripsi" error={errors.description?.message} {...register('description')} />

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

function EditPermissionModal({
  permission,
  onClose,
  onSaved,
}: {
  permission: Permission
  onClose: () => void
  onSaved: () => void
}) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<UpdateFormValues>({
    resolver: zodResolver(updateSchema),
    defaultValues: { name: permission.name, description: permission.description ?? '' },
  })

  const onSubmit = async (values: UpdateFormValues) => {
    setFormError(null)

    try {
      await permissionService.update(permission.id, { name: values.name, description: values.description || null })
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title="Ubah Permission">
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input label="Nama" error={errors.name?.message} {...register('name')} />
        <Input label="Deskripsi" error={errors.description?.message} {...register('description')} />

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
