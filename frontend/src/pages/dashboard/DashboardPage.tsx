import { useCallback, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Alert } from '@/components/ui/Alert'
import { EmptyState } from '@/components/ui/EmptyState'
import { DashboardFilterBar } from '@/components/dashboard/DashboardFilterBar'
import { SummaryCardsGrid, type SummaryCardsGridProps } from '@/components/dashboard/SummaryCardsGrid'
import { StudentGrowthChart } from '@/components/dashboard/StudentGrowthChart'
import { FacultyDistributionChart } from '@/components/dashboard/FacultyDistributionChart'
import { PaymentTrendChart } from '@/components/dashboard/PaymentTrendChart'
import { PaymentStatusChart } from '@/components/dashboard/PaymentStatusChart'
import { StudentStatusChart } from '@/components/dashboard/StudentStatusChart'
import { StaffByUnitChart } from '@/components/dashboard/StaffByUnitChart'
import { ActiveClassesByProgramChart } from '@/components/dashboard/ActiveClassesByProgramChart'
import { ApprovalStatusChart } from '@/components/dashboard/ApprovalStatusChart'
import { RecentActivityList } from '@/components/dashboard/RecentActivityList'
import { AcademicAgendaList } from '@/components/dashboard/AcademicAgendaList'
import { PendingApprovalsTable } from '@/components/dashboard/PendingApprovalsTable'
import { useAuthStore } from '@/stores/authStore'
import { useFetch } from '@/hooks/useFetch'
import { dashboardService } from '@/services/dashboardService'
import type { DashboardFilterOptions, DashboardQueryFilters } from '@/types/dashboard'
import { ROUTES } from '@/constants/routes'
import { buildDashboardLink } from '@/utils/dashboardLink'

const EMPTY_FILTER_OPTIONS: DashboardFilterOptions = { academic_terms: [], faculties: [], study_programs: [] }

/** "2026-03" -> {date_from: "2026-03-01", date_to: "2026-03-31"} — bulan diklik di chart tren pembayaran jadi rentang tanggal utuh. */
function monthToDateRange(month: string): { date_from: string; date_to: string } {
  const [year, monthNumber] = month.split('-').map(Number)
  const lastDay = new Date(year, monthNumber, 0).getDate()

  return {
    date_from: `${month}-01`,
    date_to: `${month}-${String(lastDay).padStart(2, '0')}`,
  }
}

export function DashboardPage() {
  const userName = useAuthStore((state) => state.session?.user.name)
  const navigate = useNavigate()
  const [filters, setFilters] = useState<DashboardQueryFilters>({})

  const fetchStats = useCallback(() => dashboardService.getStats(filters), [filters])
  const { data, isLoading, error } = useFetch(fetchStats)

  const summary = data?.summary ?? {}
  const charts = data?.charts ?? {}
  const filterOptions = data?.filters ?? EMPTY_FILTER_OPTIONS
  const hasFilterOptions = filterOptions.academic_terms.length > 0 || filterOptions.faculties.length > 0

  // Kartu/chart yang tidak ada di response berarti role ini memang tidak
  // punya akses ke jenis data itu (lihat DashboardController) — bukan "0
  // data". Kalau semuanya kosong, tampilkan satu pesan informatif, bukan
  // grid statistik yang terlihat rusak.
  const hasAnyStat = Object.keys(summary).length > 0 || Object.keys(charts).length > 0

  // Periode berjalan — target filter kartu Kelas Aktif kalau user belum
  // memilih periode sendiri di filter bar (cocok dengan default
  // DashboardStatsService::scopedClassQuery()).
  const currentTermId =
    filters.academic_term_id ?? filterOptions.academic_terms.find((term) => term.is_current)?.id

  // Filter dashboard yang sedang aktif ikut dibawa ke tiap List tujuan —
  // dimensi yang tidak dikenal halaman tujuan diabaikan pickFilterParams
  // di sana, jadi aman digabung apa adanya.
  const cardLinks = useMemo<SummaryCardsGridProps['links']>(
    () => ({
      total_students: buildDashboardLink(ROUTES.mahasiswa, { study_program_id: filters.study_program_id }),
      active_students: buildDashboardLink(ROUTES.mahasiswa, { status: 'active', study_program_id: filters.study_program_id }),
      total_lecturers: buildDashboardLink(ROUTES.dosen, { faculty_id: filters.faculty_id }),
      total_employees: buildDashboardLink(ROUTES.pegawai),
      total_study_programs: buildDashboardLink(ROUTES.akademik.programStudi, { faculty_id: filters.faculty_id }),
      active_classes: buildDashboardLink(ROUTES.akademik.kelasJadwal, {
        is_active: '1',
        academic_term_id: currentTermId,
        study_program_id: filters.study_program_id,
      }),
      unpaid_invoices: buildDashboardLink(ROUTES.keuangan.tagihan, { status: 'unpaid,partial' }),
      pending_approvals: buildDashboardLink(ROUTES.persetujuan, { status: 'submitted,in_progress' }),
    }),
    [filters.study_program_id, filters.faculty_id, currentTermId],
  )

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Dashboard"
        description={userName ? `Selamat datang kembali, ${userName}.` : 'Ringkasan aktivitas akademik universitas.'}
      />

      {error && <Alert variant="danger">{error}</Alert>}

      {!error && hasFilterOptions && <DashboardFilterBar options={filterOptions} value={filters} onChange={setFilters} />}

      {!error && (isLoading || hasAnyStat) && (
        <>
          <SummaryCardsGrid summary={summary} isLoading={isLoading} links={cardLinks} />

          <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <StudentGrowthChart
              data={charts.student_growth ?? []}
              onPointClick={(point) =>
                navigate(
                  buildDashboardLink(ROUTES.mahasiswa, {
                    admission_year: point.year,
                    study_program_id: filters.study_program_id,
                  }),
                )
              }
            />
            <FacultyDistributionChart
              data={charts.students_by_program ?? []}
              onBarClick={(point) => navigate(buildDashboardLink(ROUTES.mahasiswa, { study_program_id: point.id }))}
            />
            <PaymentTrendChart
              data={charts.payment_trend ?? []}
              onPointClick={(month) => navigate(buildDashboardLink(ROUTES.keuangan.pembayaran, monthToDateRange(month)))}
            />
            <PaymentStatusChart
              data={charts.invoice_status ?? []}
              onSliceClick={(slice) => navigate(buildDashboardLink(ROUTES.keuangan.tagihan, { status: slice.value }))}
            />
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <StudentStatusChart
              data={charts.student_status ?? []}
              onSliceClick={(slice) =>
                navigate(
                  buildDashboardLink(ROUTES.mahasiswa, {
                    status: slice.value,
                    study_program_id: filters.study_program_id,
                  }),
                )
              }
            />
            <StaffByUnitChart
              data={charts.staff_by_unit ?? []}
              onLecturerBarClick={(point) =>
                navigate(buildDashboardLink(ROUTES.dosen, { faculty_id: point.faculty_id ?? undefined }))
              }
              onEmployeeBarClick={(point) =>
                navigate(buildDashboardLink(ROUTES.pegawai, { unit_kerja: point.faculty_id ? undefined : point.unit }))
              }
            />
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ActiveClassesByProgramChart
              data={charts.active_classes_by_program ?? []}
              onBarClick={(point) =>
                navigate(
                  buildDashboardLink(ROUTES.akademik.kelasJadwal, {
                    study_program_id: point.id,
                    is_active: '1',
                    academic_term_id: currentTermId,
                  }),
                )
              }
            />
            {charts.approval_status && (
              <ApprovalStatusChart
                data={charts.approval_status}
                onSliceClick={(slice) => navigate(buildDashboardLink(ROUTES.persetujuan, { status: slice.values }))}
              />
            )}
          </div>
        </>
      )}

      {!error && !isLoading && !hasAnyStat && (
        <EmptyState
          title="Belum ada statistik untuk ditampilkan"
          description="Akun Anda belum memiliki akses ke data statistik dashboard. Hubungi admin universitas Anda jika ini tidak sesuai."
        />
      )}

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div className="xl:col-span-2">
          <PendingApprovalsTable />
        </div>
        <div className="xl:col-span-1">
          <AcademicAgendaList />
        </div>
      </div>

      <RecentActivityList />
    </div>
  )
}
