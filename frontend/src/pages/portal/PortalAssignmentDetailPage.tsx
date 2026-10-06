import { useCallback, useRef, useState } from 'react'
import { useParams } from 'react-router-dom'
import { Download, Paperclip, Upload } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Textarea } from '@/components/ui/Textarea'
import { Alert } from '@/components/ui/Alert'
import { InfoList, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { AssignmentStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import { fileUploadService } from '@/services/fileUploadService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import { formatCountdown, formatDateTime } from '@/utils/portalFormat'
import { formatFileSize } from '@/utils/formatters'

export function PortalAssignmentDetailPage() {
  const { id = '' } = useParams()
  const fetchAssignment = useCallback(() => studentPortalService.assignment(id), [id])
  const { data: assignment, setData, isLoading, error, refetch } = useFetch(fetchAssignment)

  const [file, setFile] = useState<File | null>(null)
  const [notes, setNotes] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [message, setMessage] = useState<{ variant: 'success' | 'danger'; text: string } | null>(null)
  const fileInput = useRef<HTMLInputElement>(null)

  const download = async (fileId: string, name: string) => {
    try {
      await downloadBlob(`/file-uploads/${fileId}/download`, name)
    } catch (err) {
      setMessage({ variant: 'danger', text: (err as NormalizedApiError).message })
    }
  }

  const pickFile = (picked: File | null) => {
    setMessage(null)
    if (!picked || !assignment) return setFile(null)

    // Pemeriksaan awal di klien supaya cepat; aturan yang mengikat tetap dicek backend.
    const extension = picked.name.split('.').pop()?.toLowerCase() ?? ''
    if (!assignment.allowed_extensions.includes(extension)) {
      setMessage({ variant: 'danger', text: `Format berkas tidak diizinkan. Format yang diterima: ${assignment.allowed_extensions.join(', ')}.` })
      return setFile(null)
    }
    if (picked.size > assignment.max_file_size_mb * 1024 * 1024) {
      setMessage({ variant: 'danger', text: `Ukuran berkas melebihi batas ${assignment.max_file_size_mb} MB.` })
      return setFile(null)
    }

    setFile(picked)
  }

  const submit = async () => {
    if (!assignment) return
    setSubmitting(true)
    setMessage(null)

    try {
      const upload = file ? await fileUploadService.upload(file) : null
      const result = await studentPortalService.submitAssignment(assignment.id, { file_upload_id: upload?.id ?? null, notes: notes || null })
      setData(result.data)
      setFile(null)
      setNotes('')
      if (fileInput.current) fileInput.current.value = ''
      setMessage({ variant: 'success', text: result.message ?? 'Tugas berhasil dikumpulkan.' })
    } catch (err) {
      const apiError = err as NormalizedApiError
      const fieldMessage = apiError.errors ? Object.values(apiError.errors)[0]?.[0] : undefined
      setMessage({ variant: 'danger', text: fieldMessage ?? apiError.message })
    } finally {
      setSubmitting(false)
    }
  }

  if (isLoading && !assignment) return <PortalLoading cards={0} rows={6} />
  if (error) return <PortalError message={error} onRetry={refetch} />
  if (!assignment) return null

  const submission = assignment.submission

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title={assignment.title}
        description={assignment.course ? `${assignment.course.name} · Kelas ${assignment.course.class_code}` : undefined}
        breadcrumb={[
          { label: 'Dashboard', path: ROUTES.dashboard },
          { label: 'Tugas', path: ROUTES.portal.tugas },
          { label: assignment.title },
        ]}
        actions={<AssignmentStatusBadge status={assignment.status} label={assignment.status_label} />}
      />

      {message && (
        <Alert variant={message.variant} onDismiss={() => setMessage(null)}>
          {message.text}
        </Alert>
      )}

      <div className="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <Card title="Detail Tugas" className="lg:col-span-2">
          <InfoList
            items={[
              { label: 'Dosen', value: assignment.course?.lecturer?.name ?? '-' },
              { label: 'Batas pengumpulan', value: `${formatDateTime(assignment.due_at)} (${formatCountdown(assignment.due_at)})` },
              { label: 'Format berkas', value: assignment.allowed_extensions.join(', ').toUpperCase() },
              { label: 'Ukuran maksimal', value: `${assignment.max_file_size_mb} MB` },
              { label: 'Pengumpulan ulang', value: assignment.allow_resubmission ? 'Diizinkan sebelum dinilai' : 'Tidak diizinkan' },
              { label: 'Keterlambatan', value: assignment.allow_late_submission ? 'Diterima (ditandai terlambat)' : 'Tidak diterima' },
            ]}
          />
          {assignment.description && <p className="mt-4 whitespace-pre-line text-sm text-ink-primary">{assignment.description}</p>}
          {assignment.attachment && (
            <Button
              size="sm"
              variant="outline"
              className="mt-4"
              leftIcon={<Paperclip className="size-3.5" />}
              onClick={() => download(assignment.attachment!.id, assignment.attachment!.name)}
            >
              Lampiran: {assignment.attachment.name}
            </Button>
          )}
        </Card>

        <div className="flex flex-col gap-5">
          <Card title="Pengumpulan Saya">
            {submission ? (
              <div className="flex flex-col gap-2 text-sm">
                <p className="text-ink-secondary">
                  Dikumpulkan {formatDateTime(submission.submitted_at)}
                  {submission.is_late && <span className="text-danger"> (terlambat)</span>}
                  {submission.submission_count > 1 && ` · pengumpulan ke-${submission.submission_count}`}
                </p>
                {submission.file && (
                  <Button size="sm" variant="outline" leftIcon={<Download className="size-3.5" />} onClick={() => download(submission.file!.id, submission.file!.name)}>
                    {submission.file.name} ({formatFileSize(submission.file.size_bytes)})
                  </Button>
                )}
                {submission.notes && <p className="whitespace-pre-line rounded-lg bg-surface-hover p-2 text-xs text-ink-secondary">{submission.notes}</p>}
                {submission.graded_at && (
                  <div className="mt-2 rounded-lg border border-border p-3">
                    <p className="text-xs text-ink-tertiary">Nilai</p>
                    <p className="text-2xl font-semibold text-ink-primary">
                      {submission.score}
                      <span className="text-sm font-normal text-ink-secondary"> / {assignment.max_score}</span>
                    </p>
                    {submission.feedback && <p className="mt-1 text-xs text-ink-secondary">{submission.feedback}</p>}
                  </div>
                )}
              </div>
            ) : (
              <p className="text-sm text-ink-secondary">Belum ada pengumpulan.</p>
            )}
          </Card>

          {assignment.can_submit ? (
            <Card title={submission ? 'Kumpulkan Ulang' : 'Kumpulkan Tugas'}>
              <div className="flex flex-col gap-3">
                <input
                  ref={fileInput}
                  type="file"
                  accept={assignment.allowed_extensions.map((extension) => `.${extension}`).join(',')}
                  className="hidden"
                  onChange={(event) => pickFile(event.target.files?.[0] ?? null)}
                />
                <Button variant="outline" leftIcon={<Upload className="size-4" />} onClick={() => fileInput.current?.click()}>
                  {file ? `${file.name} (${formatFileSize(file.size)})` : 'Pilih berkas'}
                </Button>
                <Textarea label="Catatan / jawaban (opsional)" rows={3} value={notes} onChange={(event) => setNotes(event.target.value)} />
                <Button isLoading={submitting} disabled={!file && !notes.trim()} onClick={submit}>
                  Kumpulkan
                </Button>
                {assignment.is_past_due && <p className="text-xs text-danger">Batas waktu sudah lewat — pengumpulan akan ditandai terlambat.</p>}
              </div>
            </Card>
          ) : (
            <Alert variant="info">
              {submission?.graded_at
                ? 'Tugas sudah dinilai.'
                : assignment.is_past_due && !assignment.allow_late_submission
                  ? 'Batas waktu pengumpulan sudah lewat.'
                  : 'Tugas ini tidak mengizinkan pengumpulan ulang.'}
            </Alert>
          )}
        </div>
      </div>
    </div>
  )
}
