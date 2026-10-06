import { useCallback, useMemo, useState } from 'react'
import { Download, ScrollText } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Badge } from '@/components/ui/Badge'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { SummaryTiles } from '@/components/portal/GradeTable'
import { GradeBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import type { TranscriptRow } from '@/types/studentPortal'
import { formatGpa } from '@/utils/portalFormat'

export function PortalTranscriptPage() {
  const fetchTranscript = useCallback(() => studentPortalService.transcript(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchTranscript)
  const [downloading, setDownloading] = useState(false)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  // Dikelompokkan per semester saat ditempuh (urutan kronologis dari backend).
  const groups = useMemo(() => {
    const map = new Map<string, TranscriptRow[]>()
    for (const row of data?.rows ?? []) {
      map.set(row.term_label, [...(map.get(row.term_label) ?? []), row])
    }

    return [...map.entries()]
  }, [data])

  const handleDownload = async () => {
    setDownloading(true)
    setDownloadError(null)

    try {
      await downloadBlob('/student/documents/transcript', `Transkrip-${data?.student.nim ?? 'mahasiswa'}.pdf`)
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message)
    } finally {
      setDownloading(false)
    }
  }

  if (isLoading && !data) return <PortalLoading cards={4} rows={8} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Transkrip Nilai"
        description="Nilai terbaik setiap mata kuliah yang pernah ditempuh. Mata kuliah yang diulang dihitung sekali dengan nilai terbaiknya."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Transkrip' }]}
        actions={
          data.rows.length > 0 ? (
            <Button variant="outline" size="sm" leftIcon={<Download className="size-3.5" />} isLoading={downloading} onClick={handleDownload}>
              Unduh PDF
            </Button>
          ) : undefined
        }
      />

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)}>
          {downloadError}
        </Alert>
      )}

      <SummaryTiles
        items={[
          { label: 'IPK', value: formatGpa(data.summary.ipk) },
          { label: 'Total SKS', value: String(data.summary.total_credits) },
          { label: 'SKS Lulus', value: String(data.summary.passed_credits) },
          { label: 'Mata Kuliah', value: String(data.summary.course_count) },
        ]}
      />

      {data.rows.length === 0 ? (
        <PortalEmpty icon={ScrollText} title="Transkrip belum tersedia." description="Transkrip tersedia setelah ada nilai mata kuliah." />
      ) : (
        groups.map(([termLabel, rows]) => (
          <Card key={termLabel} title={termLabel} description={`${rows.reduce((sum, row) => sum + row.credits, 0)} SKS`} noPadding>
            <ul className="flex flex-col divide-y divide-border px-4">
              {rows.map((row) => (
                <li key={row.course_id} className="flex items-center justify-between gap-3 py-3">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-ink-primary">{row.course_name}</p>
                    <p className="text-xs text-ink-secondary">
                      {row.course_code} · {row.credits} SKS · Bobot {formatGpa(row.weight)} · Mutu {formatGpa(row.quality_points)}
                    </p>
                  </div>
                  <div className="flex shrink-0 items-center gap-2">
                    {row.attempts > 1 && <Badge variant="neutral">Diulang {row.attempts}x</Badge>}
                    <GradeBadge grade={row.letter_grade} />
                  </div>
                </li>
              ))}
            </ul>
          </Card>
        ))
      )}
    </div>
  )
}
