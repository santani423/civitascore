import { useCallback } from 'react'
import { Link, useParams } from 'react-router-dom'
import { ClipboardCheck, FileText, NotebookPen } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { PortalEmpty, PortalError, PortalLoading, InfoList } from '@/components/portal/PortalState'
import { AssignmentStatusBadge } from '@/components/portal/badges'
import { MaterialItem } from '@/components/portal/MaterialItem'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import { formatCountdown, formatDateTime, formatPercent } from '@/utils/portalFormat'

export function PortalCourseDetailPage() {
  const { id = '' } = useParams()
  const fetchCourse = useCallback(() => studentPortalService.course(id), [id])
  const { data, isLoading, error, refetch } = useFetch(fetchCourse)

  if (isLoading && !data) return <PortalLoading cards={2} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title={data.course.name}
        description={`${data.course.code} · Kelas ${data.class_code} · ${data.course.credits} SKS`}
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Mata Kuliah', path: ROUTES.portal.mataKuliah },
          { label: data.course.name },
        ]}
      />

      <Card>
        <InfoList
          columns={3}
          items={[
            { label: 'Dosen pengampu', value: data.lecturer?.name ?? 'Belum ditetapkan' },
            {
              label: 'Jadwal',
              value: data.schedules.length
                ? data.schedules.map((s) => `${s.day_label} ${s.start_time}–${s.end_time}${s.room ? ` (${s.room})` : ''}`).join('; ')
                : 'Belum ditetapkan',
            },
            {
              label: 'Kehadiran',
              value: `${formatPercent(data.attendance.percentage)} (${data.attendance.present}/${data.attendance.total_meetings} pertemuan)`,
            },
          ]}
        />
      </Card>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="flex flex-col gap-4 lg:col-span-2">
          <h2 className="text-base font-semibold text-ink-primary">Materi Perkuliahan</h2>
          {data.meetings.length === 0 ? (
            <PortalEmpty icon={FileText} title="Belum ada materi." description="Materi yang dibagikan dosen akan tampil di sini." />
          ) : (
            data.meetings.map((meeting) => (
              <Card key={meeting.label} title={meeting.label}>
                <div className="flex flex-col gap-2">
                  {meeting.materials.map((material) => (
                    <MaterialItem key={material.id} material={material} />
                  ))}
                </div>
              </Card>
            ))
          )}
        </div>

        <div className="flex flex-col gap-5">
          <Card title="Tugas">
            {data.assignments.length === 0 ? (
              <p className="flex items-center gap-2 text-sm text-ink-secondary">
                <NotebookPen className="size-4" /> Belum ada tugas.
              </p>
            ) : (
              <ul className="flex flex-col gap-3">
                {data.assignments.map((assignment) => (
                  <li key={assignment.id}>
                    <Link to={ROUTES.portal.tugasDetail.replace(':id', assignment.id)} className="group block">
                      <div className="flex items-start justify-between gap-2">
                        <p className="text-sm font-medium text-ink-primary group-hover:text-primary">{assignment.title}</p>
                        <AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />
                      </div>
                      <p className="text-xs text-ink-secondary">
                        Batas {formatDateTime(assignment.due_at)} · {formatCountdown(assignment.due_at)}
                      </p>
                    </Link>
                  </li>
                ))}
              </ul>
            )}
          </Card>

          <Card title="Ujian">
            {data.exams.length === 0 ? (
              <p className="flex items-center gap-2 text-sm text-ink-secondary">
                <ClipboardCheck className="size-4" /> Belum ada ujian.
              </p>
            ) : (
              <ul className="flex flex-col gap-2">
                {data.exams.map((exam) => (
                  <li key={exam.id} className="text-sm">
                    <p className="font-medium text-ink-primary">{exam.title}</p>
                    <p className="text-xs text-ink-secondary">
                      {exam.starts_at ? formatDateTime(exam.starts_at) : 'Tanpa jadwal'} · {exam.duration_minutes} menit
                    </p>
                  </li>
                ))}
                <li>
                  <Link to={ROUTES.portal.ujian} className="text-xs font-medium text-primary hover:underline">
                    Buka halaman Ujian →
                  </Link>
                </li>
              </ul>
            )}
          </Card>
        </div>
      </div>
    </div>
  )
}
