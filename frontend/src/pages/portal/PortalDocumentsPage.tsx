import { useCallback, useState } from 'react'
import { Download, FileText } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import type { StudentDocument } from '@/types/studentPortal'

const TYPE_LABEL: Record<StudentDocument['type'], string> = {
  krs: 'KRS',
  khs: 'KHS',
  transcript: 'Transkrip',
  letter: 'Surat',
}

export function PortalDocumentsPage() {
  const fetchDocuments = useCallback(() => studentPortalService.documents(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchDocuments)
  const [downloading, setDownloading] = useState<string | null>(null)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  const download = async (document: StudentDocument) => {
    setDownloading(document.url)
    setDownloadError(null)

    try {
      await studentPortalService.downloadDocument(document)
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message)
    } finally {
      setDownloading(null)
    }
  }

  if (isLoading && !data) return <PortalLoading cards={0} rows={5} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Dokumen Akademik"
        description="Unduh KRS, KHS, transkrip, dan surat keterangan yang sudah disetujui. Hanya dokumen milik Anda yang tersedia."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Dokumen' }]}
      />

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)}>
          {downloadError}
        </Alert>
      )}

      {data.length === 0 ? (
        <PortalEmpty icon={FileText} title="Belum ada dokumen yang tersedia." description="Dokumen tersedia setelah Anda memiliki KRS atau nilai." />
      ) : (
        <Card noPadding>
          <ul className="flex flex-col divide-y divide-border">
            {data.map((document) => (
              <li key={document.url} className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-start gap-3">
                  <FileText className="mt-0.5 size-5 shrink-0 text-primary" />
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <p className="font-medium text-ink-primary">{document.title}</p>
                      <Badge variant="neutral">{TYPE_LABEL[document.type]}</Badge>
                    </div>
                    <p className="text-xs text-ink-secondary">{document.description}</p>
                  </div>
                </div>
                <Button
                  size="sm"
                  variant="outline"
                  leftIcon={<Download className="size-3.5" />}
                  isLoading={downloading === document.url}
                  onClick={() => download(document)}
                  className="self-start sm:self-center"
                >
                  Unduh PDF
                </Button>
              </li>
            ))}
          </ul>
        </Card>
      )}
    </div>
  )
}
