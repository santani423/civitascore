import { useCallback, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Pencil } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { classSectionService } from '@/services/academicService'
import type { StudentScheduleConflict } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { formatNumber } from '@/utils/formatters'
import { ClassTeachingModal } from './ClassTeachingModal'

export function ClassSectionDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const canManageTeaching = usePermission('classes.update')
  const [showTeachingForm, setShowTeachingForm] = useState(false)
  const [studentConflicts, setStudentConflicts] = useState<StudentScheduleConflict[]>([])

  const getClassSection = useCallback(() => classSectionService.show(id ?? ''), [id])
  const { data: classSection, isLoading, error, refetch } = useFetch(getClassSection)

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

      {studentConflicts.length > 0 && (
        <Alert
          variant="warning"
          title="Jadwal baru beririsan dengan kelas lain yang diambil peserta"
          onDismiss={() => setStudentConflicts([])}
        >
          {studentConflicts
            .map((conflict) => `${conflict.name} (${conflict.nim}) — ${conflict.course_name} ${conflict.class_code}, ${conflict.schedule}`)
            .join('; ')}
        </Alert>
      )}

      {classSection && (
        <Card
          title={classSection.course_name ?? classSection.class_code}
          description={`${classSection.course_code ?? '-'} · ${classSection.class_code}`}
        >
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
              <p className="text-ink-tertiary">SKS</p>
              <p className="font-medium text-ink-primary">{classSection.credits ?? '-'}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Kapasitas</p>
              <p className="font-medium text-ink-primary">{formatNumber(classSection.capacity)}</p>
            </div>
            <div>
              <p className="text-ink-tertiary">Mahasiswa Terdaftar</p>
              <p className="font-medium text-ink-primary">{formatNumber(classSection.enrolled_count ?? 0)}</p>
            </div>
          </div>
        </Card>
      )}

      {classSection && (
        <Card
          title="Dosen & Jadwal"
          description="Dosen pengampu bertanggung jawab atas nilai, absensi, dan materi kelas ini."
          actions={
            canManageTeaching && (
              <Button
                variant="outline"
                size="sm"
                leftIcon={<Pencil className="size-4" />}
                onClick={() => setShowTeachingForm(true)}
              >
                Ubah
              </Button>
            )
          }
        >
          <div className="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
            <div className="flex flex-col gap-1">
              <p className="text-ink-tertiary">Dosen Pengampu</p>
              {classSection.lecturer ? (
                <div>
                  <p className="font-medium text-ink-primary">{classSection.lecturer.name}</p>
                  {classSection.lecturer.email && (
                    <p className="text-xs text-ink-tertiary">{classSection.lecturer.email}</p>
                  )}
                </div>
              ) : (
                <div>
                  <Badge variant="warning">Belum ditetapkan</Badge>
                </div>
              )}
            </div>
            <div className="flex flex-col gap-1">
              <p className="text-ink-tertiary">Jadwal Mingguan</p>
              {classSection.schedules.length > 0 ? (
                <ul className="flex flex-col gap-1">
                  {classSection.schedules.map((schedule) => (
                    <li key={schedule.id} className="font-medium text-ink-primary">
                      {schedule.day_label}, {schedule.start_time}–{schedule.end_time}
                      {schedule.room && <span className="font-normal text-ink-tertiary"> · {schedule.room}</span>}
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-ink-tertiary">Belum ada jadwal.</p>
              )}
            </div>
          </div>
        </Card>
      )}

      {showTeachingForm && classSection && (
        <ClassTeachingModal
          classSection={classSection}
          onClose={() => setShowTeachingForm(false)}
          onSaved={(result) => {
            setShowTeachingForm(false)
            setStudentConflicts(result.warnings.student_conflicts)
            refetch()
          }}
        />
      )}
    </div>
  )
}
