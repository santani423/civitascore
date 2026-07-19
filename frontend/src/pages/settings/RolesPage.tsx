import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { Plus, ShieldCheck } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Checkbox } from '@/components/ui/Checkbox'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { roleService, permissionService } from '@/services/userManagementService'
import type { NormalizedApiError } from '@/services/api'
import { applyServerErrors } from '@/utils/applyServerErrors'
import type { Permission, Role } from '@/types/userManagement'
import { ROUTES } from '@/constants/routes'

const roleSchema = z.object({
  name: z.string().min(1, 'Nama role wajib diisi.').max(100, 'Nama role maksimal 100 karakter.'),
  description: z.string().max(500, 'Deskripsi maksimal 500 karakter.').optional().or(z.literal('')),
})

type RoleFormValues = z.infer<typeof roleSchema>

export function RolesPage() {
  const canCreate = usePermission('roles.create')
  const canUpdate = usePermission('roles.update')
  const canDelete = usePermission('roles.delete')

  const list = usePaginatedList<Role>({ fetcher: roleService.index })

  const [formModal, setFormModal] = useState<{ role: Role | null } | null>(null)
  const [permissionModal, setPermissionModal] = useState<Role | null>(null)
  const [deleteTarget, setDeleteTarget] = useState<Role | null>(null)
  const [actionError, setActionError] = useState<string | null>(null)
  const [isDeleting, setIsDeleting] = useState(false)

  const columns: DataTableColumn<Role>[] = [
    {
      header: 'Role',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.slug}</p>
        </div>
      ),
    },
    { header: 'Deskripsi', cell: (row) => row.description ?? '-' },
    {
      header: 'Tipe',
      cell: (row) => <Badge variant={row.is_system ? 'warning' : 'neutral'}>{row.is_system ? 'Sistem' : 'Kustom'}</Badge>,
    },
    {
      header: 'Aksi',
      cell: (row) => (
        <div className="flex items-center gap-2">
          <Button variant="outline" size="sm" leftIcon={<ShieldCheck className="size-3.5" />} onClick={() => setPermissionModal(row)}>
            Kelola Permission
          </Button>
          {canUpdate && (
            <Button variant="ghost" size="sm" onClick={() => setFormModal({ role: row })}>
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
      await roleService.destroy(deleteTarget.id)
      setDeleteTarget(null)
      list.refetch()
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal menghapus role.')
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Role"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Role' }]}
        actions={
          canCreate && (
            <Button leftIcon={<Plus className="size-4" />} onClick={() => setFormModal({ role: null })}>
              Tambah Role
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
            placeholder="Cari nama atau slug role..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada role.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page} ({list.meta.total} role)
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
        <RoleFormModal
          role={formModal.role}
          onClose={() => setFormModal(null)}
          onSaved={() => {
            setFormModal(null)
            list.refetch()
          }}
        />
      )}

      {permissionModal && (
        <ManagePermissionsModal
          role={permissionModal}
          onClose={() => setPermissionModal(null)}
          onSaved={() => {
            setPermissionModal(null)
            list.refetch()
          }}
        />
      )}

      <Modal open={deleteTarget !== null} onClose={() => setDeleteTarget(null)} title="Hapus Role">
        <p className="text-sm text-ink-secondary">
          Yakin ingin menghapus role <span className="font-medium text-ink-primary">{deleteTarget?.name}</span>? Tindakan ini
          tidak dapat dibatalkan.
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

function RoleFormModal({ role, onClose, onSaved }: { role: Role | null; onClose: () => void; onSaved: () => void }) {
  const [formError, setFormError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<RoleFormValues>({
    resolver: zodResolver(roleSchema),
    defaultValues: { name: role?.name ?? '', description: role?.description ?? '' },
  })

  const onSubmit = async (values: RoleFormValues) => {
    setFormError(null)
    const payload = { name: values.name, description: values.description || null }

    try {
      if (role) {
        await roleService.update(role.id, payload)
      } else {
        await roleService.create(payload)
      }
      onSaved()
    } catch (error) {
      setFormError(applyServerErrors(error as NormalizedApiError, setError))
    }
  }

  return (
    <Modal open onClose={onClose} title={role ? 'Ubah Role' : 'Tambah Role'}>
      {formError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setFormError(null)}>
          {formError}
        </Alert>
      )}

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-4" noValidate>
        <Input label="Nama Role" error={errors.name?.message} {...register('name')} />
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

function ManagePermissionsModal({ role, onClose, onSaved }: { role: Role; onClose: () => void; onSaved: () => void }) {
  const [allPermissions, setAllPermissions] = useState<Permission[] | null>(null)
  const [selected, setSelected] = useState<Set<string>>(new Set(role.permissions.map((permission) => permission.id)))
  const [loadError, setLoadError] = useState<string | null>(null)
  const [saveError, setSaveError] = useState<string | null>(null)
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    permissionService
      .index({ per_page: 100 })
      .then((result) => setAllPermissions(result.data))
      .catch((error: NormalizedApiError) => setLoadError(error.message))
  }, [])

  const toggle = (permissionId: string) => {
    setSelected((current) => {
      const next = new Set(current)
      if (next.has(permissionId)) {
        next.delete(permissionId)
      } else {
        next.add(permissionId)
      }
      return next
    })
  }

  const handleSave = async () => {
    setSaveError(null)
    setIsSaving(true)

    try {
      await roleService.syncPermissions(role.id, Array.from(selected))
      onSaved()
    } catch (error) {
      setSaveError((error as NormalizedApiError).message ?? 'Gagal menyimpan permission.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={`Kelola Permission — ${role.name}`} className="max-w-lg">
      {loadError && <Alert variant="danger">{loadError}</Alert>}
      {saveError && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setSaveError(null)}>
          {saveError}
        </Alert>
      )}

      {!allPermissions ? (
        <p className="text-sm text-ink-secondary">Memuat daftar permission...</p>
      ) : (
        <div className="max-h-80 overflow-y-auto rounded-lg border border-border">
          {allPermissions.map((permission) => (
            <label
              key={permission.id}
              className="flex items-center gap-2 border-b border-border px-3 py-2 last:border-b-0 hover:bg-surface-hover"
            >
              <Checkbox checked={selected.has(permission.id)} onChange={() => toggle(permission.id)} />
              <span className="flex-1">
                <span className="block text-sm font-medium text-ink-primary">{permission.name}</span>
                <span className="block text-xs text-ink-tertiary">{permission.slug}</span>
              </span>
            </label>
          ))}
        </div>
      )}

      <div className="mt-5 flex justify-end gap-2">
        <Button variant="outline" onClick={onClose}>
          Batal
        </Button>
        <Button isLoading={isSaving} onClick={handleSave}>
          Simpan
        </Button>
      </div>
    </Modal>
  )
}
