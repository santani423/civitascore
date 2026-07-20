import { useCallback, useState } from 'react'
import { Download } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Select } from '@/components/ui/Select'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { reportService } from '@/services/reportService'
import type { ReportType } from '@/types/report'
import { ROUTES } from '@/constants/routes'
import { formatNumber, humanizeSlug } from '@/utils/formatters'

const DEFAULT_TYPE: ReportType = 'mahasiswa'

export function ReportsPage() {
  const [selectedType, setSelectedType] = useState<ReportType>(DEFAULT_TYPE)
  const [isDownloading, setIsDownloading] = useState(false)

  const getReportTypes = useCallback(() => reportService.index(), [])
  const { data: reportTypes } = useFetch(getReportTypes)

  const getReport = useCallback(() => reportService.show(selectedType), [selectedType])
  const { data: report, isLoading, error } = useFetch(getReport)

  const handleDownload = async () => {
    setIsDownloading(true)
    try {
      await reportService.downloadCsv(selectedType)
    } finally {
      setIsDownloading(false)
    }
  }

  const columns: DataTableColumn<{ __rowKey: string } & Record<string, string | number | null>>[] =
    report?.columns.map((column) => ({
      header: column.label,
      cell: (row) => row[column.key] ?? '-',
    })) ?? []

  const rows = (report?.rows ?? []).map((row, index) => ({ ...row, __rowKey: String(index) }))

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Laporan"
        description="Laporan dan ekspor data untuk universitas Anda."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Laporan' }]}
      />

      <Card>
        <div className="flex flex-wrap items-end justify-between gap-3">
          <Select
            label="Jenis Laporan"
            value={selectedType}
            onChange={(event) => setSelectedType(event.target.value as ReportType)}
            options={(reportTypes ?? []).map((option) => ({ value: option.type, label: option.label }))}
          />
          <Button
            variant="outline"
            leftIcon={<Download className="size-4" />}
            onClick={handleDownload}
            isLoading={isDownloading}
            disabled={!report}
          >
            Unduh CSV
          </Button>
        </div>

        {report && (
          <div className="mt-5 grid grid-cols-2 gap-4 border-t border-border pt-4 text-sm sm:grid-cols-3 lg:grid-cols-4">
            {Object.entries(report.summary).map(([key, value]) => (
              <div key={key}>
                <p className="text-ink-tertiary">{humanizeSlug(key)}</p>
                <p className="font-medium text-ink-primary">
                  {typeof value === 'number' ? formatNumber(value) : (value ?? '-')}
                </p>
              </div>
            ))}
          </div>
        )}
      </Card>

      {report?.truncated && (
        <Alert variant="warning">
          Menampilkan {formatNumber(report.rows.length)} dari {formatNumber(report.total_rows)} baris. Gunakan
          &quot;Unduh CSV&quot; untuk data lengkap.
        </Alert>
      )}

      <Card noPadding>
        {error ? (
          <Alert variant="danger" className="m-4">
            {error}
          </Alert>
        ) : (
          <DataTable
            columns={columns}
            data={rows}
            rowKey={(row) => row.__rowKey}
            emptyMessage={isLoading ? 'Memuat...' : 'Tidak ada data untuk laporan ini.'}
          />
        )}
      </Card>
    </div>
  )
}
