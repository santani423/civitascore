import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { classSectionService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'
import { formatNumber } from '@/utils/formatters'

export function ClassSectionDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getClassSection = useCallback(() => classSectionService.show(id ?? ''), [id])
  const { data: classSection, isLoading, error } = useFetch(getClassSection)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Kelas"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Kelas dan Jadwal', path: ROUTES.akademik.kelasJadwal },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.akademik.kelasJadwal)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {classSection && (
        <Card title={classSection.course_name} description={classSection.class_code}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={classSection.is_active ? 'success' : 'neutral'}>
                {classSection.is_active ? 'Aktif' : 'Nonaktif'}
              </Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Program Studi</p>
              <p className="font-medium text-ink-primary">{classSection.study_program_name ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Periode Akademik</p>
              <p className="font-medium text-ink-primary">{classSection.academic_term_label ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Kapasitas</p>
              <p className="font-medium text-ink-primary">{formatNumber(classSection.capacity)}</p>
            </div>
          </div>
        </Card>
      )}
    </div>
  )
}
