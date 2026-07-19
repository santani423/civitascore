import { useCallback } from 'react'
import { Building2, Users, UserCheck, CheckCircle2 } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { StatCard } from '@/components/ui/StatCard'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { useAuthStore } from '@/stores/authStore'
import { platformStatisticsService } from '@/services/tenancyService'
import { humanizeSlug, formatNumber } from '@/utils/formatters'

export function PlatformDashboardPage() {
  const userName = useAuthStore((state) => state.session?.user.name)
  const fetchStatistics = useCallback(() => platformStatisticsService.index(), [])
  const { data, isLoading, error } = useFetch(fetchStatistics)

  return (
    <div className="flex flex-col gap-6">
      <PageHeader
        title="Dashboard Platform"
        description={userName ? `Selamat datang kembali, ${userName}.` : 'Ringkasan lintas-universitas.'}
      />

      {error && <Alert variant="danger">{error}</Alert>}

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Total Universitas" value={isLoading ? '...' : formatNumber(data?.universities_total ?? 0)} icon={Building2} />
        <StatCard label="Universitas Aktif" value={isLoading ? '...' : formatNumber(data?.universities_active ?? 0)} icon={CheckCircle2} />
        <StatCard label="Total Pengguna" value={isLoading ? '...' : formatNumber(data?.users_total ?? 0)} icon={Users} />
        <StatCard label="Total Membership" value={isLoading ? '...' : formatNumber(data?.memberships_total ?? 0)} icon={UserCheck} />
      </div>

      <Card title="Universitas per Status">
        {!data && !isLoading && <p className="text-sm text-ink-tertiary">Belum ada data.</p>}
        <div className="flex flex-wrap gap-2">
          {data &&
            Object.entries(data.universities_by_status).map(([status, total]) => (
              <Badge key={status} variant="neutral" className="text-sm">
                {humanizeSlug(status)}: {formatNumber(total)}
              </Badge>
            ))}
        </div>
      </Card>
    </div>
  )
}
