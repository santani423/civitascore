import { useCallback } from 'react'
import { Link } from 'react-router-dom'
import { BookOpen, Clock, FileText, NotebookPen, UserRound } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import { formatPercent } from '@/utils/portalFormat'

export function PortalCoursesPage() {
  const fetchCourses = useCallback(() => studentPortalService.courses(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchCourses)

  if (isLoading && !data) return <PortalLoading cards={3} rows={0} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Mata Kuliah"
        description={data.term ? `Mata kuliah yang Anda ikuti semester ${data.term.label}.` : 'Belum ada semester aktif.'}
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Perkuliahan' }, { label: 'Mata Kuliah' }]}
      />

      {data.courses.length === 0 ? (
        <PortalEmpty
          icon={BookOpen}
          title="Belum ada mata kuliah yang diikuti."
          description="Mata kuliah muncul setelah KRS Anda disetujui dosen wali."
          action={
            <Link to={ROUTES.portal.krs}>
              <Button size="sm">Buka KRS</Button>
            </Link>
          }
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
          {data.courses.map((course) => (
            <Link key={course.id} to={ROUTES.portal.mataKuliahDetail.replace(':id', course.id)} className="group">
              <Card className="h-full transition-shadow group-hover:shadow-popover">
                <div className="flex items-start justify-between gap-2">
                  <div className="min-w-0">
                    <p className="font-semibold text-ink-primary group-hover:text-primary">{course.course.name}</p>
                    <p className="text-xs text-ink-secondary">
                      {course.course.code} · Kelas {course.class_code} · {course.course.credits} SKS
                    </p>
                  </div>
                  {course.pending_assignments_count > 0 && <Badge variant="warning">{course.pending_assignments_count} tugas</Badge>}
                </div>
                <div className="mt-3 flex flex-col gap-1.5 text-xs text-ink-secondary">
                  <p className="flex items-center gap-1.5">
                    <UserRound className="size-3.5" /> {course.lecturer?.name ?? 'Dosen belum ditetapkan'}
                  </p>
                  <p className="flex items-center gap-1.5">
                    <Clock className="size-3.5" />
                    {course.schedules.length > 0
                      ? course.schedules.map((s) => `${s.day_label} ${s.start_time}–${s.end_time}`).join(', ')
                      : 'Jadwal belum ditetapkan'}
                  </p>
                  <p className="flex items-center gap-1.5">
                    <FileText className="size-3.5" /> {course.materials_count} materi
                    <NotebookPen className="ml-2 size-3.5" /> {course.assignments_count} tugas
                  </p>
                  <p>Kehadiran: {formatPercent(course.attendance.percentage)}</p>
                </div>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
