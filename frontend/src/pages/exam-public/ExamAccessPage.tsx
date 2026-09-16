import { useEffect, useState, type FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { Clock, Loader2, User2 } from 'lucide-react'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Alert } from '@/components/ui/Alert'
import { examPublicService } from '@/services/examPublicService'
import type { NormalizedApiError } from '@/services/api'
import type { PublicExamInfo } from '@/types/academic'
import { ROUTES } from '@/constants/routes'

/**
 * Halaman akses ujian publik (/exam/{access_token}) — mahasiswa membuka
 * link/scan QR dan cukup memasukkan NIM, tidak ada login sama sekali (spec
 * §3). Validasi NIM sepenuhnya di backend (examPublicService.submitNim);
 * pesan error di sini murni meneruskan `NormalizedApiError.message` dari
 * backend, tidak menduplikasi logic validasi di frontend (spec §4).
 */
export function ExamAccessPage() {
  const { accessToken } = useParams<{ accessToken: string }>()
  const navigate = useNavigate()

  const [exam, setExam] = useState<PublicExamInfo | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [loadError, setLoadError] = useState<string | null>(null)

  const [nim, setNim] = useState('')
  const [isSubmitting, setIsSubmitting] = useState(false)
  const [submitError, setSubmitError] = useState<string | null>(null)

  useEffect(() => {
    if (!accessToken) return
    let cancelled = false
    setIsLoading(true)
    setLoadError(null)

    examPublicService
      .getExamAccess(accessToken)
      .then((data) => {
        if (!cancelled) setExam(data)
      })
      .catch((err: NormalizedApiError) => {
        if (!cancelled) setLoadError(err.status === 404 ? 'Ujian tidak ditemukan atau link sudah tidak berlaku.' : err.message)
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [accessToken])

  const handleSubmit = async (event: FormEvent) => {
    event.preventDefault()
    if (!accessToken || !nim.trim()) return

    setIsSubmitting(true)
    setSubmitError(null)

    try {
      const { session_token } = await examPublicService.submitNim(accessToken, nim.trim())
      navigate(ROUTES.examPublic.attempt.replace(':accessToken', accessToken).replace(':sessionToken', session_token))
    } catch (err) {
      setSubmitError((err as NormalizedApiError).message ?? 'Gagal memvalidasi NIM. Silakan coba lagi.')
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-[calc(100vh-57px)] items-center justify-center px-4 py-10">
      <div className="w-full max-w-md">
        {isLoading ? (
          <div className="flex justify-center py-10">
            <Loader2 className="size-6 animate-spin text-ink-tertiary" />
          </div>
        ) : loadError || !exam ? (
          <Card>
            <Alert variant="danger">{loadError ?? 'Ujian tidak ditemukan.'}</Alert>
          </Card>
        ) : (
          <Card title="Akses Ujian" description="Masukkan NIM Anda untuk memulai ujian.">
            <div className="flex flex-col gap-4">
              <div className="rounded-lg border border-border-strong bg-surface-hover p-4">
                <p className="text-base font-semibold text-ink-primary">{exam.title}</p>
                <p className="text-sm text-ink-tertiary">{exam.course_name ?? '-'}</p>
                <div className="mt-3 flex flex-wrap gap-4 text-sm text-ink-secondary">
                  <span className="flex items-center gap-1.5">
                    <User2 className="size-4" /> {exam.lecturer_name ?? '-'}
                  </span>
                  <span className="flex items-center gap-1.5">
                    <Clock className="size-4" /> {exam.duration_minutes} menit
                  </span>
                </div>
              </div>

              {submitError && (
                <Alert variant="danger" onDismiss={() => setSubmitError(null)}>
                  {submitError}
                </Alert>
              )}

              <form className="flex flex-col gap-4" onSubmit={handleSubmit}>
                <Input
                  label="NIM"
                  name="nim"
                  value={nim}
                  onChange={(event) => setNim(event.target.value)}
                  placeholder="Masukkan NIM Anda"
                  autoFocus
                  required
                />

                <Button type="submit" isLoading={isSubmitting} disabled={!nim.trim()}>
                  Mulai Ujian
                </Button>
              </form>
            </div>
          </Card>
        )}
      </div>
    </div>
  )
}
