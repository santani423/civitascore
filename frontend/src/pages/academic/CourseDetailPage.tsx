import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { courseService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function CourseDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getCourse = useCallback(() => courseService.show(id ?? ''), [id])
  const { data: course, isLoading, error } = useFetch(getCourse)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Mata Kuliah"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Mata Kuliah', path: ROUTES.akademik.mataKuliah },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.akademik.mataKuliah)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {course && (
        <Card title={course.name} description={course.code}>
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div>
              <p className="text-ink-tertiary">Status</p>
              <Badge variant={course.is_active ? 'success' : 'neutral'}>{course.is_active ? 'Aktif' : 'Nonaktif'}</Badge>
            </div>
            <DetailField label="Program Studi" value={course.study_program_name} />
            <div>
              <p className="text-ink-tertiary">Kurikulum</p>
              {course.curriculum_name ? (
                <Link
                  to={`${ROUTES.akademik.kurikulum}/${course.curriculum_id}`}
                  className="font-medium text-primary hover:underline"
                >
                  {course.curriculum_name}
                </Link>
              ) : (
                <p className="font-medium text-ink-primary">-</p>
              )}
            </div>
            <DetailField label="SKS" value={course.credits} />
            <DetailField label="Semester" value={course.semester_level} />
          </div>
        </Card>
      )}
    </div>
  )
}
