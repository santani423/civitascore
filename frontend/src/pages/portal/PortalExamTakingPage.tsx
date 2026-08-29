import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { AlertTriangle, ArrowLeft, CheckCircle2, Clock, Loader2, Maximize, ShieldAlert } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { studentExamService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { StudentExam, StudentExamAttempt } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { cn } from '@/utils/cn'

const WARNING_THRESHOLD_MS = 5 * 60_000
const DANGER_THRESHOLD_MS = 60_000
const VIOLATION_DEBOUNCE_MS = 1000

function formatCountdown(ms: number): string {
  const totalSeconds = Math.floor(ms / 1000)
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  const pad = (value: number) => value.toString().padStart(2, '0')

  return hours > 0 ? `${pad(hours)}:${pad(minutes)}:${pad(seconds)}` : `${pad(minutes)}:${pad(seconds)}`
}

/**
 * Batas waktu efektif percobaan ini di sisi klien — mirror dari
 * ExamService::attemptDeadline() di backend (mana yang lebih dulu antara
 * habisnya durasi dan jadwal berakhir ujian). Ini HANYA untuk tampilan
 * countdown/auto-submit UX; backend tetap satu-satunya sumber kebenaran
 * waktu lewat finalizeIfExpired() di setiap panggilan answer/submit/show.
 */
function computeDeadlineMs(exam: StudentExam, attempt: StudentExamAttempt): number {
  const startedAtMs = new Date(attempt.started_at).getTime()
  const durationDeadlineMs = startedAtMs + exam.duration_minutes * 60_000
  const examEndsAtMs = exam.ends_at ? new Date(exam.ends_at).getTime() : null

  return examEndsAtMs !== null && examEndsAtMs < durationDeadlineMs ? examEndsAtMs : durationDeadlineMs
}

/**
 * Kombinasi shortcut devtools yang paling umum dipakai (Win/Linux Chrome:
 * Ctrl+Shift+I/J/C, Ctrl+U; Mac Chrome: Cmd+Option+I/J/C, Cmd+Option+U; F12
 * di semua browser Chromium). Ini HANYA penghalang ringan — lihat komentar
 * besar di atas komponen soal peringatan tentang batasannya.
 */
function isDevToolsShortcut(event: KeyboardEvent): boolean {
  const key = event.key.toLowerCase()

  if (key === 'f12') return true
  if ((event.ctrlKey || event.metaKey) && (event.shiftKey || event.altKey) && ['i', 'j', 'c'].includes(key)) return true
  if ((event.ctrlKey || event.metaKey) && key === 'u') return true

  return false
}

async function tryRequestFullscreen(): Promise<void> {
  try {
    if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
      await document.documentElement.requestFullscreen()
    }
  } catch {
    // Fullscreen ditolak (mis. browser/embed tidak mengizinkan, atau bukan
    // dipicu dari gesture pengguna) — ujian tetap bisa dikerjakan tanpa
    // fullscreen, ini murni enhancement, bukan syarat wajib.
  }
}

async function tryExitFullscreen(): Promise<void> {
  try {
    if (document.fullscreenElement) {
      await document.exitFullscreen()
    }
  } catch {
    // no-op
  }
}

export function PortalExamTakingPage() {
  const { id: examId } = useParams<{ id: string }>()
  const navigate = useNavigate()

  const [exam, setExam] = useState<StudentExam | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)

  const [attempt, setAttempt] = useState<StudentExamAttempt | null>(null)
  const [attemptError, setAttemptError] = useState<string | null>(null)

  const [examModeEntered, setExamModeEntered] = useState(false)
  const [currentIndex, setCurrentIndex] = useState(0)
  const [savingQuestionId, setSavingQuestionId] = useState<string | null>(null)
  const [saveError, setSaveError] = useState<string | null>(null)
  const [showSubmitConfirm, setShowSubmitConfirm] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [nowMs, setNowMs] = useState(() => Date.now())

  const [violationCount, setViolationCount] = useState(0)
  const [violationMessage, setViolationMessage] = useState<string | null>(null)
  const [isFullscreenActive, setIsFullscreenActive] = useState(() => Boolean(document.fullscreenElement))

  const autoSubmitTriggered = useRef(false)
  const lastViolationAtRef = useRef(0)

  const isActive = examModeEntered && attempt?.status === 'in_progress'

  useEffect(() => {
    if (!examId) return
    let cancelled = false
    setIsLoading(true)
    setLoadError(null)

    studentExamService
      .show(examId)
      .then(async (data) => {
        if (cancelled) return
        setExam(data)

        if (data.status !== 'available' && data.status !== 'in_progress') return

        try {
          const startedAttempt = await studentExamService.start(examId)
          if (!cancelled) setAttempt(startedAttempt)
        } catch (err) {
          if (!cancelled) setAttemptError((err as NormalizedApiError).message)
        }
      })
      .catch((err: NormalizedApiError) => {
        if (!cancelled) setLoadError(err.message)
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [examId])

  useEffect(() => {
    if (attempt?.status !== 'in_progress') return

    const interval = setInterval(() => setNowMs(Date.now()), 1000)
    return () => clearInterval(interval)
  }, [attempt?.status])

  useEffect(() => {
    if (attempt?.status !== 'in_progress') return

    function handleBeforeUnload(event: BeforeUnloadEvent) {
      event.preventDefault()
      event.returnValue = ''
    }

    window.addEventListener('beforeunload', handleBeforeUnload)
    return () => window.removeEventListener('beforeunload', handleBeforeUnload)
  }, [attempt?.status])

  const reportViolation = useCallback((message: string) => {
    const now = Date.now()
    if (now - lastViolationAtRef.current < VIOLATION_DEBOUNCE_MS) return
    lastViolationAtRef.current = now

    setViolationCount((count) => count + 1)
    setViolationMessage(message)
  }, [])

  // Deteksi (bukan mencegah — lihat catatan di atas komponen) mahasiswa
  // pindah tab/aplikasi lain selama ujian berlangsung.
  useEffect(() => {
    if (!isActive) return

    function handleVisibilityChange() {
      if (document.visibilityState === 'hidden') {
        reportViolation('Anda terdeteksi berpindah ke tab/aplikasi lain.')
      }
    }

    function handleBlur() {
      reportViolation('Jendela ujian kehilangan fokus.')
    }

    document.addEventListener('visibilitychange', handleVisibilityChange)
    window.addEventListener('blur', handleBlur)
    return () => {
      document.removeEventListener('visibilitychange', handleVisibilityChange)
      window.removeEventListener('blur', handleBlur)
    }
  }, [isActive, reportViolation])

  useEffect(() => {
    function handleFullscreenChange() {
      const active = Boolean(document.fullscreenElement)
      setIsFullscreenActive(active)

      if (!active && isActive) {
        reportViolation('Anda keluar dari mode layar penuh.')
      }
    }

    document.addEventListener('fullscreenchange', handleFullscreenChange)
    return () => document.removeEventListener('fullscreenchange', handleFullscreenChange)
  }, [isActive, reportViolation])

  // Penghalang ringan terhadap devtools/klik-kanan — TIDAK benar-benar
  // mencegah pengguna yang tahu cara lain untuk membukanya (mis. lewat menu
  // browser, browser lain, atau devtools yang sudah terbuka sebelum masuk
  // halaman ini). Screenshot OS-level sama sekali tidak bisa diblokir dari
  // halaman web — tidak ada API browser untuk itu.
  useEffect(() => {
    if (!isActive) return

    function handleKeyDown(event: KeyboardEvent) {
      if (isDevToolsShortcut(event)) {
        event.preventDefault()
        reportViolation('Percobaan membuka developer tools terdeteksi.')
      }
    }

    function handleContextMenu(event: MouseEvent) {
      event.preventDefault()
    }

    window.addEventListener('keydown', handleKeyDown)
    window.addEventListener('contextmenu', handleContextMenu)
    return () => {
      window.removeEventListener('keydown', handleKeyDown)
      window.removeEventListener('contextmenu', handleContextMenu)
    }
  }, [isActive, reportViolation])

  useEffect(() => {
    return () => {
      tryExitFullscreen()
    }
  }, [])

  const deadlineMs = useMemo(() => {
    if (!exam || !attempt) return null
    return computeDeadlineMs(exam, attempt)
  }, [exam, attempt])

  const remainingMs = deadlineMs !== null ? Math.max(0, deadlineMs - nowMs) : null

  const handleSubmit = useCallback(async () => {
    if (!attempt) return

    setIsSubmitting(true)
    setSaveError(null)

    try {
      const result = await studentExamService.submit(attempt.id)
      setAttempt(result)
      await tryExitFullscreen()
    } catch (err) {
      setSaveError((err as NormalizedApiError).message ?? 'Gagal mengumpulkan ujian.')
    } finally {
      setIsSubmitting(false)
      setShowSubmitConfirm(false)
    }
  }, [attempt])

  // Auto-submit begitu waktu habis — backend tetap otoritatif (lihat
  // finalizeIfExpired), ini murni supaya UI langsung pindah ke hasil tanpa
  // menunggu peserta melakukan aksi lain.
  useEffect(() => {
    if (remainingMs === null || remainingMs > 0) return
    if (attempt?.status !== 'in_progress') return
    if (autoSubmitTriggered.current) return

    autoSubmitTriggered.current = true
    handleSubmit()
  }, [remainingMs, attempt?.status, handleSubmit])

  const handleSelectOption = async (questionId: string, optionId: string) => {
    if (!attempt || attempt.status !== 'in_progress') return

    setAttempt((prev) =>
      prev
        ? {
            ...prev,
            questions: prev.questions.map((q) => (q.id === questionId ? { ...q, selected_option_id: optionId } : q)),
          }
        : prev,
    )
    setSavingQuestionId(questionId)
    setSaveError(null)

    try {
      const updated = await studentExamService.answer(attempt.id, {
        exam_question_id: questionId,
        exam_question_option_id: optionId,
      })
      setAttempt(updated)
    } catch (err) {
      setSaveError((err as NormalizedApiError).message ?? 'Gagal menyimpan jawaban.')
    } finally {
      setSavingQuestionId(null)
    }
  }

  const handleExit = async () => {
    if (attempt?.status === 'in_progress') {
      const confirmed = window.confirm(
        'Ujian belum selesai. Yakin ingin meninggalkan halaman ini? Jawaban yang sudah tersimpan tetap aman, tapi pengerjaan akan terhenti.',
      )
      if (!confirmed) return
    }

    await tryExitFullscreen()
    navigate(ROUTES.portal.ujian)
  }

  const canNavigateTo = (index: number) => (exam ? exam.allow_back_navigation : true) || index >= currentIndex

  const pageShell = (children: ReactNode) => (
    <div className="min-h-screen bg-background px-4 py-6 sm:px-6">
      <div className="mx-auto flex max-w-3xl flex-col gap-4">{children}</div>
    </div>
  )

  if (isLoading) {
    return pageShell(
      <div className="flex min-h-[50vh] items-center justify-center">
        <Loader2 className="size-6 animate-spin text-ink-tertiary" />
      </div>,
    )
  }

  if (loadError || !exam) {
    return pageShell(
      <>
        <Alert variant="danger">{loadError ?? 'Ujian tidak ditemukan.'}</Alert>
        <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={() => navigate(ROUTES.portal.ujian)}>
          Kembali ke Daftar Ujian
        </Button>
      </>,
    )
  }

  // Ujian belum bisa dikerjakan (upcoming/expired) atau sudah tuntas tanpa
  // sisa percobaan (completed) — status & nilai dari sudut pandang peserta
  // sudah lengkap di `exam`, tidak perlu memulai attempt sama sekali.
  if (exam.status !== 'available' && exam.status !== 'in_progress') {
    return pageShell(<ExamStatusCard exam={exam} onBack={() => navigate(ROUTES.portal.ujian)} />)
  }

  if (attemptError) {
    return pageShell(
      <>
        <Alert variant="danger">{attemptError}</Alert>
        <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={() => navigate(ROUTES.portal.ujian)}>
          Kembali ke Daftar Ujian
        </Button>
      </>,
    )
  }

  if (!attempt) {
    return pageShell(
      <div className="flex min-h-[50vh] items-center justify-center">
        <Loader2 className="size-6 animate-spin text-ink-tertiary" />
      </div>,
    )
  }

  if (attempt.status === 'submitted') {
    return pageShell(
      <Card>
        <div className="flex flex-col items-center gap-3 py-10 text-center">
          <CheckCircle2 className="size-12 text-success" />
          <p className="text-lg font-semibold text-ink-primary">Ujian Berhasil Dikumpulkan</p>
          <p className="text-sm text-ink-tertiary">{exam.title}</p>

          {attempt.result_visible ? (
            <p className="text-4xl font-bold text-primary">{attempt.score}</p>
          ) : (
            <Badge variant="warning">Waiting for Result</Badge>
          )}

          <Button className="mt-2" variant="outline" onClick={() => navigate(ROUTES.portal.ujian)}>
            Kembali ke Daftar Ujian
          </Button>
        </div>
      </Card>,
    )
  }

  if (!examModeEntered) {
    return pageShell(
      <ExamModeGate
        exam={exam}
        hasProgress={attempt.questions.some((q) => q.selected_option_id !== null)}
        onStart={async () => {
          await tryRequestFullscreen()
          setExamModeEntered(true)
        }}
        onBack={() => navigate(ROUTES.portal.ujian)}
      />,
    )
  }

  const questions = attempt.questions
  const currentQuestion = questions[currentIndex]
  const answeredCount = questions.filter((q) => q.selected_option_id !== null).length
  const isWarning = remainingMs !== null && remainingMs <= WARNING_THRESHOLD_MS
  const isDanger = remainingMs !== null && remainingMs <= DANGER_THRESHOLD_MS

  return pageShell(
    <div className="flex select-none flex-col gap-4" onCopy={(event) => event.preventDefault()}>
      <div className="sticky top-0 z-10 flex flex-col gap-3 rounded-xl border border-border bg-background/95 px-4 py-3 backdrop-blur">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="truncate font-semibold text-ink-primary">{exam.title}</p>
            <p className="truncate text-xs text-ink-tertiary">{exam.course_name ?? '-'}</p>
          </div>

          <div className="flex items-center gap-2">
            {violationCount > 0 && (
              <div
                className="flex items-center gap-1.5 rounded-lg border border-danger/40 bg-danger/10 px-2.5 py-1.5 text-xs font-medium text-danger"
                title="Jumlah aktivitas mencurigakan yang terdeteksi selama ujian ini"
              >
                <ShieldAlert className="size-3.5" />
                {violationCount}
              </div>
            )}

            <div
              className={cn(
                'flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-semibold tabular-nums',
                isDanger
                  ? 'border-danger/40 bg-danger/10 text-danger'
                  : isWarning
                    ? 'border-warning/40 bg-warning/10 text-warning'
                    : 'border-border-strong text-ink-primary',
              )}
            >
              <Clock className="size-4" />
              {remainingMs !== null ? formatCountdown(remainingMs) : '-'}
            </div>

            <Button variant="outline" size="sm" onClick={handleExit}>
              Keluar
            </Button>
          </div>
        </div>

        {isWarning && (
          <Alert variant={isDanger ? 'danger' : 'warning'} className="py-2">
            {isDanger ? 'Waktu hampir habis! Ujian akan dikumpulkan otomatis.' : 'Waktu ujian hampir habis.'}
          </Alert>
        )}

        {!isFullscreenActive && (
          // Alert bukan dipakai di sini — children-nya selalu dibungkus <p>,
          // jadi tidak valid menaruh <Button> (elemen block) di dalamnya.
          <div className="flex items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <div className="flex items-center gap-2">
              <AlertTriangle className="size-4 shrink-0" aria-hidden />
              <span>Anda tidak dalam mode layar penuh.</span>
            </div>
            <Button size="sm" variant="outline" leftIcon={<Maximize className="size-3.5" />} onClick={tryRequestFullscreen}>
              Aktifkan Layar Penuh
            </Button>
          </div>
        )}

        <div className="flex flex-wrap gap-1.5">
          {questions.map((q, index) => {
            const isAnswered = q.selected_option_id !== null
            const isCurrent = index === currentIndex
            const navigable = canNavigateTo(index)

            return (
              <button
                key={q.id}
                type="button"
                disabled={!navigable}
                onClick={() => setCurrentIndex(index)}
                title={isAnswered ? 'Sudah dijawab' : 'Belum dijawab'}
                className={cn(
                  'flex size-8 items-center justify-center rounded-lg border text-xs font-medium transition-colors',
                  isCurrent && 'ring-2 ring-primary ring-offset-1 ring-offset-background',
                  isAnswered
                    ? 'border-success/40 bg-success/10 text-success'
                    : 'border-border-strong bg-transparent text-ink-secondary',
                  !navigable && 'cursor-not-allowed opacity-40',
                )}
              >
                {index + 1}
              </button>
            )
          })}
        </div>

        <p className="text-xs text-ink-tertiary">
          {answeredCount} dari {questions.length} soal terjawab
        </p>
      </div>

      {violationMessage && (
        <Alert variant="warning" onDismiss={() => setViolationMessage(null)}>
          {violationMessage} Aktivitas ini tercatat (ke-{violationCount}).
        </Alert>
      )}

      {saveError && (
        <Alert variant="danger" onDismiss={() => setSaveError(null)}>
          {saveError}
        </Alert>
      )}

      {currentQuestion && (
        <Card>
          <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between gap-2">
              <p className="text-sm font-medium text-ink-tertiary">
                Soal {currentIndex + 1} dari {questions.length}
              </p>
              {savingQuestionId === currentQuestion.id && (
                <span className="flex items-center gap-1 text-xs text-ink-tertiary">
                  <Loader2 className="size-3 animate-spin" /> Menyimpan...
                </span>
              )}
            </div>

            <p className="whitespace-pre-wrap text-base font-medium text-ink-primary">{currentQuestion.question_text}</p>

            <div className="flex flex-col gap-2">
              {currentQuestion.options.map((option) => {
                const isSelected = currentQuestion.selected_option_id === option.id

                return (
                  <button
                    key={option.id}
                    type="button"
                    onClick={() => handleSelectOption(currentQuestion.id, option.id)}
                    className={cn(
                      'flex items-center gap-3 rounded-lg border p-3 text-left text-sm transition-colors',
                      isSelected
                        ? 'border-primary bg-primary/5 text-ink-primary'
                        : 'border-border-strong text-ink-secondary hover:bg-surface-hover',
                    )}
                  >
                    <span
                      className={cn(
                        'flex size-4 shrink-0 items-center justify-center rounded-full border',
                        isSelected ? 'border-primary bg-primary' : 'border-border-strong',
                      )}
                    >
                      {isSelected && <span className="size-1.5 rounded-full bg-white" />}
                    </span>
                    {option.option_text}
                  </button>
                )
              })}
            </div>
          </div>
        </Card>
      )}

      <div className="flex items-center justify-between gap-2">
        <Button
          variant="outline"
          disabled={currentIndex === 0 || !canNavigateTo(currentIndex - 1)}
          onClick={() => setCurrentIndex((i) => Math.max(0, i - 1))}
        >
          Previous
        </Button>

        <div className="flex gap-2">
          {currentIndex < questions.length - 1 ? (
            <Button onClick={() => setCurrentIndex((i) => Math.min(questions.length - 1, i + 1))}>Next</Button>
          ) : (
            <Button variant="primary" onClick={() => setShowSubmitConfirm(true)}>
              Submit Exam
            </Button>
          )}
        </div>
      </div>

      <Modal open={showSubmitConfirm} onClose={() => setShowSubmitConfirm(false)} title="Kumpulkan Ujian?">
        <div className="flex flex-col gap-4">
          <div className="flex items-start gap-2 rounded-lg border border-warning/40 bg-warning/10 p-3 text-sm text-warning">
            <AlertTriangle className="mt-0.5 size-4 shrink-0" />
            <p>
              Anda telah menjawab {answeredCount} dari {questions.length} soal. Setelah dikumpulkan, jawaban tidak dapat diubah
              lagi.
            </p>
          </div>

          <div className="flex justify-end gap-2">
            <Button variant="outline" onClick={() => setShowSubmitConfirm(false)}>
              Batal
            </Button>
            <Button variant="primary" isLoading={isSubmitting} onClick={handleSubmit}>
              Ya, Kumpulkan
            </Button>
          </div>
        </div>
      </Modal>
    </div>,
  )
}

/**
 * Layar konfirmasi sebelum masuk mode ujian — perlu klik eksplisit supaya
 * requestFullscreen() dipanggil dari dalam user-gesture asli (browser
 * menolak panggilan fullscreen di luar konteks itu), sekaligus tempat
 * menjelaskan aturan main ke mahasiswa sebelum timer backend berjalan.
 */
function ExamModeGate({
  exam,
  hasProgress,
  onStart,
  onBack,
}: {
  exam: StudentExam
  hasProgress: boolean
  onStart: () => void
  onBack: () => void
}) {
  return (
    <Card>
      <div className="flex flex-col gap-5 py-4">
        <div className="text-center">
          <p className="text-lg font-semibold text-ink-primary">{exam.title}</p>
          <p className="text-sm text-ink-tertiary">{exam.course_name ?? '-'}</p>
        </div>

        <div className="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-ink-tertiary">Durasi</p>
            <p className="font-semibold text-ink-primary">{exam.duration_minutes} menit</p>
          </div>
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-ink-tertiary">Jumlah Soal</p>
            <p className="font-semibold text-ink-primary">{exam.questions_per_participant}</p>
          </div>
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-ink-tertiary">Percobaan</p>
            <p className="font-semibold text-ink-primary">
              {exam.attempts_used} / {exam.max_attempts}
            </p>
          </div>
        </div>

        <div className="rounded-lg border border-border-strong bg-surface-hover p-4 text-sm text-ink-secondary">
          <p className="mb-2 font-medium text-ink-primary">Sebelum memulai:</p>
          <ul className="list-inside list-disc space-y-1">
            <li>Ujian akan dibuka dalam mode layar penuh.</li>
            <li>Jangan berpindah tab, aplikasi lain, atau me-refresh halaman selama ujian berlangsung.</li>
            <li>Waktu berjalan otomatis dan ujian akan dikumpulkan otomatis saat waktu habis.</li>
            <li>Jawaban tersimpan otomatis setiap kali Anda memilih opsi.</li>
            {!exam.allow_back_navigation && <li>Soal yang sudah dilewati tidak dapat dikunjungi kembali.</li>}
          </ul>
        </div>

        <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-between">
          <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={onBack}>
            Kembali
          </Button>
          <Button variant="primary" leftIcon={<Maximize className="size-4" />} onClick={onStart}>
            {hasProgress ? 'Lanjutkan Mengerjakan' : 'Mulai Mengerjakan'}
          </Button>
        </div>
      </div>
    </Card>
  )
}

function ExamStatusCard({ exam, onBack }: { exam: StudentExam; onBack: () => void }) {
  const message: Record<Exclude<StudentExam['status'], 'available' | 'in_progress'>, string> = {
    upcoming: exam.starts_at
      ? `Ujian ini baru dapat dikerjakan mulai ${new Date(exam.starts_at).toLocaleString('id-ID', {
          dateStyle: 'medium',
          timeStyle: 'short',
        })}.`
      : 'Ujian ini belum dapat dikerjakan.',
    expired: 'Waktu pengerjaan ujian ini sudah berakhir.',
    completed: 'Anda sudah menyelesaikan ujian ini.',
  }

  return (
    <Card>
      <div className="flex flex-col items-center gap-3 py-10 text-center">
        <p className="text-lg font-semibold text-ink-primary">{exam.title}</p>
        <p className="text-sm text-ink-tertiary">{message[exam.status as 'upcoming' | 'expired' | 'completed']}</p>

        {exam.attempts_used > 0 &&
          (exam.result_visible ? (
            <p className="text-3xl font-bold text-primary">{exam.score}</p>
          ) : (
            <Badge variant="warning">Waiting for Result</Badge>
          ))}

        <Button className="mt-2" variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={onBack}>
          Kembali ke Daftar Ujian
        </Button>
      </div>
    </Card>
  )
}
