import { useCallback, useEffect, useState, type ReactNode } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  ArrowLeft,
  CheckCircle2,
  Clock,
  Download,
  Flag,
  Library,
  Loader2,
  MinusCircle,
  PencilLine,
  Play,
  Printer,
  ShieldAlert,
  XCircle,
} from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Select } from '@/components/ui/Select'
import { Alert } from '@/components/ui/Alert'
import { useFetch } from '@/hooks/useFetch'
import { usePrintLightTheme } from '@/hooks/usePrintLightTheme'
import { examAttemptReportService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type {
  ExamAttemptReportQuestion,
  ExamAttemptReportQuestionStatus,
  ExamAttemptReportTimelineEvent,
} from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { cn } from '@/utils/cn'

const QUESTION_STATUS_LABEL: Record<ExamAttemptReportQuestionStatus, string> = {
  correct: 'Benar',
  wrong: 'Salah',
  unanswered: 'Tidak Dijawab',
}

const QUESTION_STATUS_BADGE: Record<ExamAttemptReportQuestionStatus, BadgeVariant> = {
  correct: 'success',
  wrong: 'danger',
  unanswered: 'neutral',
}

const QUESTION_FILTERS: { value: ExamAttemptReportQuestionStatus | 'all'; label: string }[] = [
  { value: 'all', label: 'Semua' },
  { value: 'correct', label: 'Benar' },
  { value: 'wrong', label: 'Salah' },
  { value: 'unanswered', label: 'Tidak Dijawab' },
]

const TIMELINE_ICON: Record<ExamAttemptReportTimelineEvent['type'], ReactNode> = {
  started: <Play className="size-3.5" />,
  answered: <PencilLine className="size-3.5" />,
  violation: <ShieldAlert className="size-3.5" />,
  submitted: <Flag className="size-3.5" />,
}

/** Timezone universitas dari backend bisa berupa teks bebas — jatuh ke timezone browser kalau tidak valid. */
function resolveTimeZone(timeZone: string): string | undefined {
  try {
    new Intl.DateTimeFormat('id-ID', { timeZone })
    return timeZone
  } catch {
    return undefined
  }
}

function formatDuration(seconds: number | null): string {
  if (seconds === null) return '-'
  const pad = (value: number) => value.toString().padStart(2, '0')

  return `${pad(Math.floor(seconds / 3600))}:${pad(Math.floor((seconds % 3600) / 60))}:${pad(seconds % 60)}`
}

/**
 * "Detail Report" satu percobaan ujian untuk dosen (dibuka dari tabel
 * "Mahasiswa yang Dapat Mengikuti Ujian" di ExamDetailPage) — waktu
 * pengerjaan, jawaban per soal, pelanggaran, dan linimasa aktivitas. Bisa
 * dicetak langsung dari browser (layout shell disembunyikan saat print) atau
 * diunduh sebagai PDF yang dirender backend (ExamAttemptController::reportPdf).
 */
export function ExamAttemptReportPage() {
  const { id: examId = '', attemptId = '' } = useParams<{ id: string; attemptId: string }>()
  const navigate = useNavigate()
  usePrintLightTheme()

  const getReport = useCallback(() => examAttemptReportService.show(attemptId), [attemptId])
  const { data: report, error, refetch } = useFetch(getReport)

  const [questionFilter, setQuestionFilter] = useState<ExamAttemptReportQuestionStatus | 'all'>('all')
  const [isDownloading, setIsDownloading] = useState(false)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  // Selama peserta masih mengerjakan, laporan di-refresh berkala (polling,
  // sama seperti tabel peserta di ExamDetailPage) supaya dosen bisa memantau.
  const isInProgress = report?.attempt.status === 'in_progress'
  useEffect(() => {
    if (!isInProgress) return

    const interval = setInterval(refetch, 10000)
    return () => clearInterval(interval)
  }, [isInProgress, refetch])

  const examDetailPath = ROUTES.akademik.ujianDetail.replace(':id', examId)

  const handleDownload = async () => {
    if (!report) return
    setIsDownloading(true)
    setDownloadError(null)

    try {
      const slug = report.exam.title.replace(/\s+/g, '-').toLowerCase()
      await examAttemptReportService.downloadPdf(
        attemptId,
        `laporan-ujian-${slug}-${report.student.nim}-percobaan-${report.attempt.attempt_number}.pdf`,
      )
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message ?? 'Gagal mengunduh PDF laporan ujian.')
    } finally {
      setIsDownloading(false)
    }
  }

  if (!report) {
    return (
      <div className="flex flex-col gap-4">
        {error ? (
          <>
            <Alert variant="danger">{error}</Alert>
            <div>
              <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={() => navigate(examDetailPath)}>
                Kembali ke Detail Ujian
              </Button>
            </div>
          </>
        ) : (
          <div className="flex min-h-[50vh] items-center justify-center">
            <Loader2 className="size-6 animate-spin text-ink-tertiary" />
          </div>
        )}
      </div>
    )
  }

  const timeZone = resolveTimeZone(report.timezone)
  const formatDateTime = (iso: string | null) =>
    iso
      ? new Date(iso).toLocaleString('id-ID', { timeZone, dateStyle: 'medium', timeStyle: 'medium' }) +
        ` ${timeZoneAbbreviation(iso, timeZone)}`
      : '-'
  const formatTime = (iso: string | null) =>
    iso ? new Date(iso).toLocaleTimeString('id-ID', { timeZone, hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-'

  const { exam, student, attempt, summary } = report
  const timeUsagePercent = attempt.allowed_seconds > 0 ? Math.min(100, (attempt.duration_seconds / attempt.allowed_seconds) * 100) : 0

  return (
    <div className="flex flex-col gap-5 print:gap-3">
      <div className="print:hidden">
        <PageHeader
          title="Laporan Pengerjaan Ujian"
          description={`${student.name} (${student.nim}) — ${exam.title}`}
          breadcrumb={[
            { label: 'Dashboard', path: ROUTES.dashboard },
            { label: 'Ujian', path: ROUTES.akademik.ujian },
            { label: 'Detail', path: examDetailPath },
            { label: 'Laporan Mahasiswa' },
          ]}
          actions={
            <div className="flex flex-wrap gap-2">
              <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={() => navigate(examDetailPath)}>
                Kembali
              </Button>
              <Button variant="outline" leftIcon={<Printer className="size-4" />} onClick={() => window.print()}>
                Cetak
              </Button>
              <Button leftIcon={<Download className="size-4" />} isLoading={isDownloading} onClick={handleDownload}>
                Download PDF
              </Button>
            </div>
          }
        />
      </div>

      {/* Kop khusus cetak — PageHeader (breadcrumb + tombol) disembunyikan saat print. */}
      <div className="hidden border-b border-border-strong pb-2 print:block">
        <h1 className="text-lg font-bold tracking-wide text-ink-primary">LAPORAN PENGERJAAN UJIAN</h1>
        <p className="text-sm text-ink-secondary">
          {exam.title} — {exam.course_name ?? '-'}
        </p>
      </div>

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)} className="print:hidden">
          {downloadError}
        </Alert>
      )}

      {report.attempts.length > 1 && (
        <div className="flex flex-wrap items-end gap-3 print:hidden">
          <div className="w-full sm:w-72">
            <Select
              label="Percobaan"
              value={attempt.id}
              onChange={(event) =>
                navigate(ROUTES.akademik.ujianLaporan.replace(':id', examId).replace(':attemptId', event.target.value))
              }
              options={report.attempts.map((a) => ({
                value: a.id,
                label: `Percobaan ke-${a.attempt_number} — ${a.status === 'submitted' ? `Nilai ${a.score ?? '-'}` : 'Sedang dikerjakan'}`,
              }))}
            />
          </div>
        </div>
      )}

      {isInProgress && (
        <Alert variant="warning" className="print:hidden">
          Mahasiswa masih mengerjakan ujian ini — laporan diperbarui otomatis setiap 10 detik. Nilai akhir baru tersedia
          setelah ujian dikumpulkan.
        </Alert>
      )}

      <div className="grid gap-5 lg:grid-cols-2 print:grid-cols-2 print:gap-3">
        <Card title="Identitas Mahasiswa" className="print:shadow-none">
          <dl className="grid grid-cols-2 gap-3 text-sm">
            <InfoItem label="Nama" value={student.name} />
            <InfoItem label="NIM" value={student.nim} />
            <InfoItem label="Program Studi" value={student.study_program ?? '-'} />
            <InfoItem label="Percobaan" value={`Ke-${attempt.attempt_number} dari maks. ${exam.max_attempts}`} />
          </dl>
        </Card>
        <Card title="Informasi Ujian" className="print:shadow-none">
          <dl className="grid grid-cols-2 gap-3 text-sm">
            <InfoItem
              label="Mata Kuliah"
              value={`${exam.course_code ? `${exam.course_code} — ` : ''}${exam.course_name ?? '-'}`}
            />
            <InfoItem label="Kelas" value={exam.class_code ?? '-'} />
            <InfoItem label="Semester" value={exam.academic_term ?? '-'} />
            <InfoItem label="Dosen Pengampu" value={exam.lecturer_name ?? '-'} />
          </dl>
        </Card>
      </div>

      <Card title="Waktu Pengerjaan" className="print:shadow-none">
        <dl className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
          <InfoItem label="Mulai Mengerjakan" value={formatDateTime(attempt.started_at)} />
          <InfoItem label="Selesai / Dikumpulkan" value={formatDateTime(attempt.submitted_at)} />
          <InfoItem label="Batas Waktu Peserta" value={formatDateTime(attempt.deadline_at)} />
          <InfoItem
            label="Status"
            value={
              attempt.status === 'submitted' ? (
                <Badge variant="success">
                  {attempt.submission_type === 'auto_timeout' ? 'Dikumpulkan otomatis (waktu habis)' : 'Dikumpulkan peserta'}
                </Badge>
              ) : (
                <Badge variant="warning">Sedang Mengerjakan</Badge>
              )
            }
          />
          <InfoItem
            label="Jadwal Ujian"
            value={`${formatDateTime(exam.starts_at)} s.d. ${formatDateTime(exam.ends_at)}`}
            className="col-span-2"
          />
          <InfoItem label="Durasi Ujian" value={`${exam.duration_minutes} menit`} />
          <InfoItem label="Durasi Pengerjaan" value={formatDuration(attempt.duration_seconds)} />
        </dl>

        <div className="mt-4">
          <div className="mb-1 flex justify-between text-xs text-ink-tertiary">
            <span>Pemakaian waktu</span>
            <span>
              {formatDuration(attempt.duration_seconds)} / {formatDuration(attempt.allowed_seconds)} (
              {timeUsagePercent.toFixed(0)}%)
            </span>
          </div>
          <div className="h-2 overflow-hidden rounded-full bg-surface-hover">
            <div className="h-full rounded-full bg-primary" style={{ width: `${timeUsagePercent}%` }} />
          </div>
        </div>
      </Card>

      <Card title="Ringkasan Nilai" className="print:shadow-none">
        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 print:grid-cols-6">
          <StatTile label="Jumlah Soal" value={summary.total_questions} />
          <StatTile label="Dijawab" value={summary.answered} />
          <StatTile label="Benar" value={summary.correct_answers} tone="success" />
          <StatTile label="Salah" value={summary.wrong_answers} tone="danger" />
          <StatTile label="Tidak Dijawab" value={summary.unanswered} />
          <StatTile label="Poin" value={`${summary.earned_points} / ${summary.total_points}`} />
          <StatTile label="Raw Score" value={summary.raw_score ?? '-'} />
          <StatTile
            label={`Penalti (${summary.violation_count}x)`}
            value={Number(summary.penalty_score) > 0 ? `-${summary.penalty_score}` : '0.00'}
            tone={Number(summary.penalty_score) > 0 ? 'danger' : undefined}
          />
          <StatTile label="Final Score" value={summary.score ?? '-'} tone="primary" />
          <StatTile label="Grade" value={summary.grade ?? '-'} />
          <StatTile
            label={`Weighted Score${exam.weight_percentage !== null ? ` (${exam.weight_percentage}%)` : ''}`}
            value={summary.weighted_score ?? '-'}
          />
        </div>
      </Card>

      <Card
        title="Lembar Jawaban"
        description="Ringkasan jawaban per nomor — klik nomor untuk melihat detail soal."
        className="print:break-inside-avoid print:shadow-none"
      >
        <div className="grid grid-cols-5 gap-2 sm:grid-cols-10">
          {report.questions.map((question) => (
            <a
              key={question.question_id}
              href={`#soal-${question.number}`}
              title={`Soal ${question.number}: ${QUESTION_STATUS_LABEL[question.status]}`}
              className={cn(
                'flex flex-col items-center rounded-lg border px-1 py-1.5 text-xs font-medium',
                question.status === 'correct' && 'border-success/50 bg-success/10 text-success',
                question.status === 'wrong' && 'border-danger/50 bg-danger/10 text-danger',
                question.status === 'unanswered' && 'border-border-strong text-ink-tertiary',
              )}
            >
              <span className="text-[10px] text-ink-tertiary">{question.number}</span>
              <span className="text-sm font-semibold">{question.selected_option_label ?? '–'}</span>
            </a>
          ))}
        </div>
      </Card>

      <Card
        title={
          <span className="flex items-center gap-2">
            Pelanggaran
            {summary.violation_count > 0 && <Badge variant="danger">{summary.violation_count}x</Badge>}
          </span>
        }
        description="Pelanggaran anti-cheat yang terdeteksi selama ujian, masing-masing dikenai penalti."
        className="print:shadow-none"
      >
        {report.violations.length === 0 ? (
          <p className="text-sm text-ink-tertiary">Tidak ada pelanggaran tercatat pada percobaan ini.</p>
        ) : (
          <div className="flex flex-col gap-4">
            <div className="flex flex-wrap gap-2">
              {report.violation_breakdown.map((row) => (
                <span
                  key={row.violation_type}
                  className="inline-flex items-center gap-1.5 rounded-lg border border-danger/30 bg-danger/5 px-2.5 py-1 text-xs text-ink-secondary"
                >
                  <ShieldAlert className="size-3.5 text-danger" />
                  {row.violation_label}
                  <span className="font-semibold text-danger">
                    {row.count}x · -{row.penalty_points}
                  </span>
                </span>
              ))}
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead>
                  <tr className="border-b border-border text-xs uppercase tracking-wide text-ink-tertiary">
                    <th className="py-2 pr-3 font-medium">#</th>
                    <th className="py-2 pr-3 font-medium">Jenis</th>
                    <th className="py-2 pr-3 font-medium">Waktu Kejadian</th>
                    <th className="py-2 pr-3 font-medium">Menit ke-</th>
                    <th className="py-2 font-medium">Penalti</th>
                  </tr>
                </thead>
                <tbody>
                  {report.violations.map((violation) => (
                    <tr key={violation.id} className="border-b border-border last:border-0">
                      <td className="py-2 pr-3">{violation.sequence_number}</td>
                      <td className="py-2 pr-3">{violation.violation_label}</td>
                      <td className="py-2 pr-3 text-ink-secondary">{formatDateTime(violation.occurred_at)}</td>
                      <td className="py-2 pr-3 font-mono text-xs">{formatDuration(violation.occurred_after_seconds)}</td>
                      <td className="py-2 font-medium text-danger">-{violation.penalty_points}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}
      </Card>

      <Card
        title="Linimasa Aktivitas"
        description="Urutan kejadian selama pengerjaan. Waktu jawaban adalah saat jawaban terakhir disimpan."
        className="print:shadow-none"
      >
        <ol className="relative flex flex-col gap-3 border-l border-border-strong pl-5">
          {report.timeline.map((event, index) => (
            <li key={`${event.type}-${event.occurred_at}-${index}`} className="relative print:break-inside-avoid">
              <span
                className={cn(
                  'absolute -left-[31px] flex size-5 items-center justify-center rounded-full border bg-surface',
                  event.type === 'violation' ? 'border-danger/50 text-danger' : 'border-primary/50 text-primary',
                )}
              >
                {TIMELINE_ICON[event.type]}
              </span>
              <p className={cn('text-sm', event.type === 'violation' ? 'font-medium text-danger' : 'text-ink-primary')}>
                {event.description}
              </p>
              <p className="text-xs text-ink-tertiary">
                {formatTime(event.occurred_at)} · menit ke-{formatDuration(event.elapsed_seconds)}
              </p>
            </li>
          ))}
        </ol>
      </Card>

      <div className="flex flex-col gap-3 print:break-before-page">
        <div className="flex flex-wrap items-center justify-between gap-2">
          <h2 className="text-sm font-semibold text-ink-primary">Detail Jawaban per Soal</h2>
          <div className="flex flex-wrap gap-1.5 print:hidden">
            {QUESTION_FILTERS.map((filter) => (
              <Button
                key={filter.value}
                size="sm"
                variant={questionFilter === filter.value ? 'primary' : 'outline'}
                onClick={() => setQuestionFilter(filter.value)}
              >
                {filter.label}
              </Button>
            ))}
          </div>
        </div>

        {report.questions.map((question) => (
          <QuestionReportCard
            key={question.question_id}
            question={question}
            answeredAtLabel={formatTime(question.answered_at)}
            // Filter hanya untuk layar — saat dicetak semua soal selalu ikut.
            hiddenOnScreen={questionFilter !== 'all' && question.status !== questionFilter}
          />
        ))}
      </div>

      <div className="hidden justify-end pt-6 text-sm text-ink-primary print:flex print:break-inside-avoid">
        <div className="w-56">
          <p>Dosen Pengampu,</p>
          <div className="h-16" />
          <p className="font-semibold">{exam.lecturer_name ?? '..............................'}</p>
        </div>
      </div>

      <p className="hidden text-[10px] text-ink-tertiary print:block">
        Dicetak {formatDateTime(report.generated_at)} · {student.nim} — {exam.title}
      </p>
    </div>
  )
}

/** Singkatan zona waktu (WIB/WITA/WIT/…) untuk ditempel di belakang tanggal. */
function timeZoneAbbreviation(iso: string, timeZone: string | undefined): string {
  return (
    new Intl.DateTimeFormat('id-ID', { timeZone, timeZoneName: 'short' })
      .formatToParts(new Date(iso))
      .find((part) => part.type === 'timeZoneName')?.value ?? ''
  )
}

function InfoItem({ label, value, className }: { label: string; value: ReactNode; className?: string }) {
  return (
    <div className={className}>
      <dt className="text-ink-tertiary">{label}</dt>
      <dd className="mt-0.5 font-medium text-ink-primary">{value}</dd>
    </div>
  )
}

function StatTile({
  label,
  value,
  tone,
}: {
  label: string
  value: ReactNode
  tone?: 'success' | 'danger' | 'primary'
}) {
  return (
    <div
      className={cn(
        'rounded-lg border p-3 text-center',
        tone === 'success' && 'border-success/40 bg-success/5',
        tone === 'danger' && 'border-danger/40 bg-danger/5',
        tone === 'primary' && 'border-primary/40 bg-primary/5',
        !tone && 'border-border-strong',
      )}
    >
      <p
        className={cn(
          'text-lg font-semibold',
          tone === 'success' && 'text-success',
          tone === 'danger' && 'text-danger',
          tone === 'primary' && 'text-primary',
          !tone && 'text-ink-primary',
        )}
      >
        {value}
      </p>
      <p className="text-xs text-ink-tertiary">{label}</p>
    </div>
  )
}

function QuestionReportCard({
  question,
  answeredAtLabel,
  hiddenOnScreen,
}: {
  question: ExamAttemptReportQuestion
  answeredAtLabel: string
  hiddenOnScreen: boolean
}) {
  const StatusIcon = { correct: CheckCircle2, wrong: XCircle, unanswered: MinusCircle }[question.status]

  return (
    <Card
      id={`soal-${question.number}`}
      className={cn(
        'scroll-mt-20 border-l-4 print:break-inside-avoid print:shadow-none',
        question.status === 'correct' && 'border-l-success',
        question.status === 'wrong' && 'border-l-danger',
        question.status === 'unanswered' && 'border-l-border-strong',
        hiddenOnScreen && 'hidden print:block',
      )}
    >
      <div className="flex flex-col gap-3">
        <div className="flex flex-wrap items-start justify-between gap-2">
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-ink-tertiary">
            <span className="text-sm font-semibold text-ink-primary">Soal {question.number}</span>
            <span>
              {question.earned_points} / {question.points} poin
            </span>
            {question.answered_at && (
              <span className="inline-flex items-center gap-1">
                <Clock className="size-3" />
                Disimpan {answeredAtLabel} · menit ke-{formatDuration(question.answered_after_seconds)}
              </span>
            )}
            {question.from_question_bank && (
              <span className="inline-flex items-center gap-1">
                <Library className="size-3" />
                Bank Soal
              </span>
            )}
          </div>
          <Badge variant={QUESTION_STATUS_BADGE[question.status]}>
            <StatusIcon className="size-3.5" />
            {QUESTION_STATUS_LABEL[question.status]}
          </Badge>
        </div>

        <p className="whitespace-pre-wrap text-sm font-medium text-ink-primary">
          {question.question_text ?? '(Soal tidak lagi tersedia)'}
        </p>

        <div className="flex flex-col gap-1.5">
          {question.options.map((option) => {
            const isSelected = option.id === question.selected_option_id

            return (
              <div
                key={option.id}
                className={cn(
                  'flex items-center justify-between gap-2 rounded-lg border p-2 text-sm',
                  option.is_correct
                    ? 'border-success/50 bg-success/10 text-ink-primary'
                    : isSelected
                      ? 'border-danger/50 bg-danger/10 text-ink-primary'
                      : 'border-border-strong text-ink-secondary',
                )}
              >
                <span className="flex items-start gap-2">
                  <span className="font-semibold">{option.label}.</span>
                  <span>{option.option_text}</span>
                </span>
                <span className="flex shrink-0 gap-1.5">
                  {isSelected && <Badge variant={option.is_correct ? 'success' : 'danger'}>Jawaban Mahasiswa</Badge>}
                  {option.is_correct && <Badge variant="success">Kunci Jawaban</Badge>}
                </span>
              </div>
            )
          })}
          {question.selected_option_id === null && (
            <p className="text-xs italic text-ink-tertiary">Mahasiswa tidak menjawab soal ini.</p>
          )}
        </div>
      </div>
    </Card>
  )
}

