import { useRef, useState } from 'react'
import { Eye, Plus, Upload } from 'lucide-react'
import { pmbApplicant } from '@/pmb/api/applicant'
import type { ApplicantDocument } from '@/pmb/api/types'
import { useLoad } from '@/pmb/components/useLoad'
import { Feedback, useAction } from '@/pmb/components/useAction'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { DocumentPreviewModal } from '@/pmb/components/DocumentPreviewModal'
import { Card } from '@/components/ui/Card'
import { Button } from '@/components/ui/Button'
import { Alert } from '@/components/ui/Alert'
import { compressionNote, fitFileToLimit } from '@/utils/compressFile'

/** POR-05 Dokumen: unggah per slot, versi, status tinjauan & alasan, tambah slot (dokumen multi). */
export function DocumentsPanel({ applicationId, stage = 'application', editable = true, onChanged }: { applicationId: string; stage?: 'application' | 're_registration'; editable?: boolean; onChanged?: () => void }) {
  const docs = useLoad(() => pmbApplicant.documents(applicationId, stage), [applicationId, stage])
  const action = useAction()
  const inputRef = useRef<HTMLInputElement>(null)
  const [target, setTarget] = useState<ApplicantDocument | null>(null)
  const [preview, setPreview] = useState<ApplicantDocument | null>(null)

  const pick = (doc: ApplicantDocument) => {
    setTarget(doc)
    inputRef.current?.click()
  }

  const upload = async (file: File | undefined) => {
    if (!file || !target) return
    const maxKb = target.document_type?.max_size_kb ?? 2048
    if (inputRef.current) inputRef.current.value = ''

    // Gambar yang melebihi batas dikompres otomatis; PDF yang terlalu besar tetap ditolak dengan pesan.
    const uploaded = await action.run(async () => {
      const ready = await fitFileToLimit(file, maxKb * 1024, { accept: target.document_type?.allowed_mime_types })
      await pmbApplicant.uploadDocument(applicationId, target.id, ready)

      return ready
    }, (ready) => `Berkas diunggah${compressionNote(file, ready)}.`)

    if (uploaded) {
      docs.reload()
      onChanged?.()
    }
  }

  return (
    <Card title={stage === 'application' ? 'Dokumen Pendaftaran' : 'Dokumen Daftar Ulang'}>
      <input ref={inputRef} type="file" className="hidden" accept={target?.document_type?.allowed_mime_types.join(',')} onChange={(e) => upload(e.target.files?.[0])} />
      <div className="flex flex-col gap-3">
        <Feedback state={action} />
        {docs.error && <Alert variant="danger">{docs.error.message}</Alert>}
        {docs.data?.length === 0 && <p className="text-sm text-ink-secondary">Tidak ada dokumen yang diminta pada tahap ini.</p>}
        {(docs.data ?? []).map((doc) => {
          const canUpload = editable && !doc.locked && ['missing', 'uploaded', 'revision_requested', 'rejected'].includes(doc.status)

          return (
            <div key={doc.id} className="flex flex-wrap items-start justify-between gap-3 rounded-lg border border-border p-3">
              <div className="min-w-0 flex-1">
                <p className="text-sm font-medium text-ink-primary">{doc.document_type?.name}{doc.slot > 1 ? ` #${doc.slot}` : ''} {doc.is_mandatory ? <span className="text-danger">*</span> : <span className="text-xs text-ink-tertiary">(opsional)</span>}</p>
                <p className="text-xs text-ink-tertiary">{doc.document_type?.allowed_mime_types.map((m) => m.split('/')[1]?.toUpperCase()).join(', ')} · maks {Math.round((doc.document_type?.max_size_kb ?? 0) / 102.4) / 10} MB{doc.instructions ? ` · ${doc.instructions}` : ''}</p>
                {doc.current_version && <p className="mt-1 text-xs text-ink-secondary">Versi {doc.current_version.version_no}: {doc.current_version.original_name}{doc.current_version.scan_status && doc.current_version.scan_status !== 'clean' ? ` (pemindaian: ${doc.current_version.scan_status})` : ''}</p>}
                {doc.last_review && doc.last_review.decision !== 'approved' && <p className="mt-1 text-xs text-danger">Catatan: {doc.last_review.note ?? doc.last_review.reason_code}</p>}
              </div>
              <div className="flex items-center gap-2">
                <StatusBadge status={doc.status} />
                {doc.current_version && <Button size="sm" variant="ghost" leftIcon={<Eye className="size-4" />} onClick={() => setPreview(doc)}>Lihat</Button>}
                {canUpload && <Button size="sm" variant={doc.current_version ? 'outline' : 'primary'} leftIcon={<Upload className="size-4" />} isLoading={action.busy && target?.id === doc.id} onClick={() => pick(doc)}>{doc.current_version ? 'Ganti' : 'Unggah'}</Button>}
              </div>
              {editable && doc.document_type?.allows_multiple && doc.current_version && (
                <Button size="sm" variant="ghost" leftIcon={<Plus className="size-4" />} onClick={() => action.run(() => pmbApplicant.addDocumentSlot(applicationId, doc.document_type!.id, stage), 'Slot ditambahkan.').then(docs.reload)}>Tambah berkas {doc.document_type.name}</Button>
              )}
            </div>
          )
        })}
      </div>
      {preview?.current_version && (
        <DocumentPreviewModal
          title={`${preview.document_type?.name ?? 'Dokumen'}${preview.slot > 1 ? ` #${preview.slot}` : ''}`}
          // Peserta hanya melihat versi terkini; endpoint unduhnya menerima id dokumen, bukan id versi.
          versions={[{ id: preview.id, version_no: preview.current_version.version_no, mime_type: preview.current_version.mime_type, size: preview.current_version.size, scan_status: preview.current_version.scan_status, created_at: preview.current_version.uploaded_at }]}
          initialVersionId={preview.id}
          loadUrl={() => pmbApplicant.documentUrl(applicationId, preview.id)}
          onClose={() => setPreview(null)}
        />
      )}
    </Card>
  )
}
