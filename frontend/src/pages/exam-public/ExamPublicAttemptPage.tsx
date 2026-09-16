import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { AlertTriangle, Clock, Loader2, ShieldAlert } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { examPublicService } from '@/services/examPublicService'
import type { NormalizedApiError } from '@/services/api'
import type { ExamViolationType, PublicExamInfo, StudentExamAttempt } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { cn } from '@/utils/cn'
import { computeDeadlineMs, formatCountdown } from '@/utils/examTiming'
import { useExamViolationTracking } from '@/hooks/useExamViolationTracking'

const WARNING_THRESHOLD_MS = 5 * 60_000
const DANGER_THRESHOLD_MS = 60_000

/**
 * Halaman pengerjaan ujian publik (/exam/{access_token}/attempt/{session_token})
 * — standalone, tanpa sidebar/header dashboard (lihat PublicExamLayout).
 * Sengaja dibuat terpisah dari PortalExamTakingPage (bukan dipakai bersama)
 * supaya perubahan di sini tidak berisiko terhadap alur login yang sudah
 * berjalan dan diproktori — hanya dua helper murni (formatCountdown,
 * computeDeadlineMs) yang dipakai bersama lewat utils/examTiming.
 */
export function ExamPublicAttemptPage() {
  const { accessToken, sessionToken } = useParams<{ accessToken: string; sessionToken: string }>()
  const navigate = useNavigate()

  const [exam, setExam] = useState<PublicExamInfo | null>(null)
  const [attempt, setAttempt] = useState<StudentExamAttempt | null>(null)
  const [student, setStudent] = useState<{ name: string; nim: string } | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)

  const [currentIndex, setCurrentIndex] = useState(0)
  const [savingQuestionId, setSavingQuestionId] = useState<string | null>(null)
  const [saveError, setSaveError] = useState<string | null>(null)
  const [showSubmitConfirm, setShowSubmitConfirm] = useState(false)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [nowMs, setNowMs] = useState(() => Date.now())

  useEffect(() => {
    if (!accessToken || !sessionToken) return
    let cancelled = false
    setIsLoading(true)
    setLoadError(null)

    Promise.all([examPublicService.getExamAccess(accessToken), examPublicService.getAttempt(sessionToken)])
      .then(([examData, attemptData]) => {
        if (cancelled) return
        setExam(examData)
        setAttempt(attemptData)
        if (attemptData.student) setStudent(attemptData.student)
      })
      .catch((err: NormalizedApiError) => {
        if (!cancelled) setLoadError(err.message ?? 'Sesi ujian tidak ditemukan atau sudah tidak berlaku.')
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [accessToken, sessionToken])

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

  const deadlineMs = useMemo(() => {
    if (!exam || !attempt) return null
    return computeDeadlineMs(exam, attempt)
  }, [exam, attempt])

  const remainingMs = deadlineMs !== null ? Math.max(0, deadlineMs - nowMs) : null
  const violationCount = attempt?.violation_count ?? 0

  // Pelanggaran dilaporkan ke backend dan langsung memotong nilai
  // (-1/pelanggaran, spec §4) — sama seperti PortalExamTakingPage, lewat
  // hook bersama useExamViolationTracking.
  const handleViolation = useCallback(
    (type: ExamViolationType) => {
      if (!sessionToken) return

      examPublicService
        .reportViolation(sessionToken, { violation_type: type })
        .then((updated) => setAttempt((prev) => (prev ? { ...updated, student: prev.student } : updated)))
        .catch(() => {
          // Fire-and-forget — lihat catatan yang sama di PortalExamTakingPage.
        })
    },
    [sessionToken],
  )

  useExamViolationTracking(attempt?.status === 'in_progress', handleViolation)

  const handleSubmit = useCallback(async () => {
    if (!sessionToken) return

    setIsSubmitting(true)
    setSaveError(null)

    try {
      const result = await examPublicService.submit(sessionToken)
      setAttempt((prev) => (prev ? { ...result, student: prev.student } : result))
    } catch (err) {
      setSaveError((err as NormalizedApiError).message ?? 'Gagal mengumpulkan ujian.')
    } finally {
      setIsSubmitting(false)
      setShowSubmitConfirm(false)
    }
  }, [sessionToken])

  const autoSubmitTriggered = useRef(false)

  useEffect(() => {
    if (remainingMs === null || remainingMs > 0) return
    if (attempt?.status !== 'in_progress') return
    if (autoSubmitTriggered.current) return

    autoSubmitTriggered.current = true
    handleSubmit()
  }, [remainingMs, attempt?.status, handleSubmit, autoSubmitTriggered])

  useEffect(() => {
    if (accessToken && sessionToken && attempt?.status === 'submitted') {
      navigate(ROUTES.examPublic.result.replace(':accessToken', accessToken).replace(':sessionToken', sessionToken), {
        replace: true,
      })
    }
  }, [attempt?.status, accessToken, sessionToken, navigate])

  const handleSelectOption = async (questionId: string, optionId: string) => {
    if (!attempt || attempt.status !== 'in_progress' || !sessionToken) return

    setAttempt((prev) =>
      prev
        ? { ...prev, questions: prev.questions.map((q) => (q.id === questionId ? { ...q, selected_option_id: optionId } : q)) }
        : prev,
    )
    setSavingQuestionId(questionId)
    setSaveError(null)

    try {
      const updated = await examPublicService.answer(sessionToken, {
        exam_question_id: questionId,
        exam_question_option_id: optionId,
      })
      setAttempt((prev) => (prev ? { ...updated, student: prev.student } : updated))
    } catch (err) {
      setSaveError((err as NormalizedApiError).message ?? 'Gagal menyimpan jawaban.')
    } finally {
      setSavingQuestionId(null)
    }
  }

  const canNavigateTo = (index: number) => (exam ? exam.allow_back_navigation : true) || index >= currentIndex

  const pageShell = (children: ReactNode) => (
    <div className="px-4 py-6 sm:px-6">
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

  if (loadError || !exam || !attempt) {
    return pageShell(<Alert variant="danger">{loadError ?? 'Sesi ujian tidak ditemukan.'}</Alert>)
  }

  if (attempt.status === 'submitted') {
    return pageShell(
      <div className="flex min-h-[50vh] flex-col items-center justify-center gap-3 text-center">
        <Loader2 className="size-6 animate-spin text-ink-tertiary" />
        <p className="text-sm text-ink-tertiary">Ujian berhasil dikumpulkan. Mengarahkan ke hasil ujian...</p>
      </div>,
    )
  }

  const questions = attempt.questions
  const currentQuestion = questions[currentIndex]
  const answeredCount = questions.filter((q) => q.selected_option_id !== null).length
  const isWarning = remainingMs !== null && remainingMs <= WARNING_THRESHOLD_MS
  const isDanger = remainingMs !== null && remainingMs <= DANGER_THRESHOLD_MS

  return pageShell(
    <div className="flex select-none flex-col gap-4">
      <div className="sticky top-0 z-10 flex flex-col gap-3 rounded-xl border border-border bg-background/95 px-4 py-3 backdrop-blur">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div className="min-w-0">
            <p className="truncate font-semibold text-ink-primary">Ujian: {exam.title}</p>
            <p className="truncate text-xs text-ink-tertiary">{exam.course_name ?? '-'}</p>
            {student && (
              <p className="mt-1 text-xs text-ink-secondary">
                NIM: {student.nim} · {student.name}
              </p>
            )}
          </div>

          <div className="flex items-center gap-2">
            {violationCount > 0 && (
              <div
                className="flex items-center gap-1.5 rounded-lg border border-danger/40 bg-danger/10 px-2.5 py-1.5 text-xs font-medium text-danger"
                title="Jumlah pelanggaran yang tercatat selama ujian ini"
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
          </div>
        </div>

        {isWarning && (
          <Alert variant={isDanger ? 'danger' : 'warning'} className="py-2">
            {isDanger ? 'Waktu hampir habis! Ujian akan dikumpulkan otomatis.' : 'Waktu ujian hampir habis.'}
          </Alert>
        )}

        {violationCount > 0 && (
          <Alert variant="warning" className="py-2">
            Pelanggaran terdeteksi ({violationCount}x). Nilai akan dikurangi {violationCount} poin.
          </Alert>
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
          Sebelumnya
        </Button>

        <div className="flex gap-2">
          {currentIndex < questions.length - 1 ? (
            <Button onClick={() => setCurrentIndex((i) => Math.min(questions.length - 1, i + 1))}>Berikutnya</Button>
          ) : (
            <Button variant="primary" onClick={() => setShowSubmitConfirm(true)}>
              Submit Ujian
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
