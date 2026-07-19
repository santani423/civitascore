import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { lecturerService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'

export function LecturerDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getLecturer = useCallback(() => lecturerService.show(id ?? ''), [id])
  const { data: lecturer, isLoading, error } = useFetch(getLecturer)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Dosen"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Dosen', path: ROUTES.dosen },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.dosen)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {lecturer && (
        <Card title={lecturer.name} description={`NIDN ${lecturer.nidn}`}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={lecturer.is_active ? 'success' : 'neutral'}>{lecturer.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Fakultas</p>
              <p className="font-medium text-ink-primary">{lecturer.faculty_name ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Email</p>
              <p className="font-medium text-ink-primary">{lecturer.email ?? '-'}</p>
            </div>
          </div>
        </Card>
      )}
    </div>
  )
}
