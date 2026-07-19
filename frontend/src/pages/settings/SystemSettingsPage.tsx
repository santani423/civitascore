import { useState } from 'react'
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
import { systemSettingService } from '@/services/systemSettingService'
import type { NormalizedApiError } from '@/services/api'
import type { SystemSetting } from '@/types/systemSetting'
import { ROUTES } from '@/constants/routes'

function displayValue(setting: SystemSetting): string {
  if (setting.value === null) return '-'
  if (setting.type === 'boolean') return setting.value ? 'Aktif' : 'Nonaktif'
  if (setting.type === 'json') return JSON.stringify(setting.value)
  return String(setting.value)
}

export function SystemSettingsPage() {
  const canUpdate = usePermission('system_settings.update')
  const list = usePaginatedList<SystemSetting>({ fetcher: systemSettingService.index })
  const [editing, setEditing] = useState<SystemSetting | null>(null)

  const columns: DataTableColumn<SystemSetting>[] = [
    {
      header: 'Key',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.key}</p>
          {row.description && <p className="text-xs text-ink-tertiary">{row.description}</p>}
        </div>
      ),
    },
    { header: 'Grup', cell: (row) => row.group ?? '-' },
    { header: 'Nilai', cell: (row) => displayValue(row) },
    {
      header: 'Publik',
      cell: (row) => <Badge variant={row.is_public ? 'success' : 'neutral'}>{row.is_public ? 'Ya' : 'Tidak'}</Badge>,
    },
    {
      header: '',
      cell: (row) =>
        canUpdate ? (
          <Button variant="ghost" size="sm" onClick={() => setEditing(row)}>
            Ubah
          </Button>
        ) : null,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengaturan Sistem"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Pengaturan Sistem' }]}
      />

      <Card noPadding>
        <div className="flex items-center gap-3 border-b border-border p-3">
          <Input
            placeholder="Cari key atau grup..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada pengaturan.'}
          />
        )}

        {list.meta && list.meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-border p-3 text-sm text-ink-secondary">
            <span>
              Halaman {list.meta.current_page} dari {list.meta.last_page}
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

      {editing && (
        <EditSettingModal
          setting={editing}
          onClose={() => setEditing(null)}
          onSaved={() => {
            setEditing(null)
            list.refetch()
          }}
        />
      )}
    </div>
  )
}

function EditSettingModal({
  setting,
  onClose,
  onSaved,
}: {
  setting: SystemSetting
  onClose: () => void
  onSaved: () => void
}) {
  const [value, setValue] = useState(setting.type === 'json' ? JSON.stringify(setting.value, null, 2) : String(setting.value ?? ''))
  const [boolValue, setBoolValue] = useState(Boolean(setting.value))
  const [error, setError] = useState<string | null>(null)
  const [isSaving, setIsSaving] = useState(false)

  const handleSave = async () => {
    setError(null)
    setIsSaving(true)

    try {
      await systemSettingService.update(setting.id, setting.type === 'boolean' ? String(boolValue) : value)
      onSaved()
    } catch (submitError) {
      setError((submitError as NormalizedApiError).message ?? 'Gagal menyimpan pengaturan.')
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <Modal open onClose={onClose} title={`Ubah ${setting.key}`}>
      {error && (
        <Alert variant="danger" className="mb-4" onDismiss={() => setError(null)}>
          {error}
        </Alert>
      )}

      {setting.description && <p className="mb-3 text-sm text-ink-secondary">{setting.description}</p>}

      {setting.type === 'boolean' ? (
        <Checkbox label="Aktifkan" checked={boolValue} onChange={(event) => setBoolValue(event.target.checked)} />
      ) : (
        <Input label="Nilai" value={value} onChange={(event) => setValue(event.target.value)} />
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
