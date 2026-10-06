import { useCallback } from 'react'
import { Award } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { GradeTable, SummaryTiles } from '@/components/portal/GradeTable'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import { formatGpa } from '@/utils/portalFormat'

export function PortalGradesPage() {
  const fetchGrades = useCallback(() => studentPortalService.grades(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchGrades)

  if (isLoading && !data) return <PortalLoading cards={4} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Nilai"
        description="Nilai seluruh mata kuliah per semester. Nilai bersifat final dan hanya dapat diubah dosen pengampu."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Akademik' }, { label: 'Nilai' }]}
      />

      <SummaryTiles
        items={[
          { label: 'IPK', value: formatGpa(data.ipk) },
          { label: 'Total SKS', value: String(data.total_credits) },
          { label: 'SKS Lulus', value: String(data.passed_credits) },
          { label: 'Semester Ditempuh', value: String(data.terms.length) },
        ]}
      />

      {data.terms.length === 0 ? (
        <PortalEmpty icon={Award} title="Nilai belum tersedia." description="Nilai akan muncul setelah dosen menginput nilai mata kuliah Anda." />
      ) : (
        [...data.terms].reverse().map((term) => (
          <Card
            key={term.term.id}
            title={`${term.semester_number ? `Semester ${term.semester_number} — ` : ''}${term.term.label}`}
            description={`${term.total_credits} SKS diambil · IPS ${formatGpa(term.ips)}`}
            noPadding
          >
            <div className="px-4 py-2 sm:px-2">
              <GradeTable rows={term.rows} />
            </div>
          </Card>
        ))
      )}
    </div>
  )
}
