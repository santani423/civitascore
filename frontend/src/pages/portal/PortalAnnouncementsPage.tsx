import { useCallback, useState } from 'react'
import { Megaphone, Paperclip, Pin, Search } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Modal } from '@/components/ui/Modal'
import { ListPagination } from '@/components/ui/ListPagination'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import type { AnnouncementFilter, PortalAnnouncement } from '@/types/studentPortal'
import { formatDate } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

const FILTERS: { value: AnnouncementFilter; label: string }[] = [
  { value: 'all', label: 'Semua' },
  { value: 'unread', label: 'Belum dibaca' },
  { value: 'study_program', label: 'Program Studi' },
  { value: 'faculty', label: 'Fakultas' },
  { value: 'admission_year', label: 'Angkatan' },
  { value: 'semester', label: 'Semester' },
  { value: 'students', label: 'Khusus Mahasiswa' },
]

function targetLabel(announcement: PortalAnnouncement): string {
  const parts = [
    announcement.target_scope === 'program_studi' ? 'Program studi' : announcement.target_scope === 'fakultas' ? 'Fakultas' : 'Universitas',
  ]
  if (announcement.target_admission_year) parts.push(`Angkatan ${announcement.target_admission_year}`)
  if (announcement.target_semester) parts.push(`Semester ${announcement.target_semester}`)

  return parts.join(' · ')
}

export function PortalAnnouncementsPage() {
  const [filter, setFilter] = useState<AnnouncementFilter>('all')
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [page, setPage] = useState(1)

  const fetchAnnouncements = useCallback(
    () => studentPortalService.announcements({ filter, search: query || undefined, page, per_page: 10 }),
    [filter, query, page],
  )
  const { data, setData, isLoading, error, refetch } = useFetch(fetchAnnouncements)
  const [selected, setSelected] = useState<PortalAnnouncement | null>(null)
  const [attachmentError, setAttachmentError] = useState<string | null>(null)

  const open = async (announcement: PortalAnnouncement) => {
    setSelected(announcement)
    setAttachmentError(null)

    if (!announcement.is_read) {
      try {
        const result = await studentPortalService.markAnnouncementRead(announcement.id)
        setData((current) =>
          current
            ? {
                ...current,
                data: current.data.map((item) => (item.id === announcement.id ? { ...item, is_read: true } : item)),
                meta: { ...current.meta, unread_count: result.data.unread_count },
              }
            : current,
        )
      } catch {
        // Gagal menandai dibaca tidak menghalangi membaca pengumuman.
      }
    }
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengumuman"
        description={data?.meta.unread_count ? `${data.meta.unread_count} pengumuman belum dibaca.` : 'Pengumuman yang ditujukan untuk Anda.'}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengumuman' }]}
      />

      <form
        className="max-w-sm"
        onSubmit={(event) => {
          event.preventDefault()
          setPage(1)
          setQuery(search.trim())
        }}
      >
        <Input placeholder="Cari pengumuman" value={search} onChange={(event) => setSearch(event.target.value)} leftIcon={<Search className="size-4" />} />
      </form>

      <div className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
        {FILTERS.map((option) => (
          <button
            key={option.value}
            type="button"
            onClick={() => {
              setFilter(option.value)
              setPage(1)
            }}
            className={cn(
              'shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
              filter === option.value ? 'border-primary bg-primary text-white' : 'border-border text-ink-secondary hover:bg-surface-hover',
            )}
          >
            {option.label}
          </button>
        ))}
      </div>

      {isLoading && !data ? (
        <PortalLoading cards={0} rows={5} />
      ) : error ? (
        <PortalError message={error} onRetry={refetch} />
      ) : !data || data.data.length === 0 ? (
        <PortalEmpty icon={Megaphone} title="Belum ada pengumuman." />
      ) : (
        <Card noPadding>
          <ul className="flex flex-col divide-y divide-border">
            {data.data.map((announcement) => (
              <li key={announcement.id}>
                <button type="button" onClick={() => open(announcement)} className="flex w-full items-start gap-3 p-4 text-left hover:bg-surface-hover">
                  <span className={cn('mt-1.5 size-2 shrink-0 rounded-full', announcement.is_read ? 'bg-transparent' : 'bg-primary')} aria-label={announcement.is_read ? undefined : 'Belum dibaca'} />
                  <span className="min-w-0 flex-1">
                    <span className="flex flex-wrap items-center gap-2">
                      <span className={cn('text-sm', announcement.is_read ? 'text-ink-secondary' : 'font-semibold text-ink-primary')}>{announcement.title}</span>
                      {announcement.is_pinned && (
                        <Badge variant="warning">
                          <Pin className="size-3" /> Disematkan
                        </Badge>
                      )}
                      {announcement.attachment && <Paperclip className="size-3.5 text-ink-tertiary" />}
                    </span>
                    <span className="mt-0.5 line-clamp-2 block text-xs text-ink-secondary">{announcement.body}</span>
                    <span className="mt-1 block text-xs text-ink-tertiary">
                      {formatDate(announcement.published_at)} · {targetLabel(announcement)}
                    </span>
                  </span>
                </button>
              </li>
            ))}
          </ul>
          <div className="border-t border-border px-4 py-3">
            <ListPagination meta={data.meta} page={page} onPageChange={setPage} itemLabel="pengumuman" />
          </div>
        </Card>
      )}

      <Modal open={selected !== null} onClose={() => setSelected(null)} title={selected?.title} className="max-w-2xl">
        {selected && (
          <div className="flex flex-col gap-3 text-sm">
            <p className="text-xs text-ink-tertiary">
              {formatDate(selected.published_at, 'long')} · {targetLabel(selected)}
              {selected.creator_name ? ` · ${selected.creator_name}` : ''}
            </p>
            <p className="whitespace-pre-line text-ink-primary">{selected.body}</p>
            {selected.attachment && (
              <Button
                size="sm"
                variant="outline"
                leftIcon={<Paperclip className="size-3.5" />}
                className="self-start"
                onClick={() =>
                  downloadBlob(`/file-uploads/${selected.attachment!.id}/download`, selected.attachment!.name).catch((err: NormalizedApiError) =>
                    setAttachmentError(err.message),
                  )
                }
              >
                {selected.attachment.name}
              </Button>
            )}
            {attachmentError && <p className="text-xs text-danger">{attachmentError}</p>}
          </div>
        )}
      </Modal>
    </div>
  )
}
