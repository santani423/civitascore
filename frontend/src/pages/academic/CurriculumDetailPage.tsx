import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { curriculumService } from '@/services/academicService'
import { ROUTES } from '@/constants/routes'
import { formatNumber } from '@/utils/formatters'

function DetailField({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <div>
      <p className="text-ink-tertiary">{label}</p>
      <p className="font-medium text-ink-primary">{value ?? '-'}</p>
    </div>
  )
}

export function CurriculumDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const getCurriculum = useCallback(() => curriculumService.show(id ?? ''), [id])
  const { data: curriculum, isLoading, error } = useFetch(getCurriculum)

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Detail Kurikulum"
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Kurikulum', path: ROUTES.akademik.kurikulum },
          { label: 'Detail' },
        ]}
        actions={
          <Button variant="outline" onClick={() => navigate(ROUTES.akademik.kurikulum)}>
            Kembali
          </Button>
        }
      />

      {isLoading && <p className="text-sm text-ink-tertiary">Memuat...</p>}
      {error && <Alert variant="danger">{error}</Alert>}

      {curriculum && (
        <>
          <Card title={curriculum.name} description={curriculum.academic_year}>
            <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div>
                <p className="text-ink-tertiary">Status</p>
                <Badge variant={curriculum.is_active ? 'success' : 'neutral'}>
                  {curriculum.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
              </div>
              <DetailField label="Program Studi" value={curriculum.study_program_name} />
              <DetailField label="Jumlah Mata Kuliah" value={formatNumber(curriculum.courses_count ?? 0)} />
            </div>
          </Card>

          <Card title="Mata Kuliah" noPadding>
            {curriculum.courses && curriculum.courses.length > 0 ? (
              <ul className="divide-y divide-border">
                {curriculum.courses.map((course) => (
                  <li key={course.id} className="flex items-center justify-between gap-3 p-3">
                    <Link to={`${ROUTES.akademik.mataKuliah}/${course.id}`} className="min-w-0">
                      <p className="truncate font-medium text-primary hover:underline">{course.name}</p>
                      <p className="text-xs text-ink-tertiary">
                        {course.code} · {course.credits} SKS · Semester {course.semester_level}
                      </p>
                    </Link>
                  </li>
                ))}
              </ul>
            ) : (
              <p className="p-4 text-sm text-ink-tertiary">Belum ada mata kuliah pada kurikulum ini.</p>
            )}
          </Card>
        </>
      )}
    </div>
  )
}
