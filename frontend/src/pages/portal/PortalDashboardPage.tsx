import { useCallback } from 'react'
import { Link } from 'react-router-dom'
import {
  AlertTriangle,
  BookOpenCheck,
  CalendarClock,
  CheckCircle2,
  ChevronRight,
  ClipboardList,
  FileClock,
  GraduationCap,
  Info,
  Layers,
  Megaphone,
  Target,
  XCircle,
  type LucideIcon,
} from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { PortalError, PortalLoading, ProgressBar } from '@/components/portal/PortalState'
import { GradeBadge, KrsStatusBadge, StudentStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { studentPortalService } from '@/services/studentPortalService'
import { ROUTES } from '@/constants/routes'
import type { DashboardAlert, StudentDashboard } from '@/types/studentPortal'
import { formatCountdown, formatDate, formatDateTime, formatGpa, formatPercent } from '@/utils/portalFormat'
import { cn } from '@/utils/cn'

const ALERT_STYLE: Record<DashboardAlert['severity'], { icon: LucideIcon; classes: string }> = {
  danger: { icon: XCircle, classes: 'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200' },
  warning: { icon: AlertTriangle, classes: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200' },
  info: { icon: Info, classes: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200' },
  success: { icon: CheckCircle2, classes: 'border-primary-200 bg-primary-50 text-primary-800 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-200' },
}

const SLOT_STATUS: Record<'upcoming' | 'ongoing' | 'finished', { label: string; variant: 'info' | 'success' | 'neutral' }> = {
  ongoing: { label: 'Sedang berlangsung', variant: 'success' },
  upcoming: { label: 'Akan dimulai', variant: 'info' },
  finished: { label: 'Selesai', variant: 'neutral' },
}

function Metric({ label, value, caption, icon: Icon }: { label: string; value: string; caption?: string; icon: LucideIcon }) {
  return (
    <div className="rounded-xl border border-border bg-surface p-4 shadow-card">
      <div className="flex items-center justify-between gap-2">
        <p className="text-xs font-medium text-ink-secondary">{label}</p>
        <Icon className="size-4 text-primary" aria-hidden />
      </div>
      <p className="mt-2 text-2xl font-semibold tracking-tight text-ink-primary">{value}</p>
      {caption && <p className="mt-0.5 text-xs text-ink-tertiary">{caption}</p>}
    </div>
  )
}

function SectionLink({ to, label = 'Lihat semua' }: { to: string; label?: string }) {
  return (
    <Link to={to} className="inline-flex items-center gap-0.5 text-xs font-medium text-primary hover:underline">
      {label} <ChevronRight className="size-3.5" />
    </Link>
  )
}

function DashboardContent({ data }: { data: StudentDashboard }) {
  const { profile, academic, krs } = data
  const firstName = profile.name.split(' ')[0]

  return (
    <div className="flex flex-col gap-5">
      <div className="flex flex-col gap-3 rounded-xl border border-border bg-surface p-4 shadow-card sm:flex-row sm:items-center sm:justify-between sm:p-5">
        <div className="min-w-0">
          <p className="text-sm text-ink-secondary">Halo,</p>
          <h1 className="truncate text-xl font-semibold tracking-tight text-ink-primary sm:text-2xl">{firstName}</h1>
          <p className="mt-1 text-sm text-ink-secondary">
            {profile.nim} · {profile.study_program}
            {profile.faculty ? ` · ${profile.faculty}` : ''}
          </p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <StudentStatusBadge status={profile.status} />
          <Badge variant="neutral">Angkatan {profile.admission_year}</Badge>
          {profile.semester && <Badge variant="primary">Semester {profile.semester}</Badge>}
        </div>
      </div>

      {data.alerts.length > 0 && (
        <section aria-label="Perlu perhatian" className="flex flex-col gap-2">
          {data.alerts.map((alert, index) => {
            const { icon: Icon, classes } = ALERT_STYLE[alert.severity]
            const body = (
              <span className={cn('flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', classes)}>
                <Icon className="mt-0.5 size-4 shrink-0" aria-hidden />
                <span className="flex-1">{alert.message}</span>
                {alert.link && <ChevronRight className="mt-0.5 size-4 shrink-0 opacity-60" aria-hidden />}
              </span>
            )

            return alert.link ? (
              <Link key={`${alert.type}-${index}`} to={alert.link} className="block transition-opacity hover:opacity-90">
                {body}
              </Link>
            ) : (
              <div key={`${alert.type}-${index}`}>{body}</div>
            )
          })}
        </section>
      )}

      <section className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
        <Metric label="IPK" value={formatGpa(academic.ipk)} caption={`${academic.total_credits} SKS kumulatif`} icon={GraduationCap} />
        <Metric
          label="IPS Terakhir"
          value={formatGpa(academic.last_ips?.ips)}
          caption={academic.last_ips?.label ?? 'Belum ada nilai'}
          icon={Target}
        />
        <Metric
          label="SKS Semester Ini"
          value={`${academic.current_term_credits}`}
          caption={academic.max_credits ? `Maksimal ${academic.max_credits} SKS` : undefined}
          icon={Layers}
        />
        <Metric
          label="SKS Tersisa"
          value={academic.remaining_credits !== null ? `${academic.remaining_credits}` : '-'}
          caption={`${academic.passed_credits} SKS lulus`}
          icon={BookOpenCheck}
        />
      </section>

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <div className="flex flex-col gap-5 lg:col-span-2">
          <Card
            title="Jadwal Hari Ini"
            description={academic.current_term ? academic.current_term.label : undefined}
            actions={<SectionLink to={ROUTES.portal.jadwal} label="Jadwal lengkap" />}
          >
            {data.today_schedule.length === 0 ? (
              <div className="text-sm text-ink-secondary">
                Tidak ada kuliah hari ini.
                {data.next_class && (
                  <p className="mt-2">
                    Kuliah berikutnya: <span className="font-medium text-ink-primary">{data.next_class.course_name}</span> —{' '}
                    {formatDate(data.next_class.date, 'long')}, {data.next_class.start_time}
                    {data.next_class.room ? ` · ${data.next_class.room}` : ''}
                  </p>
                )}
              </div>
            ) : (
              <ul className="flex flex-col divide-y divide-border">
                {data.today_schedule.map((slot) => (
                  <li key={`${slot.id}-${slot.date}`} className="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <p className="font-medium text-ink-primary">{slot.course_name}</p>
                      <p className="text-xs text-ink-secondary">
                        {slot.start_time}–{slot.end_time}
                        {slot.room ? ` · ${slot.room}` : ''}
                        {slot.lecturer ? ` · ${slot.lecturer.name}` : ''}
                      </p>
                    </div>
                    <Badge variant={SLOT_STATUS[slot.status].variant}>{SLOT_STATUS[slot.status].label}</Badge>
                  </li>
                ))}
              </ul>
            )}
          </Card>

          <div className="grid grid-cols-1 gap-5 md:grid-cols-2">
            <Card title="Ujian Mendatang" actions={<SectionLink to={ROUTES.portal.ujian} />}>
              {data.upcoming_exams.length === 0 ? (
                <p className="text-sm text-ink-secondary">Belum ada ujian terjadwal.</p>
              ) : (
                <ul className="flex flex-col gap-3">
                  {data.upcoming_exams.map((exam) => (
                    <li key={exam.id} className="min-w-0">
                      <div className="flex items-start justify-between gap-2">
                        <p className="truncate text-sm font-medium text-ink-primary">{exam.title}</p>
                        <Badge variant={exam.status === 'upcoming' ? 'neutral' : 'warning'}>
                          {exam.status === 'upcoming' ? 'Terjadwal' : exam.status === 'in_progress' ? 'Dikerjakan' : 'Dibuka'}
                        </Badge>
                      </div>
                      <p className="text-xs text-ink-secondary">
                        {exam.course_name} · {exam.starts_at ? formatDateTime(exam.starts_at) : 'Tanpa jadwal'}
                      </p>
                    </li>
                  ))}
                </ul>
              )}
            </Card>

            <Card title="Tugas Mendekati Deadline" actions={<SectionLink to={ROUTES.portal.tugas} />}>
              {data.assignments_due.length === 0 ? (
                <p className="text-sm text-ink-secondary">Tidak ada tugas yang mendesak.</p>
              ) : (
                <ul className="flex flex-col gap-3">
                  {data.assignments_due.map((assignment) => (
                    <li key={assignment.id}>
                      <Link to={ROUTES.portal.tugasDetail.replace(':id', assignment.id)} className="group block min-w-0">
                        <p className="truncate text-sm font-medium text-ink-primary group-hover:text-primary">{assignment.title}</p>
                        <p className="text-xs text-ink-secondary">
                          {assignment.course?.name} · {formatCountdown(assignment.due_at)}
                        </p>
                      </Link>
                    </li>
                  ))}
                </ul>
              )}
            </Card>
          </div>
        </div>

        <div className="flex flex-col gap-5">
          <Card title="KRS" description={academic.current_term?.label} actions={<SectionLink to={ROUTES.portal.krs} label="Buka KRS" />}>
            <div className="flex items-center justify-between gap-2">
              <KrsStatusBadge status={krs.status} label={krs.status_label} />
              <span className="text-sm text-ink-secondary">
                {krs.total_credits}
                {krs.max_credits ? ` / ${krs.max_credits}` : ''} SKS · {krs.course_count} MK
              </span>
            </div>
            {krs.is_open && krs.krs_end_date && (
              <p className="mt-2 text-xs text-ink-tertiary">Periode KRS dibuka sampai {formatDate(krs.krs_end_date)}.</p>
            )}
            {krs.status === 'rejected' && krs.decision_note && (
              <p className="mt-2 text-xs text-danger">Catatan dosen wali: {krs.decision_note}</p>
            )}
            {krs.is_open && ['not_started', 'draft', 'rejected'].includes(krs.status) && (
              <Link to={ROUTES.portal.krs} className="mt-3 block">
                <Button size="sm" className="w-full">
                  {krs.status === 'not_started' ? 'Ambil KRS' : 'Lanjutkan KRS'}
                </Button>
              </Link>
            )}
          </Card>

          <Card title="Presensi" actions={<SectionLink to={ROUTES.portal.absensi} />}>
            <div className="flex items-end justify-between">
              <p className="text-2xl font-semibold text-ink-primary">{formatPercent(data.attendance.overall.percentage)}</p>
              <p className="text-xs text-ink-tertiary">{data.attendance.overall.total_meetings} pertemuan tercatat</p>
            </div>
            <div className="mt-2">
              <ProgressBar
                value={data.attendance.overall.percentage ?? 0}
                tone={(data.attendance.overall.percentage ?? 100) < data.attendance.minimum_percent ? 'danger' : 'primary'}
              />
            </div>
            {data.attendance.below_minimum.length > 0 && (
              <p className="mt-2 text-xs text-danger">
                {data.attendance.below_minimum.length} mata kuliah di bawah batas minimum {formatPercent(data.attendance.minimum_percent)}.
              </p>
            )}
          </Card>

          <Card title="Nilai Terbaru" actions={<SectionLink to={ROUTES.portal.nilai} />}>
            {data.recent_grades.length === 0 ? (
              <p className="text-sm text-ink-secondary">Belum ada nilai baru.</p>
            ) : (
              <ul className="flex flex-col gap-2">
                {data.recent_grades.map((grade) => (
                  <li key={`${grade.course_code}-${grade.term_label}`} className="flex items-center justify-between gap-2 text-sm">
                    <span className="min-w-0 truncate text-ink-primary">{grade.course_name}</span>
                    <GradeBadge grade={grade.letter_grade} />
                  </li>
                ))}
              </ul>
            )}
          </Card>

          <Card
            title="Pengumuman"
            description={data.announcements.unread_count > 0 ? `${data.announcements.unread_count} belum dibaca` : undefined}
            actions={<SectionLink to={ROUTES.portal.pengumuman} />}
          >
            {data.announcements.latest.length === 0 ? (
              <p className="text-sm text-ink-secondary">Belum ada pengumuman.</p>
            ) : (
              <ul className="flex flex-col gap-2.5">
                {data.announcements.latest.map((announcement) => (
                  <li key={announcement.id} className="flex items-start gap-2">
                    <Megaphone className={cn('mt-0.5 size-3.5 shrink-0', announcement.is_read ? 'text-ink-tertiary' : 'text-primary')} aria-hidden />
                    <div className="min-w-0">
                      <p className={cn('truncate text-sm', announcement.is_read ? 'text-ink-secondary' : 'font-medium text-ink-primary')}>
                        {announcement.title}
                      </p>
                      <p className="text-xs text-ink-tertiary">{formatDate(announcement.published_at)}</p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </Card>

          <Card title="Dosen Wali">
            {academic.academic_advisor ? (
              <div className="text-sm">
                <p className="font-medium text-ink-primary">{academic.academic_advisor.name}</p>
                {academic.academic_advisor.email && <p className="text-ink-secondary">{academic.academic_advisor.email}</p>}
              </div>
            ) : (
              <p className="text-sm text-ink-secondary">Belum ditetapkan. Hubungi Bagian Akademik.</p>
            )}
          </Card>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 text-xs text-ink-tertiary">
        <span className="inline-flex items-center gap-1">
          <CalendarClock className="size-3.5" /> Data diperbarui setiap kali halaman dibuka.
        </span>
        <Link to={ROUTES.portal.kalender} className="inline-flex items-center gap-1 text-primary hover:underline">
          <ClipboardList className="size-3.5" /> Kalender akademik
        </Link>
        <Link to={ROUTES.portal.pengajuan} className="inline-flex items-center gap-1 text-primary hover:underline">
          <FileClock className="size-3.5" /> Pengajuan akademik
        </Link>
      </div>
    </div>
  )
}

export function PortalDashboardPage() {
  const fetchDashboard = useCallback(() => studentPortalService.dashboard(), [])
  const { data, isLoading, error, refetch } = useFetch(fetchDashboard)

  if (isLoading && !data) return <PortalLoading cards={4} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!data) return null

  return <DashboardContent data={data} />
}
