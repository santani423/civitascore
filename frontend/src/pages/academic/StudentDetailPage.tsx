import { useCallback } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Wallet } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { DataTable, type DataTableColumn } from '@/components/ui/DataTable'
import { useFetch } from '@/hooks/useFetch'
import { usePermission } from '@/hooks/usePermission'
import { attendanceService, gradeService, krsItemService, studentService } from '@/services/academicService'
import type { Attendance, AttendanceStatus, Grade, KrsItem, LetterGrade, StudentStatus } from '@/types/academic'
import { internshipService } from '@/services/internshipService'
import type { Internship, InternshipProgramType, InternshipStatus } from '@/types/internship'
import { scholarshipApplicationService } from '@/services/scholarshipService'
import type { ScholarshipApplication, ScholarshipApplicationStatus } from '@/types/scholarship'
import { thesisService } from '@/services/thesisService'
import type { Thesis, ThesisStatus } from '@/types/thesis'
import { ROUTES } from '@/constants/routes'
import { buildDashboardLink } from '@/utils/dashboardLink'

const LETTER_GRADE_VARIANT: Record<LetterGrade, BadgeVariant> = {
  A: 'success',
  AB: 'success',
  B: 'info',
  BC: 'info',
  C: 'warning',
  D: 'danger',
  E: 'danger',
}

const KRS_COLUMNS: DataTableColumn<KrsItem>[] = [
  { header: 'Mata Kuliah', cell: (row) => row.course_name ?? '-' },
  { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
  { header: 'Nilai', cell: (row) => row.letter_grade ?? <span className="text-ink-tertiary">Belum dinilai</span> },
]

const GRADE_COLUMNS: DataTableColumn<Grade>[] = [
  { header: 'Mata Kuliah', cell: (row) => row.course_name ?? '-' },
  { header: 'Periode', cell: (row) => row.academic_term_label ?? '-' },
  {
    header: 'Nilai Huruf',
    cell: (row) => (row.letter_grade ? <Badge variant={LETTER_GRADE_VARIANT[row.letter_grade]}>{row.letter_grade}</Badge> : '-'),
  },
  { header: 'Skor', cell: (row) => row.score ?? '-' },
]

const ATTENDANCE_COLUMNS: DataTableColumn<Attendance>[] = [
  { header: 'Tanggal', cell: (row) => row.meeting_date },
  { header: 'Mata Kuliah', cell: (row) => row.course_name ?? '-' },
  {
    header: 'Status',
    cell: (row) => <Badge variant={ATTENDANCE_STATUS_VARIANT[row.status]}>{ATTENDANCE_STATUS_LABEL[row.status]}</Badge>,
  },
]

const ATTENDANCE_STATUS_LABEL: Record<AttendanceStatus, string> = {
  present: 'Hadir',
  permitted: 'Izin',
  sick: 'Sakit',
  absent: 'Alpa',
}

const ATTENDANCE_STATUS_VARIANT: Record<AttendanceStatus, BadgeVariant> = {
  present: 'success',
  permitted: 'info',
  sick: 'warning',
  absent: 'danger',
}

const SCHOLARSHIP_STATUS_LABEL: Record<ScholarshipApplicationStatus, string> = {
  submitted: 'Diajukan',
  under_review: 'Ditinjau',
  approved: 'Disetujui',
  rejected: 'Ditolak',
}

const SCHOLARSHIP_STATUS_VARIANT: Record<ScholarshipApplicationStatus, BadgeVariant> = {
  submitted: 'neutral',
  under_review: 'info',
  approved: 'success',
  rejected: 'danger',
}

const SCHOLARSHIP_COLUMNS: DataTableColumn<ScholarshipApplication>[] = [
  { header: 'Beasiswa', cell: (row) => row.scholarship_name ?? '-' },
  { header: 'Diajukan', cell: (row) => row.submitted_at.slice(0, 10) },
  {
    header: 'Status',
    cell: (row) => <Badge variant={SCHOLARSHIP_STATUS_VARIANT[row.status]}>{SCHOLARSHIP_STATUS_LABEL[row.status]}</Badge>,
  },
]

const THESIS_STATUS_LABEL: Record<ThesisStatus, string> = {
  proposal: 'Pengajuan Judul',
  bimbingan: 'Bimbingan',
  seminar_proposal: 'Seminar Proposal',
  penelitian: 'Penelitian',
  sidang: 'Sidang',
  selesai: 'Selesai',
}

const THESIS_STATUS_VARIANT: Record<ThesisStatus, BadgeVariant> = {
  proposal: 'neutral',
  bimbingan: 'info',
  seminar_proposal: 'info',
  penelitian: 'warning',
  sidang: 'warning',
  selesai: 'success',
}

const THESIS_COLUMNS: DataTableColumn<Thesis>[] = [
  { header: 'Judul', cell: (row) => row.title },
  { header: 'Pembimbing', cell: (row) => row.supervisor_name ?? '-' },
  {
    header: 'Status',
    cell: (row) => <Badge variant={THESIS_STATUS_VARIANT[row.status]}>{THESIS_STATUS_LABEL[row.status]}</Badge>,
  },
]

const INTERNSHIP_PROGRAM_TYPE_LABEL: Record<InternshipProgramType, string> = {
  magang: 'Magang',
  kkn: 'KKN',
  mbkm: 'MBKM',
}

const INTERNSHIP_STATUS_LABEL: Record<InternshipStatus, string> = {
  terdaftar: 'Terdaftar',
  berlangsung: 'Berlangsung',
  selesai: 'Selesai',
  dibatalkan: 'Dibatalkan',
}

const INTERNSHIP_STATUS_VARIANT: Record<InternshipStatus, BadgeVariant> = {
  terdaftar: 'neutral',
  berlangsung: 'info',
  selesai: 'success',
  dibatalkan: 'danger',
}

const INTERNSHIP_COLUMNS: DataTableColumn<Internship>[] = [
  { header: 'Instansi', cell: (row) => row.institution_name },
  { header: 'Jenis', cell: (row) => INTERNSHIP_PROGRAM_TYPE_LABEL[row.program_type] },
  {
    header: 'Status',
    cell: (row) => <Badge variant={INTERNSHIP_STATUS_VARIANT[row.status]}>{INTERNSHIP_STATUS_LABEL[row.status]}</Badge>,
  },
]

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
  const canSeeKrs = usePermission('krs.read')
  const canSeeGrades = usePermission('grades.read')
  const canSeeAttendance = usePermission('attendance.read')
  const canSeeScholarships = usePermission('scholarships.read')
  const canSeeTheses = usePermission('theses.read')
  const canSeeInternships = usePermission('internships.read')

  const getStudent = useCallback(() => studentService.show(id ?? ''), [id])
  const { data: student, isLoading, error } = useFetch(getStudent)

  const getKrsItems = useCallback(async () => {
    if (!canSeeKrs) return []
    const result = await krsItemService.index({ filter: { student_id: id ?? '' }, per_page: 5, sort: '-created_at' })
    return result.data
  }, [id, canSeeKrs])
  const { data: krsItems } = useFetch(getKrsItems)

  const getGrades = useCallback(async () => {
    if (!canSeeGrades) return []
    const result = await gradeService.index({ filter: { student_id: id ?? '' }, per_page: 5, sort: '-created_at' })
    return result.data
  }, [id, canSeeGrades])
  const { data: grades } = useFetch(getGrades)

  const getAttendances = useCallback(async () => {
    if (!canSeeAttendance) return []
    const result = await attendanceService.index({ filter: { student_id: id ?? '' }, per_page: 5, sort: '-meeting_date' })
    return result.data
  }, [id, canSeeAttendance])
  const { data: attendances } = useFetch(getAttendances)

  const getScholarshipApplications = useCallback(async () => {
    if (!canSeeScholarships) return []
    const result = await scholarshipApplicationService.index({ filter: { student_id: id ?? '' }, per_page: 5, sort: '-submitted_at' })
    return result.data
  }, [id, canSeeScholarships])
  const { data: scholarshipApplications } = useFetch(getScholarshipApplications)

  const getTheses = useCallback(async () => {
    if (!canSeeTheses) return []
    const result = await thesisService.index({ filter: { student_id: id ?? '' }, per_page: 5 })
    return result.data
  }, [id, canSeeTheses])
  const { data: theses } = useFetch(getTheses)

  const getInternships = useCallback(async () => {
    if (!canSeeInternships) return []
    const result = await internshipService.index({ filter: { student_id: id ?? '' }, per_page: 5, sort: '-start_date' })
    return result.data
  }, [id, canSeeInternships])
  const { data: internships } = useFetch(getInternships)

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
        <>
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

          {canSeeKrs && (
            <Card
              title="KRS"
              description="5 kartu rencana studi terbaru."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.akademik.krs, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable columns={KRS_COLUMNS} data={krsItems ?? []} rowKey={(row) => row.id} emptyMessage="Belum ada KRS." />
            </Card>
          )}

          {canSeeGrades && (
            <Card
              title="Nilai"
              description="5 nilai terbaru."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.akademik.penilaian, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable columns={GRADE_COLUMNS} data={grades ?? []} rowKey={(row) => row.id} emptyMessage="Belum ada nilai." />
            </Card>
          )}

          {canSeeAttendance && (
            <Card
              title="Absensi"
              description="5 riwayat kehadiran terbaru."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.akademik.absensi, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable
                columns={ATTENDANCE_COLUMNS}
                data={attendances ?? []}
                rowKey={(row) => row.id}
                emptyMessage="Belum ada data absensi."
              />
            </Card>
          )}

          {canSeeScholarships && (
            <Card
              title="Beasiswa"
              description="Pengajuan beasiswa mahasiswa ini."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.keuangan.beasiswa, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable
                columns={SCHOLARSHIP_COLUMNS}
                data={scholarshipApplications ?? []}
                rowKey={(row) => row.id}
                emptyMessage="Belum ada pengajuan beasiswa."
              />
            </Card>
          )}

          {canSeeTheses && (
            <Card
              title="Skripsi"
              description="Status skripsi mahasiswa ini."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.skripsi, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable columns={THESIS_COLUMNS} data={theses ?? []} rowKey={(row) => row.id} emptyMessage="Belum ada data skripsi." />
            </Card>
          )}

          {canSeeInternships && (
            <Card
              title="Magang dan MBKM"
              description="Riwayat magang dan MBKM mahasiswa ini."
              noPadding
              actions={
                <Link to={buildDashboardLink(ROUTES.magangMbkm, { student_id: student.id })}>
                  <Button variant="outline" size="sm">
                    Lihat Semua
                  </Button>
                </Link>
              }
            >
              <DataTable
                columns={INTERNSHIP_COLUMNS}
                data={internships ?? []}
                rowKey={(row) => row.id}
                emptyMessage="Belum ada data magang atau MBKM."
              />
            </Card>
          )}
        </>
      )}
    </div>
  )
}
