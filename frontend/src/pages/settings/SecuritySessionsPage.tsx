import { useCallback, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { LogOut, Monitor } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { SkeletonRow } from '@/components/ui/Skeleton'
import { useFetch } from '@/hooks/useFetch'
import { authService } from '@/services/authService'
import type { NormalizedApiError } from '@/services/api'
import type { UserDevice, UserSession } from '@/types/auth'
import { useAuthStore } from '@/stores/authStore'
import { ROUTES } from '@/constants/routes'
import { formatRelativeTime } from '@/utils/formatters'

export function SecuritySessionsPage() {
  const navigate = useNavigate()
  const clearSession = useAuthStore((state) => state.clearSession)
  const [actionError, setActionError] = useState<string | null>(null)
  const [revokingId, setRevokingId] = useState<string | null>(null)
  const [isLoggingOutAll, setIsLoggingOutAll] = useState(false)

  const getSessions = useCallback(() => authService.getSessions(), [])
  const getDevices = useCallback(() => authService.getDevices(), [])

  const sessions = useFetch(getSessions)
  const devices = useFetch(getDevices)

  const handleRevoke = async (sessionId: string) => {
    setActionError(null)
    setRevokingId(sessionId)

    try {
      await authService.revokeSession(sessionId)
      sessions.refetch()
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal mencabut sesi.')
    } finally {
      setRevokingId(null)
    }
  }

  const handleLogoutAll = async () => {
    setActionError(null)
    setIsLoggingOutAll(true)

    try {
      await authService.logoutAllDevices()
      clearSession()
      navigate(ROUTES.login, { replace: true })
    } catch (error) {
      setActionError((error as NormalizedApiError).message ?? 'Gagal keluar dari semua perangkat.')
      setIsLoggingOutAll(false)
    }
  }

  const sessionColumns: DataTableColumn<UserSession>[] = [
    {
      header: 'Perangkat',
      cell: (row) => (
        <div>
          <p className="font-medium text-ink-primary">{row.device?.device_name ?? 'Tidak diketahui'}</p>
          <p className="text-xs text-ink-tertiary">{row.device?.platform ?? row.user_agent ?? '-'}</p>
        </div>
      ),
    },
    { header: 'IP', cell: (row) => row.ip_address ?? '-' },
    {
      header: 'Aktivitas Terakhir',
      cell: (row) => (row.last_activity_at ? formatRelativeTime(row.last_activity_at) : '-'),
    },
    {
      header: 'Status',
      cell: (row) => (
        <Badge variant={row.is_active ? 'success' : 'neutral'}>{row.is_active ? 'Aktif' : 'Dicabut'}</Badge>
      ),
    },
    {
      header: '',
      cell: (row) =>
        row.is_active ? (
          <Button
            variant="outline"
            size="sm"
            isLoading={revokingId === row.id}
            onClick={() => handleRevoke(row.id)}
          >
            Cabut
          </Button>
        ) : null,
    },
  ]

  const deviceColumns: DataTableColumn<UserDevice>[] = [
    {
      header: 'Perangkat',
      cell: (row) => (
        <div className="flex items-center gap-2">
          <Monitor className="size-4 text-ink-tertiary" />
          <span className="font-medium text-ink-primary">{row.device_name ?? 'Tidak diketahui'}</span>
        </div>
      ),
    },
    { header: 'Tipe', cell: (row) => <Badge variant="neutral">{row.device_type}</Badge> },
    { header: 'Platform', cell: (row) => row.platform ?? '-' },
    {
      header: 'Dipercaya',
      cell: (row) => <Badge variant={row.is_trusted ? 'success' : 'neutral'}>{row.is_trusted ? 'Ya' : 'Tidak'}</Badge>,
    },
    {
      header: 'Terakhir Dipakai',
      cell: (row) => (row.last_used_at ? formatRelativeTime(row.last_used_at) : '-'),
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Keamanan"
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengaturan' }, { label: 'Keamanan' }]}
        actions={
          <Button variant="danger" leftIcon={<LogOut className="size-4" />} isLoading={isLoggingOutAll} onClick={handleLogoutAll}>
            Keluar dari Semua Perangkat
          </Button>
        }
      />

      {actionError && (
        <Alert variant="danger" onDismiss={() => setActionError(null)}>
          {actionError}
        </Alert>
      )}

      <Card title="Sesi Aktif" description="Perangkat yang sedang masuk ke akun Anda" noPadding>
        {sessions.isLoading ? (
          <div className="flex flex-col gap-3 p-4">
            <SkeletonRow />
            <SkeletonRow />
          </div>
        ) : sessions.error ? (
          <Alert variant="danger" className="m-4">
            {sessions.error}
          </Alert>
        ) : (
          <DataTable
            columns={sessionColumns}
            data={sessions.data ?? []}
            rowKey={(row) => row.id}
            emptyMessage="Tidak ada sesi aktif."
          />
        )}
      </Card>

      <Card title="Perangkat Terdaftar" description="Perangkat yang pernah dipakai untuk masuk" noPadding>
        {devices.isLoading ? (
          <div className="flex flex-col gap-3 p-4">
            <SkeletonRow />
            <SkeletonRow />
          </div>
        ) : devices.error ? (
          <Alert variant="danger" className="m-4">
            {devices.error}
          </Alert>
        ) : (
          <DataTable
            columns={deviceColumns}
            data={devices.data ?? []}
            rowKey={(row) => row.id}
            emptyMessage="Belum ada perangkat terdaftar."
          />
        )}
      </Card>
    </div>
  )
}
