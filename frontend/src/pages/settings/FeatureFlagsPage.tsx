import { useState } from 'react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Checkbox } from '@/components/ui/Checkbox'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { usePaginatedList } from '@/hooks/usePaginatedList'
import { usePermission } from '@/hooks/usePermission'
import { featureFlagService } from '@/services/systemSettingService'
import type { NormalizedApiError } from '@/services/api'
import type { FeatureFlag } from '@/types/systemSetting'
import { ROUTES } from '@/constants/routes'

export function FeatureFlagsPage() {
  const canUpdate = usePermission('feature_flags.update')
  const list = usePaginatedList<FeatureFlag>({ fetcher: featureFlagService.index })
  const [togglingId, setTogglingId] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)

  const handleToggle = async (flag: FeatureFlag) => {
    setError(null)
    setTogglingId(flag.id)

    try {
      await featureFlagService.update(flag.id, !flag.is_enabled)
      list.refetch()
    } catch (toggleError) {
      setError((toggleError as NormalizedApiError).message ?? 'Gagal mengubah feature flag.')
    } finally {
      setTogglingId(null)
    }
  }

  const columns: DataTableColumn<FeatureFlag>[] = [
    {
      header: 'Feature Flag',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.name}</p>
          <p className="text-xs text-ink-tertiary">{row.key}</p>
        </div>
      ),
    },
    { header: 'Deskripsi', cell: (row) => row.description ?? '-' },
    {
      header: 'Aktif',
      cell: (row) => (
        <Checkbox
          checked={row.is_enabled}
          disabled={!canUpdate || togglingId === row.id}
          onChange={() => handleToggle(row)}
        />
      ),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Feature Flag"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Feature Flag' }]}
      />

      {error && (
        <Alert variant="danger" onDismiss={() => setError(null)}>
          {error}
        </Alert>
      )}

      <Card noPadding>
        <div className="flex items-center gap-3 border-b border-border p-3">
          <Input
            placeholder="Cari key atau nama..."
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
            emptyMessage={list.isLoading ? 'Memuat...' : 'Tidak ada feature flag.'}
          />
        )}
      </Card>
    </div>
  )
}
