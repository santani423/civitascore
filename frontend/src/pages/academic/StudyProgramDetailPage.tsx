import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { BookOpen, Users } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { studyProgramService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'
import { buildDashboardLink } from '@/utils/dashboardLink'
import { formatNumber } from '@/utils/formatters'

export function StudyProgramDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canSeeStudents = usePermission('students.read')
  const canSeeClasses = usePermission('classes.read')

  const getProgram = useCallback(() => studyProgramService.show(id ?? ''), [id])
  const { data: program, isLoading, error } = useFetch(getProgram)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Program Studi"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Program Studi', path: ROUTES.akademik.programStudi },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.akademik.programStudi)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {program && (
        <Card title={program.name} description={`${program.code} · ${program.degree_level}`}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={program.is_active ? 'success' : 'neutral'}>{program.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            </div>
            <div>
              <p className="text-ink-tertiary">Fakultas</p>
              <p className="font-medium text-ink-primary">{program.faculty_name ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Jumlah Mahasiswa</p>
              <p className="font-medium text-ink-primary">{formatNumber(program.students_count ?? 0)}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Jumlah Kelas</p>
              <p className="font-medium text-ink-primary">{formatNumber(program.class_sections_count ?? 0)}</p>
            </div>
          </div>

          {(canSeeStudents || canSeeClasses) && (
            <div className="mt-5 flex flex-wrap gap-2 border-t border-border pt-4">
              {canSeeStudents && (
                <Link to={buildDashboardLink(ROUTES.mahasiswa, { study_program_id: program.id })}>
                  <Button variant="outline" leftIcon={<Users className="size-4" />}>
                    Lihat Mahasiswa
                  </Button>
                </Link>
              )}
              {canSeeClasses && (
                <Link to={buildDashboardLink(ROUTES.akademik.kelasJadwal, { study_program_id: program.id })}>
                  <Button variant="outline" leftIcon={<BookOpen className="size-4" />}>
                    Lihat Kelas
                  </Button>
                </Link>
              )}
            </div>
          )}
        </Card>
      )}
    </div>
  )
}
