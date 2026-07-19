import { useCallback, useState } from 'react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Alert } from '@/components/ui/Alert'
import { EmptyState } from '@/components/ui/EmptyState'
import { DashboardFilterBar } from '@/components/dashboard/DashboardFilterBar'
import { SummaryCardsGrid } from '@/components/dashboard/SummaryCardsGrid'
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

const EMPTY_FILTER_OPTIONS: DashboardFilterOptions = { academic_terms: [], faculties: [], study_programs: [] }

export function DashboardPage() {
  const userName = useAuthStore((state) => state.session?.user.name)
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
          <SummaryCardsGrid summary={summary} isLoading={isLoading} />

          <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <StudentGrowthChart data={charts.student_growth ?? []} />
            <FacultyDistributionChart data={charts.students_by_program ?? []} />
            <PaymentTrendChart data={charts.payment_trend ?? []} />
            <PaymentStatusChart data={charts.invoice_status ?? []} />
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <StudentStatusChart data={charts.student_status ?? []} />
            <StaffByUnitChart data={charts.staff_by_unit ?? []} />
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <ActiveClassesByProgramChart data={charts.active_classes_by_program ?? []} />
            {charts.approval_status && <ApprovalStatusChart data={charts.approval_status} />}
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
