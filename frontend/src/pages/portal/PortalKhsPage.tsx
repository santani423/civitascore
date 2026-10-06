import { useCallback, useState } from 'react'
import { Download, FileText } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { GradeTable, SummaryTiles } from '@/components/portal/GradeTable'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import { formatGpa } from '@/utils/portalFormat'

export function PortalKhsPage() {
  const [termId, setTermId] = useState('')
  const fetchKhs = useCallback(() => studentPortalService.khs(termId || undefined), [termId])
  const { data, isLoading, error, refetch } = useFetch(fetchKhs)
  const [downloading, setDownloading] = useState(false)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  const handleDownload = async () => {
    if (!data?.term) return
    setDownloading(true)
    setDownloadError(null)

    try {
      await downloadBlob(`/student/documents/khs/${data.term.id}`, `KHS-${data.term.label.replace(/\W+/g, '-')}.pdf`)
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message)
    } finally {
      setDownloading(false)
    }
  }

  if (isLoading && !data) return <PortalLoading cards={4} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Kartu Hasil Studi (KHS)"
        description={data.term ? `${data.semester_number ? `Semester ${data.semester_number} — ` : ''}${data.term.label}` : undefined}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'KHS' }]}
        actions={
          data.term ? (
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
              {data.terms.length > 1 && (
                <div className="w-full sm:w-56">
                  <Select
                    aria-label="Pilih semester"
                    value={data.term.id}
                    onChange={(event) => setTermId(event.target.value)}
                    options={[...data.terms].reverse().map((term) => ({ value: term.id, label: term.label }))}
                  />
                </div>
              )}
              <Button variant="outline" size="sm" leftIcon={<Download className="size-3.5" />} isLoading={downloading} onClick={handleDownload}>
                Unduh PDF
              </Button>
            </div>
          ) : undefined
        }
      />

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)}>
          {downloadError}
        </Alert>
      )}

      {!data.term || !data.summary ? (
        <PortalEmpty icon={FileText} title="KHS belum tersedia." description="KHS tersedia setelah Anda mengikuti perkuliahan dan nilai diinput dosen." />
      ) : (
        <>
          <SummaryTiles
            items={[
              { label: 'SKS Semester (dinilai)', value: String(data.summary.term_credits) },
              { label: 'IPS', value: formatGpa(data.summary.ips) },
              { label: 'IPK (s.d. semester ini)', value: formatGpa(data.summary.ipk) },
              { label: 'Total SKS Kumulatif', value: String(data.summary.cumulative_credits) },
            ]}
          />

          {!data.summary.all_graded && (
            <Alert variant="info">Sebagian mata kuliah semester ini belum dinilai — IPS dihitung dari mata kuliah yang sudah dinilai.</Alert>
          )}

          <Card title="Rincian Nilai" noPadding>
            <div className="px-4 py-2 sm:px-2">
              <GradeTable rows={data.rows} />
            </div>
          </Card>
        </>
      )}
    </div>
  )
}
