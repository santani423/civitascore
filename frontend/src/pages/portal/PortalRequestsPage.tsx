import { useCallback, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { Download, FilePlus2, FileText, Paperclip } from 'lucide-react'
import { PageHeader } from '@/components/ui/PageHeader'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Input } from '@/components/ui/Input'
import { Select } from '@/components/ui/Select'
import { Textarea } from '@/components/ui/Textarea'
import { Alert } from '@/components/ui/Alert'
import { Modal } from '@/components/ui/Modal'
import { ListPagination } from '@/components/ui/ListPagination'
import { InfoList, PortalEmpty, PortalError, PortalLoading } from '@/components/portal/PortalState'
import { RequestStatusBadge } from '@/components/portal/badges'
import { useFetch } from '@/hooks/useFetch'
import { downloadBlob, studentPortalService } from '@/services/studentPortalService'
import { fileUploadService } from '@/services/fileUploadService'
import type { NormalizedApiError } from '@/services/api'
import { ROUTES } from '@/constants/routes'
import type { StudentRequest, StudentRequestType } from '@/types/studentPortal'
import { formatDateTime } from '@/utils/portalFormat'

const TYPE_OPTIONS: { value: StudentRequestType; label: string; hint: string }[] = [
  { value: 'leave', label: 'Cuti Akademik', hint: 'Mengajukan cuti untuk semester berjalan. Status Anda menjadi Cuti setelah disetujui.' },
  { value: 'reactivation', label: 'Aktif Kembali', hint: 'Untuk mahasiswa yang sedang Cuti/Nonaktif dan ingin kembali kuliah.' },
  { value: 'data_change', label: 'Perubahan Data', hint: 'Perubahan nama, email, tempat/tanggal lahir, atau jenis kelamin — diverifikasi Bagian Akademik.' },
  { value: 'letter', label: 'Surat Keterangan', hint: 'Surat keterangan aktif kuliah, berkelakuan baik, rekomendasi, dll. PDF tersedia setelah disetujui.' },
  { value: 'other', label: 'Pengajuan Lainnya', hint: 'Pengajuan akademik lain kepada Bagian Akademik.' },
]

const DEFAULT_TITLES: Record<StudentRequestType, string> = {
  leave: 'Pengajuan Cuti Akademik',
  reactivation: 'Pengajuan Aktif Kembali',
  data_change: 'Pengajuan Perubahan Data',
  letter: 'Permohonan Surat Keterangan',
  other: '',
}

const CHANGE_FIELDS: { key: string; label: string; type?: string }[] = [
  { key: 'name', label: 'Nama lengkap baru' },
  { key: 'email', label: 'Email baru', type: 'email' },
  { key: 'birth_place', label: 'Tempat lahir' },
  { key: 'tanggal_lahir', label: 'Tanggal lahir', type: 'date' },
]

function isRequestType(value: string | null): value is StudentRequestType {
  return TYPE_OPTIONS.some((option) => option.value === value)
}

export function PortalRequestsPage() {
  const [searchParams] = useSearchParams()
  const presetType = searchParams.get('jenis')

  const [page, setPage] = useState(1)
  const fetchRequests = useCallback(() => studentPortalService.requests(page), [page])
  const { data, isLoading, error, refetch } = useFetch(fetchRequests)
  const fetchOptions = useCallback(() => studentPortalService.requestOptions(), [])
  const { data: options } = useFetch(fetchOptions)

  const [formOpen, setFormOpen] = useState(isRequestType(presetType))
  const [type, setType] = useState<StudentRequestType>(isRequestType(presetType) ? presetType : 'leave')
  const [title, setTitle] = useState(DEFAULT_TITLES[isRequestType(presetType) ? presetType : 'leave'])
  const [description, setDescription] = useState('')
  const [changes, setChanges] = useState<Record<string, string>>({})
  const [letterType, setLetterType] = useState('active_student')
  const [purpose, setPurpose] = useState('')
  const [attachment, setAttachment] = useState<File | null>(null)
  const [saving, setSaving] = useState<'draft' | 'submit' | null>(null)
  const [formErrors, setFormErrors] = useState<Record<string, string[]>>({})

  const [message, setMessage] = useState<{ variant: 'success' | 'danger'; text: string } | null>(null)
  const [selected, setSelected] = useState<StudentRequest | null>(null)
  const [acting, setActing] = useState(false)

  const resetForm = (nextType: StudentRequestType = 'leave') => {
    setType(nextType)
    setTitle(DEFAULT_TITLES[nextType])
    setDescription('')
    setChanges({})
    setPurpose('')
    setAttachment(null)
    setFormErrors({})
  }

  const buildPayload = (): Record<string, unknown> | null => {
    if (type === 'data_change') {
      return { changes: Object.fromEntries(Object.entries(changes).filter(([, value]) => value.trim() !== '')) }
    }
    if (type === 'letter') return { letter_type: letterType, purpose }

    return null
  }

  const save = async (submit: boolean) => {
    setSaving(submit ? 'submit' : 'draft')
    setFormErrors({})
    setMessage(null)

    try {
      const upload = attachment ? await fileUploadService.upload(attachment) : null
      const result = await studentPortalService.createRequest({
        type,
        title,
        description: description || null,
        payload: buildPayload(),
        attachment_file_id: upload?.id ?? null,
        submit,
      })
      setMessage({ variant: 'success', text: result.message ?? 'Pengajuan tersimpan.' })
      setFormOpen(false)
      resetForm()
      setPage(1)
      refetch()
    } catch (err) {
      const apiError = err as NormalizedApiError
      setFormErrors(apiError.errors ?? {})
      const firstError = apiError.errors ? Object.values(apiError.errors)[0]?.[0] : undefined
      setMessage({ variant: 'danger', text: firstError ?? apiError.message })
    } finally {
      setSaving(null)
    }
  }

  const act = async (action: () => Promise<{ data: StudentRequest; message: string | null }>) => {
    setActing(true)
    try {
      const result = await action()
      setSelected(result.data)
      setMessage({ variant: 'success', text: result.message ?? 'Berhasil.' })
      refetch()
    } catch (err) {
      setMessage({ variant: 'danger', text: (err as NormalizedApiError).message })
    } finally {
      setActing(false)
    }
  }

  const openDetail = async (request: StudentRequest) => {
    try {
      setSelected(await studentPortalService.request(request.id))
    } catch (err) {
      setMessage({ variant: 'danger', text: (err as NormalizedApiError).message })
    }
  }

  const typeHint = TYPE_OPTIONS.find((option) => option.value === type)?.hint

  return (
    <div className="flex flex-col gap-5">
      <PageHeader
        title="Pengajuan Akademik"
        description="Ajukan cuti, aktif kembali, perubahan data, atau surat keterangan. Keputusan diambil Bagian Akademik."
        breadcrumb={[{ label: 'Dashboard', path: ROUTES.dashboard }, { label: 'Pengajuan' }]}
        actions={
          !formOpen && (
            <Button size="sm" leftIcon={<FilePlus2 className="size-4" />} onClick={() => setFormOpen(true)}>
              Buat Pengajuan
            </Button>
          )
        }
      />

      {message && (
        <Alert variant={message.variant} onDismiss={() => setMessage(null)}>
          {message.text}
        </Alert>
      )}

      {formOpen && (
        <Card title="Pengajuan Baru">
          <div className="flex flex-col gap-4">
            <Select
              label="Jenis pengajuan"
              value={type}
              onChange={(event) => resetForm(event.target.value as StudentRequestType)}
              options={TYPE_OPTIONS.map((option) => ({ value: option.value, label: option.label }))}
            />
            {typeHint && <p className="-mt-2 text-xs text-ink-tertiary">{typeHint}</p>}

            <Input label="Judul" value={title} onChange={(event) => setTitle(event.target.value)} error={formErrors.title?.[0]} />

            {type === 'data_change' && (
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                {CHANGE_FIELDS.map((field) => (
                  <Input
                    key={field.key}
                    label={field.label}
                    type={field.type ?? 'text'}
                    value={changes[field.key] ?? ''}
                    onChange={(event) => setChanges((current) => ({ ...current, [field.key]: event.target.value }))}
                    error={formErrors[`payload.changes.${field.key}`]?.[0]}
                  />
                ))}
                <Select
                  label="Jenis kelamin"
                  value={changes.gender ?? ''}
                  onChange={(event) => setChanges((current) => ({ ...current, gender: event.target.value }))}
                  options={[
                    { value: '', label: 'Tidak diubah' },
                    { value: 'male', label: 'Laki-laki' },
                    { value: 'female', label: 'Perempuan' },
                  ]}
                />
                {formErrors['payload.changes'] && <p className="text-xs text-danger sm:col-span-2">{formErrors['payload.changes'][0]}</p>}
              </div>
            )}

            {type === 'letter' && (
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <Select
                  label="Jenis surat"
                  value={letterType}
                  onChange={(event) => setLetterType(event.target.value)}
                  options={options?.letter_types ?? [{ value: 'active_student', label: 'Surat Keterangan Aktif Kuliah' }]}
                />
                <Input label="Keperluan" value={purpose} onChange={(event) => setPurpose(event.target.value)} error={formErrors['payload.purpose']?.[0]} placeholder="Mis. pengajuan beasiswa" />
              </div>
            )}

            <Textarea
              label={type === 'leave' ? 'Alasan cuti' : 'Keterangan'}
              rows={3}
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              error={formErrors.description?.[0]}
            />

            <div className="flex flex-col gap-1.5">
              <label className="text-sm font-medium text-ink-primary">Lampiran (opsional)</label>
              <input
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                onChange={(event) => setAttachment(event.target.files?.[0] ?? null)}
                className="text-sm text-ink-secondary file:mr-3 file:rounded-lg file:border-0 file:bg-surface-hover file:px-3 file:py-1.5 file:text-sm file:text-ink-primary"
              />
              {formErrors.attachment_file_id && <p className="text-xs text-danger">{formErrors.attachment_file_id[0]}</p>}
            </div>

            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
              <Button
                variant="ghost"
                onClick={() => {
                  setFormOpen(false)
                  resetForm()
                }}
              >
                Batal
              </Button>
              <Button variant="outline" isLoading={saving === 'draft'} disabled={saving !== null} onClick={() => save(false)}>
                Simpan Draft
              </Button>
              <Button isLoading={saving === 'submit'} disabled={saving !== null} onClick={() => save(true)}>
                Kirim Pengajuan
              </Button>
            </div>
          </div>
        </Card>
      )}

      {isLoading && !data ? (
        <PortalLoading cards={0} rows={5} />
      ) : error ? (
        <PortalError message={error} onRetry={refetch} />
      ) : !data || data.data.length === 0 ? (
        <PortalEmpty icon={FileText} title="Belum ada pengajuan." description="Pengajuan yang Anda buat akan tampil di sini beserta statusnya." />
      ) : (
        <Card noPadding>
          <ul className="flex flex-col divide-y divide-border">
            {data.data.map((request) => (
              <li key={request.id}>
                <button type="button" onClick={() => openDetail(request)} className="flex w-full flex-col gap-1 p-4 text-left hover:bg-surface-hover sm:flex-row sm:items-center sm:justify-between">
                  <div className="min-w-0">
                    <p className="font-medium text-ink-primary">{request.title}</p>
                    <p className="text-xs text-ink-secondary">
                      {request.type_label} · {request.submitted_at ? `Dikirim ${formatDateTime(request.submitted_at)}` : `Dibuat ${formatDateTime(request.created_at)}`}
                    </p>
                  </div>
                  <RequestStatusBadge status={request.status} label={request.status_label} />
                </button>
              </li>
            ))}
          </ul>
          <div className="border-t border-border px-4 py-3">
            <ListPagination meta={data.meta} page={page} onPageChange={setPage} itemLabel="pengajuan" />
          </div>
        </Card>
      )}

      <Modal open={selected !== null} onClose={() => setSelected(null)} title={selected?.title}>
        {selected && (
          <div className="flex flex-col gap-4 text-sm">
            <RequestStatusBadge status={selected.status} label={selected.status_label} />
            <InfoList
              columns={1}
              items={[
                { label: 'Jenis', value: selected.letter_type_label ? `${selected.type_label} — ${selected.letter_type_label}` : selected.type_label },
                { label: 'Semester', value: selected.term?.label ?? '-' },
                { label: 'Dikirim', value: formatDateTime(selected.submitted_at) },
                { label: 'Tahap saat ini', value: selected.current_step?.name ?? (selected.status === 'submitted' ? 'Menunggu peninjauan' : '-') },
                { label: 'Peninjau', value: selected.reviewer ?? '-' },
                { label: 'Catatan peninjau', value: selected.decision_note ?? '-' },
              ]}
            />
            {selected.description && <p className="whitespace-pre-line rounded-lg bg-surface-hover p-3 text-ink-secondary">{selected.description}</p>}
            {selected.attachment && (
              <Button size="sm" variant="outline" leftIcon={<Paperclip className="size-3.5" />} onClick={() => downloadBlob(`/file-uploads/${selected.attachment!.id}/download`, selected.attachment!.name)}>
                {selected.attachment.name}
              </Button>
            )}
            {selected.history.length > 0 && (
              <div>
                <p className="mb-1 text-xs font-medium text-ink-tertiary">Riwayat</p>
                <ul className="flex flex-col gap-1">
                  {selected.history.map((entry, index) => (
                    <li key={index} className="text-xs text-ink-secondary">
                      {formatDateTime(entry.created_at)} — {entry.description}
                    </li>
                  ))}
                </ul>
              </div>
            )}
            <div className="flex flex-wrap justify-end gap-2">
              {selected.has_letter && (
                <Button
                  size="sm"
                  leftIcon={<Download className="size-3.5" />}
                  onClick={() =>
                    downloadBlob(`/student/requests/${selected.id}/letter`, `${selected.title.replace(/\W+/g, '-')}.pdf`).catch((err: NormalizedApiError) =>
                      setMessage({ variant: 'danger', text: err.message }),
                    )
                  }
                >
                  Unduh Surat
                </Button>
              )}
              {selected.status === 'draft' && (
                <Button size="sm" isLoading={acting} onClick={() => act(() => studentPortalService.submitRequest(selected.id))}>
                  Kirim
                </Button>
              )}
              {(selected.status === 'draft' || selected.status === 'submitted') && (
                <Button size="sm" variant="danger" isLoading={acting} onClick={() => act(() => studentPortalService.cancelRequest(selected.id))}>
                  Batalkan
                </Button>
              )}
            </div>
          </div>
        )}
      </Modal>
    </div>
  )
}
