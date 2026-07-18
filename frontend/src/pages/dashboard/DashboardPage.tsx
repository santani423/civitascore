import { PageHeader } from '@/components/ui/PageHeader'
import { SummaryCardsGrid } from '@/components/dashboard/SummaryCardsGrid'
import { StudentGrowthChart } from '@/components/dashboard/StudentGrowthChart'
import { FacultyDistributionChart } from '@/components/dashboard/FacultyDistributionChart'
import { PaymentStatusChart } from '@/components/dashboard/PaymentStatusChart'
import { AttendanceTrendChart } from '@/components/dashboard/AttendanceTrendChart'
import { StudentStatusChart } from '@/components/dashboard/StudentStatusChart'
import { RecentActivityList } from '@/components/dashboard/RecentActivityList'
import { AcademicAgendaList } from '@/components/dashboard/AcademicAgendaList'
import { PendingApprovalsTable } from '@/components/dashboard/PendingApprovalsTable'
import { useAuthStore } from '@/stores/authStore'

export function DashboardPage() {
  const userName = useAuthStore((state) => state.session?.user.name)

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Dashboard"
        description={userName ? `Selamat datang kembali, ${userName}.` : 'Ringkasan aktivitas akademik universitas.'}
      />

      <SummaryCardsGrid />

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <StudentGrowthChart />
        <FacultyDistributionChart />
        <AttendanceTrendChart />
        <PaymentStatusChart />
      </div>

      <div className="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div className="xl:col-span-1">
          <StudentStatusChart />
        </div>
        <div className="xl:col-span-2">
          <PendingApprovalsTable />
        </div>
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <RecentActivityList />
        <AcademicAgendaList />
      </div>
    </div>
  )
}
