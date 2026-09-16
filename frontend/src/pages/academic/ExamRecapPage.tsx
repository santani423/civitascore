import { useCallback, useMemo, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Download } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { examService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { ExamParticipant, ExamParticipantStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

const STATUS_LABEL: Record<ExamParticipantStatus, string> = {
  not_started: 'Belum Mengerjakan',
  in_progress: 'Sedang Mengerjakan',
  completed: 'Selesai',
}

const STATUS_BADGE: Record<ExamParticipantStatus, BadgeVariant> = {
  not_started: 'neutral',
  in_progress: 'warning',
  completed: 'success',
}

/**
 * Rekap Nilai (spec §7) — ringkasan ujian + tabel nilai seluruh peserta,
 * memakai endpoint `/exams/{exam}/recap` yang me-reuse query peserta yang
 * sama dengan tabel "Mahasiswa yang Dapat Mengikuti Ujian" di
 * ExamDetailPage (lihat ExamService::participantsQuery), jadi tidak ada
 * dua sumber kebenaran untuk siapa peserta ujian ini.
 */
export function ExamRecapPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const fetchRecap = useCallback(() => examService.recap(id ?? ''), [id])
  const { data: recap, isLoading, error } = useFetch(fetchRecap)

  const [search, setSearch] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [exportError, setExportError] = useState<string | null>(null)
  const [isExporting, setIsExporting] = useState<'csv' | 'pdf' | null>(null)

  const filteredParticipants = useMemo(() => {
    if (!recap) return []

    return recap.participants.filter((p) => {
      const matchesSearch =
        search.trim() === '' ||
        p.student_nim?.toLowerCase().includes(search.trim().toLowerCase()) ||
        p.student_name?.toLowerCase().includes(search.trim().toLowerCase())
      const matchesStatus = statusFilter === '' || p.status === statusFilter

      return matchesSearch && matchesStatus
    })
  }, [recap, search, statusFilter])

  const handleExport = async (format: 'csv' | 'pdf') => {
    if (!id || !recap) return
    setIsExporting(format)
    setExportError(null)

    try {
      const filename = `rekap-nilai-${recap.summary.exam_title.replace(/\s+/g, '-').toLowerCase()}.${format}`
      await examService.downloadRecapExport(id, format, filename)
    } catch (err) {
      setExportError((err as NormalizedApiError).message ?? 'Gagal mengunduh rekap nilai.')
    } finally {
      setIsExporting(null)
    }
  }

  const columns: DataTableColumn<ExamParticipant>[] = [
    { header: 'NIM', cell: (row) => row.student_nim ?? '-' },
    { header: 'Nama', cell: (row) => row.student_name ?? '-' },
    { header: 'Raw Score', cell: (row) => row.raw_score ?? '-' },
    { header: 'Penalty', cell: (row) => (row.penalty_score && row.penalty_score !== '0.00' ? `-${row.penalty_score}` : '-') },
    { header: 'Final Score', cell: (row) => row.score ?? '-' },
    { header: 'Grade', cell: (row) => row.grade ?? '-' },
    { header: 'Weighted Score', cell: (row) => row.weighted_score ?? '-' },
    {
      header: 'Status',
      cell: (row) => <Badge variant={STATUS_BADGE[row.status]}>{STATUS_LABEL[row.status]}</Badge>,
    },
  ]

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Rekap Nilai"
        description={recap ? `${recap.summary.exam_title} — ${recap.summary.course_name ?? '-'}` : undefined}
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Ujian', path: ROUTES.akademik.ujian },
          { label: 'Detail', path: ROUTES.akademik.ujianDetail.replace(':id', id ?? '') },
          { label: 'Rekap Nilai' },
        ]}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" onClick={() => navigate(ROUTES.akademik.ujianDetail.replace(':id', id ?? ''))}>
              Kembali
            </Button>
            <Button
              variant="outline"
              leftIcon={<Download className="size-4" />}
              isLoading={isExporting === 'csv'}
              onClick={() => handleExport('csv')}
            >
              Excel/CSV
            </Button>
            <Button
              leftIcon={<Download className="size-4" />}
              isLoading={isExporting === 'pdf'}
              onClick={() => handleExport('pdf')}
            >
              PDF
            </Button>
          </div>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}
      {exportError && (
        <Alert variant="danger" onDismiss={() => setExportError(null)}>
          {exportError}
        </Alert>
      )}

      {recap && (
        <>
          <Card title="Ringkasan Ujian">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
              <div className="rounded-lg border border-border-strong p-3 text-center">
                <p className="text-lg font-semibold text-ink-primary">{recap.summary.total_participants}</p>
                <p className="text-xs text-ink-tertiary">Total Peserta</p>
              </div>
              <div className="rounded-lg border border-success/40 bg-success/5 p-3 text-center">
                <p className="text-lg font-semibold text-success">{recap.summary.completed}</p>
                <p className="text-xs text-ink-tertiary">Selesai</p>
              </div>
              <div className="rounded-lg border border-warning/40 bg-warning/5 p-3 text-center">
                <p className="text-lg font-semibold text-warning">{recap.summary.in_progress}</p>
                <p className="text-xs text-ink-tertiary">Sedang Mengerjakan</p>
              </div>
              <div className="rounded-lg border border-border-strong p-3 text-center">
                <p className="text-lg font-semibold text-ink-primary">{recap.summary.not_started}</p>
                <p className="text-xs text-ink-tertiary">Belum Mengerjakan</p>
              </div>
              <div className="rounded-lg border border-primary/40 bg-primary/5 p-3 text-center">
                <p className="text-lg font-semibold text-primary">{recap.summary.average_score ?? '-'}</p>
                <p className="text-xs text-ink-tertiary">Rata-rata</p>
              </div>
              <div className="rounded-lg border border-border-strong p-3 text-center">
                <p className="text-lg font-semibold text-ink-primary">
                  {recap.summary.highest_score ?? '-'} / {recap.summary.lowest_score ?? '-'}
                </p>
                <p className="text-xs text-ink-tertiary">Tertinggi / Terendah</p>
              </div>
            </div>
          </Card>

          <Card noPadding>
            <div className="flex flex-wrap items-end gap-3 border-b border-border p-3">
              <Input
                label="Cari"
                placeholder="NIM atau nama..."
                value={search}
                onChange={(event) => setSearch(event.target.value)}
              />
              <Select
                label="Status"
                value={statusFilter}
                onChange={(event) => setStatusFilter(event.target.value)}
                options={Object.entries(STATUS_LABEL).map(([value, label]) => ({ value, label }))}
                placeholder="Semua status"
              />
            </div>

            <DataTable
              columns={columns}
              data={filteredParticipants}
              rowKey={(row) => row.krs_item_id}
              emptyMessage="Tidak ada peserta yang cocok dengan filter."
            />
          </Card>
        </>
      )}
    </div>
  )
}
