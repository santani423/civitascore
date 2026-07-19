import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Wallet } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { studentService } from '@/services/academicService'
import type { StudentStatus } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { buildDashboardLink } from '@/utils/dashboardLink'

const STATUS_LABEL: Record<StudentStatus, string> = {
  active: 'Aktif',
  leave: 'Cuti',
  graduated: 'Lulus',
  inactive: 'Nonaktif',
  dropped_out: 'Drop Out',
}

const STATUS_VARIANT: Record<StudentStatus, BadgeVariant> = {
  active: 'success',
  leave: 'warning',
  graduated: 'info',
  inactive: 'neutral',
  dropped_out: 'danger',
}

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function StudentDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canSeeInvoices = usePermission('invoices.read')

  const getStudent = useCallback(() => studentService.show(id ?? ''), [id])
  const { data: student, isLoading, error } = useFetch(getStudent)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Mahasiswa"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Mahasiswa', path: ROUTES.mahasiswa },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.mahasiswa)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {student && (
        <Card title={student.name} description={`NIM ${student.nim}`}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={STATUS_VARIANT[student.status]}>{STATUS_LABEL[student.status]}</Badge>
            </div>
            <DetailField label="Program Studi" value={student.study_program_name} />
            <DetailField label="Angkatan" value={student.admission_year} />
            <DetailField label="Email" value={student.email} />
            <DetailField label="Terdaftar" value={student.enrolled_at} />
            <DetailField label="Lulus" value={student.graduated_at} />
          </div>

          {canSeeInvoices && (
            <div className="mt-5 border-t border-border pt-4">
              <Link to={buildDashboardLink(ROUTES.keuangan.tagihan, { student_id: student.id })}>
                <Button variant="outline" leftIcon={<Wallet className="size-4" />}>
                  Lihat Tagihan Mahasiswa Ini
                </Button>
              </Link>
            </div>
          )}
        </Card>
      )}
    </div>
  )
}
