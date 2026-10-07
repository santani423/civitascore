import { useCallback } from 'react'
import { Flag, GraduationCap, RefreshCcw, School } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { InfoList, PortalError, PortalLoading, ProgressBar } from '@/components/portal/PortalState'
import { StudentStatusBadge } from '@/components/portal/badges'
import { SummaryTiles } from '@/components/portal/GradeTable'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { AcademicHistoryEvent } from '@/types/studentPortal'
import { formatDate, formatGpa } from '@/utils/portalFormat'

const EVENT_ICON: Record<AcademicHistoryEvent['type'], typeof Flag> = {
  admission: School,
  term: GraduationCap,
  status: RefreshCcw,
  graduation: Flag,
}

/** "Akademik Saya" — ringkasan akademik, dosen wali, status, dan riwayat akademik. */
export function PortalAcademicPage() {
  const fetchSummary = useCallback(() => studentPortalService.academicSummary(), [])
  const fetchHistory = useCallback(() => studentPortalService.academicHistory(), [])
  const { data: summary, isLoading, error, refetch } = useFetch(fetchSummary)
  const { data: history } = useFetch(fetchHistory)

  if (isLoading && !summary) return <PortalLoading cards={4} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!summary) return null

  const curriculumTotal = summary.curriculum?.total_credits ?? null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Akademik Saya"
        description="Ringkasan studi Anda. Status akademik hanya dapat diubah Bagian Akademik (mis. lewat pengajuan cuti/aktif kembali)."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Akademik Saya' }]}
      />

      <SummaryTiles
        items={[
          { label: 'IPK', value: formatGpa(summary.ipk) },
          { label: 'Total SKS', value: String(summary.total_credits) },
          { label: 'SKS Semester Ini', value: String(summary.current_term_credits) },
          { label: 'Semester', value: summary.semester ? String(summary.semester) : '-' },
        ]}
      />

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <Card title="Data Akademik" className="lg:col-span-2">
          <InfoList
            items={[
              { label: 'NIM', value: summary.nim },
              { label: 'Nama', value: summary.name },
              { label: 'Program studi', value: `${summary.study_program.name} (${summary.study_program.degree_level})` },
              { label: 'Fakultas', value: summary.faculty?.name ?? '-' },
              { label: 'Angkatan', value: summary.admission_year },
              { label: 'Semester aktif', value: summary.current_term?.label ?? '-' },
              { label: 'Status', value: <StudentStatusBadge status={summary.status} /> },
              { label: 'Kurikulum', value: summary.curriculum ? `${summary.curriculum.name} (${summary.curriculum.academic_year})` : '-' },
              { label: 'IPS terakhir', value: summary.last_ips ? `${formatGpa(summary.last_ips.ips)} (${summary.last_ips.label})` : '-' },
              { label: 'Batas SKS semester ini', value: summary.max_credits ?? '-' },
            ]}
          />
        </Card>

        <div className="flex flex-col gap-5">
          <Card title="Dosen Wali">
            {summary.academic_advisor ? (
              <InfoList
                columns={1}
                items={[
                  { label: 'Nama', value: summary.academic_advisor.name },
                  { label: 'Email', value: summary.academic_advisor.email ?? '-' },
                  { label: 'Program studi', value: summary.study_program.name },
                ]}
              />
            ) : (
              <p className="text-sm text-ink-secondary">Dosen wali belum ditetapkan. Hubungi Bagian Akademik.</p>
            )}
          </Card>

          <Card title="Progres Studi">
            {curriculumTotal ? (
              <>
                <p className="text-sm text-ink-secondary">
                  <span className="text-xl font-semibold text-ink-primary">{summary.passed_credits}</span> / {curriculumTotal} SKS lulus
                </p>
                <div className="mt-2">
                  <ProgressBar value={summary.passed_credits} max={curriculumTotal} />
                </div>
                <p className="mt-1.5 text-xs text-ink-tertiary">Sisa {summary.remaining_credits ?? '-'} SKS sesuai kurikulum.</p>
              </>
            ) : (
              <p className="text-sm text-ink-secondary">Kurikulum belum tersedia.</p>
            )}
          </Card>
        </div>
      </div>

      <Card title="Riwayat Akademik">
        {!history || history.length === 0 ? (
          <p className="text-sm text-ink-secondary">Belum ada riwayat.</p>
        ) : (
          <ol className="relative ml-2 border-l border-border">
            {history.map((event, index) => {
              const Icon = EVENT_ICON[event.type]

              return (
                <li key={`${event.type}-${event.date}-${index}`} className="mb-5 ml-5 last:mb-0">
                  <span className="absolute -left-3 flex size-6 items-center justify-center rounded-full border border-border bg-surface text-primary">
                    <Icon className="size-3.5" />
                  </span>
                  <p className="text-xs text-ink-tertiary">{formatDate(event.date, 'medium')}</p>
                  <p className="text-sm font-medium text-ink-primary">{event.title}</p>
                  {event.description && <p className="text-xs text-ink-secondary">{event.description}</p>}
                </li>
              )
            })}
          </ol>
        )}
      </Card>
    </div>
  )
}
