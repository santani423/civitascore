import { useCallback } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { employeeService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'

export function EmployeeDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getEmployee = useCallback(() => employeeService.show(id ?? ''), [id])
  const { data: employee, isLoading, error } = useFetch(getEmployee)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Pegawai"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Pegawai', path: ROUTES.pegawai },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.pegawai)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {employee && (
        <Card title={employee.name} description={employee.position ?? undefined}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={employee.is_active ? 'success' : 'neutral'}>{employee.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Unit Kerja</p>
              <p className="font-medium text-ink-primary">{employee.unit_kerja}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Email</p>
              <p className="font-medium text-ink-primary">{employee.email ?? '-'}</p>
            </div>
          </div>
        </Card>
      )}
    </div>
  )
}
