import { useCallback, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Bell, CheckCheck } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { ListPagination } from '@/components/ui/ListPagination'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { notificationInboxService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { InboxNotification } from '@/types/studentPortal'
import { formatRelativeTime } from '@/utils/formatters'
import { cn } from '@/utils/cn'

/** Pusat notifikasi in-app — KRS, nilai, tugas, pengajuan, dsb. */
export function PortalNotificationsPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [unreadOnly, setUnreadOnly] = useState(false)
  const fetchNotifications = useCallback(() => notificationInboxService.list({ page, unread: unreadOnly }), [page, unreadOnly])
  const { data, setData, isLoading, error, refetch } = useFetch(fetchNotifications)
  const [markingAll, setMarkingAll] = useState(false)

  const open = async (notification: InboxNotification) => {
    if (!notification.read_at) {
      try {
        const result = await notificationInboxService.markRead(notification.id)
        setData((current) =>
          current
            ? {
                ...current,
                data: current.data.map((item) => (item.id === notification.id ? { ...item, read_at: result.data.read_at } : item)),
                meta: { ...current.meta, unread_count: result.data.unread_count },
              }
            : current,
        )
      } catch {
        // Tetap lanjut membuka tautan.
      }
    }

    if (notification.link) navigate(notification.link)
  }

  const markAll = async () => {
    setMarkingAll(true)
    try {
      await notificationInboxService.markAllRead()
      refetch()
    } finally {
      setMarkingAll(false)
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Notifikasi"
        description={data?.meta.unread_count ? `${data.meta.unread_count} notifikasi belum dibaca.` : 'Semua notifikasi sudah dibaca.'}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Notifikasi' }]}
        actions={
          <div className="flex gap-2">
            <Button size="sm" variant={unreadOnly ? 'primary' : 'outline'} onClick={() => { setUnreadOnly((value) => !value); setPage(1) }}>
              Belum dibaca
            </Button>
            <Button size="sm" variant="outline" leftIcon={<CheckCheck className="size-3.5" />} isLoading={markingAll} disabled={!data?.meta.unread_count} onClick={markAll}>
              Tandai semua dibaca
            </Button>
          </div>
        }
      />

      {isLoading && !data ? (
        <PortalLoading cards={0} rows={6} />
      ) : error ? (
        <PortalError message={error} onRetry={refetch} />
      ) : !data || data.data.length === 0 ? (
        <PortalEmpty icon={Bell} title={unreadOnly ? 'Tidak ada notifikasi yang belum dibaca.' : 'Belum ada notifikasi.'} />
      ) : (
        <Card noPadding>
          <ul className="flex flex-col divide-y divide-border">
            {data.data.map((notification) => (
              <li key={notification.id}>
                <button type="button" onClick={() => open(notification)} className="flex w-full items-start gap-3 p-4 text-left hover:bg-surface-hover">
                  <span className={cn('mt-1.5 size-2 shrink-0 rounded-full', notification.read_at ? 'bg-transparent' : 'bg-primary')} />
                  <span className="min-w-0 flex-1">
                    <span className={cn('block text-sm', notification.read_at ? 'text-ink-secondary' : 'font-medium text-ink-primary')}>
                      {notification.message ?? 'Notifikasi'}
                    </span>
                    {notification.created_at && <span className="mt-0.5 block text-xs text-ink-tertiary">{formatRelativeTime(notification.created_at)}</span>}
                  </span>
                </button>
              </li>
            ))}
          </ul>
          <div className="border-t border-border px-4 py-3">
            <ListPagination meta={data.meta} page={page} onPageChange={setPage} itemLabel="notifikasi" />
          </div>
        </Card>
      )}
    </div>
  )
}
