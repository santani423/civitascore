import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { announcementService } from '@/services/announcementService'
import type { AnnouncementTargetScope } from '@/types/announcement'
import { ROUTES } from '@/constants/routes'

const TARGET_SCOPE_LABEL: Record<AnnouncementTargetScope, string> = {
  universitas: 'Universitas',
  fakultas: 'Fakultas',
  program_studi: 'Program Studi',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function AnnouncementDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getAnnouncement = useCallback(() => announcementService.show(id ?? ''), [id])
  const { data: announcement, isLoading, error } = useFetch(getAnnouncement)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Pengumuman"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Pengumuman', path: ROUTES.pengumuman },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.pengumuman)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {announcement && (
        <Card
          title={announcement.title}
          description={`${TARGET_SCOPE_LABEL[announcement.target_scope]} · ${announcement.published_at.slice(0, 10)}`}
        >
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <DetailField label="Dibuat Oleh" value={announcement.creator_name} />
            <DetailField label="Ditandai Penting" value={announcement.is_pinned ? 'Ya' : 'Tidak'} />
          </div>

          <div className="mt-5 border-t border-border pt-4">
            <p className="whitespace-pre-line text-sm text-ink-primary">{announcement.body}</p>
          </div>
        </Card>
      )}
    </div>
  )
}
