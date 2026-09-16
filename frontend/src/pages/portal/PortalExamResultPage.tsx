import { useCallback, useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { ArrowLeft, CheckCircle2, Clock, Download, Loader2, XCircle } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Badge } from '@/components/ui/Badge'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { studentExamService } from '@/services/academicService'
import type { NormalizedApiError } from '@/services/api'
import type { StudentExamResult } from '@/types/academic'
import { ROUTES } from '@/constants/routes'
import { cn } from '@/utils/cn'

function formatDateTime(iso: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

function formatDuration(seconds: number | null): string {
  if (seconds === null) return '-'
  const hours = Math.floor(seconds / 3600)
  const minutes = Math.floor((seconds % 3600) / 60)
  const secs = seconds % 60
  const pad = (value: number) => value.toString().padStart(2, '0')

  return hours > 0 ? `${pad(hours)}:${pad(minutes)}:${pad(secs)}` : `${pad(minutes)}:${pad(secs)}`
}

export function PortalExamResultPage() {
  const { attemptId } = useParams<{ attemptId: string }>()
  const navigate = useNavigate()

  const [result, setResult] = useState<StudentExamResult | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [notAvailableMessage, setNotAvailableMessage] = useState<string | null>(null)
  const [isDownloading, setIsDownloading] = useState(false)
  const [downloadError, setDownloadError] = useState<string | null>(null)

  useEffect(() => {
    if (!attemptId) return
    let cancelled = false
    setIsLoading(true)
    setError(null)
    setNotAvailableMessage(null)

    studentExamService
      .result(attemptId)
      .then((data) => {
        if (!cancelled) setResult(data)
      })
      .catch((err: NormalizedApiError) => {
        if (cancelled) return

        if (err.status === 409) {
          setNotAvailableMessage(err.message)
        } else if (err.status === 403) {
          setError('Anda tidak berhak melihat hasil ujian ini.')
        } else {
          setError(err.message ?? 'Gagal memuat hasil ujian.')
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [attemptId])

  const handleDownload = useCallback(async () => {
    if (!attemptId || !result) return

    setIsDownloading(true)
    setDownloadError(null)

    try {
      const filename = `hasil-ujian-${result.exam.title.replace(/\s+/g, '-').toLowerCase()}.pdf`
      await studentExamService.downloadResultPdf(attemptId, filename)
    } catch (err) {
      setDownloadError((err as NormalizedApiError).message ?? 'Gagal mengunduh PDF hasil ujian. Silakan coba lagi.')
    } finally {
      setIsDownloading(false)
    }
  }, [attemptId, result])

  const backButton = (
    <Button variant="outline" leftIcon={<ArrowLeft className="size-4" />} onClick={() => navigate(ROUTES.portal.ujian)}>
      Kembali ke Daftar Ujian
    </Button>
  )

  if (isLoading) {
    return (
      <div className="flex min-h-[50vh] items-center justify-center">
        <Loader2 className="size-6 animate-spin text-ink-tertiary" />
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex flex-col gap-4">
        <Alert variant="danger">{error}</Alert>
        {backButton}
      </div>
    )
  }

  if (notAvailableMessage) {
    return (
      <div className="flex flex-col gap-4">
        <Card>
          <div className="flex flex-col items-center gap-3 py-10 text-center">
            <Clock className="size-10 text-ink-tertiary" />
            <p className="text-lg font-semibold text-ink-primary">Hasil Belum Tersedia</p>
            <p className="max-w-md text-sm text-ink-tertiary">{notAvailableMessage}</p>
            {backButton}
          </div>
        </Card>
      </div>
    )
  }

  if (!result) {
    return (
      <div className="flex flex-col gap-4">
        <Alert variant="warning">Data hasil ujian tidak ditemukan.</Alert>
        {backButton}
      </div>
    )
  }

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Hasil Ujian"
        description={`${result.exam.title} — ${result.exam.course_name ?? '-'}`}
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Perkuliahan' },
          { label: 'Ujian', path: ROUTES.portal.ujian },
          { label: 'Hasil Ujian' },
        ]}
        actions={
          <Button variant="primary" leftIcon={<Download className="size-4" />} isLoading={isDownloading} onClick={handleDownload}>
            Download Hasil Ujian
          </Button>
        }
      />

      {downloadError && (
        <Alert variant="danger" onDismiss={() => setDownloadError(null)}>
          {downloadError}
        </Alert>
      )}

      <Card title="Identitas & Ringkasan Nilai">
        <div className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
          <div>
            <p className="text-ink-tertiary">Nama Mahasiswa</p>
            <p className="mt-0.5 font-medium text-ink-primary">{result.student.name}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">NIM</p>
            <p className="mt-0.5 font-medium text-ink-primary">{result.student.nim}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Waktu Mulai</p>
            <p className="mt-0.5 font-medium text-ink-primary">{formatDateTime(result.attempt.started_at)}</p>
          </div>
          <div>
            <p className="text-ink-tertiary">Waktu Selesai</p>
            <p className="mt-0.5 font-medium text-ink-primary">{formatDateTime(result.attempt.submitted_at)}</p>
          </div>
        </div>

        <div className="my-4 border-t border-border" />

        <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-lg font-semibold text-ink-primary">{result.summary.total_questions}</p>
            <p className="text-xs text-ink-tertiary">Jumlah Soal</p>
          </div>
          <div className="rounded-lg border border-success/40 bg-success/5 p-3 text-center">
            <p className="text-lg font-semibold text-success">{result.summary.correct_answers}</p>
            <p className="text-xs text-ink-tertiary">Jumlah Benar</p>
          </div>
          <div className="rounded-lg border border-danger/40 bg-danger/5 p-3 text-center">
            <p className="text-lg font-semibold text-danger">{result.summary.wrong_answers}</p>
            <p className="text-xs text-ink-tertiary">Jumlah Salah</p>
          </div>
          <div className="rounded-lg border border-primary/40 bg-primary/5 p-3 text-center">
            <p className="text-lg font-semibold text-primary">{result.summary.score}</p>
            <p className="text-xs text-ink-tertiary">Nilai Akhir</p>
          </div>
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-lg font-semibold text-ink-primary">{result.summary.percentage}%</p>
            <p className="text-xs text-ink-tertiary">Persentase</p>
          </div>
          <div className="rounded-lg border border-border-strong p-3 text-center">
            <p className="text-lg font-semibold text-ink-primary">{formatDuration(result.attempt.duration_seconds)}</p>
            <p className="text-xs text-ink-tertiary">Durasi Pengerjaan</p>
          </div>
        </div>
      </Card>

      <div className="flex flex-col gap-3">
        <h2 className="text-sm font-semibold text-ink-primary">Koreksi Jawaban</h2>

        {result.questions.map((question) => (
          <Card
            key={question.number}
            className={cn('border-l-4', question.is_correct ? 'border-l-success' : 'border-l-danger')}
          >
            <div className="flex flex-col gap-3">
              <div className="flex items-start justify-between gap-2">
                <p className="text-sm font-medium text-ink-tertiary">Soal {question.number}</p>
                {question.is_correct ? (
                  <Badge variant="success" className="gap-1">
                    <CheckCircle2 className="size-3.5" /> Benar
                  </Badge>
                ) : (
                  <Badge variant="danger" className="gap-1">
                    <XCircle className="size-3.5" /> Salah
                  </Badge>
                )}
              </div>

              <p className="whitespace-pre-wrap text-sm font-medium text-ink-primary">{question.question_text}</p>

              <div className="flex flex-col gap-2">
                {question.options.map((option) => {
                  const isSelected = option.id === question.selected_option_id
                  const isCorrectOption = option.id === question.correct_option_id

                  return (
                    <div
                      key={option.id}
                      className={cn(
                        'flex items-center justify-between gap-2 rounded-lg border p-2.5 text-sm',
                        isCorrectOption
                          ? 'border-success/50 bg-success/10 text-ink-primary'
                          : isSelected
                            ? 'border-danger/50 bg-danger/10 text-ink-primary'
                            : 'border-border-strong text-ink-secondary',
                      )}
                    >
                      <span>{option.option_text}</span>
                      <span className="flex shrink-0 gap-1.5">
                        {isSelected && (
                          <Badge variant={isCorrectOption ? 'success' : 'danger'}>Jawaban Anda</Badge>
                        )}
                        {isCorrectOption && !isSelected && <Badge variant="success">Jawaban Benar</Badge>}
                      </span>
                    </div>
                  )
                })}
                {question.selected_option_id === null && (
                  <p className="text-xs italic text-ink-tertiary">Anda tidak menjawab soal ini.</p>
                )}
              </div>

              {question.explanation && (
                <div className="rounded-lg border border-border-strong bg-surface-hover p-3 text-sm text-ink-secondary">
                  <p className="mb-1 font-medium text-ink-primary">Pembahasan</p>
                  <p className="whitespace-pre-wrap">{question.explanation}</p>
                </div>
              )}
            </div>
          </Card>
        ))}
      </div>

      {backButton}
    </div>
  )
}
