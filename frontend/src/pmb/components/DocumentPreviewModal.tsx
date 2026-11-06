import { useState, type ReactNode } from 'react'
import { ExternalLink, ZoomIn, ZoomOut } from 'lucide-react'
import { Modal } from '@/components/ui/Modal'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { KeyValue } from '@/pmb/components/KeyValue'
import { StatusBadge } from '@/pmb/components/StatusBadge'
import { useLoad } from '@/pmb/components/useLoad'
import { dateTime } from '@/pmb/lib/format'
import { formatFileSize } from '@/utils/formatters'
import { cn } from '@/utils/cn'

export interface PreviewVersion {
  id: string
  version_no: number
  mime_type: string | null
  size: number | null
  scan_status: string | null
  created_at: string | null
}

export interface DocumentPreviewModalProps {
  title: string
  /** Urutan tampilan pemilih versi (API mengirim terbaru dulu). */
  versions: PreviewVersion[]
  initialVersionId: string
  /** Mengambil URL bertanda tangan untuk satu versi — tiap pemanggilan tercatat di audit. */
  loadUrl: (versionId: string) => Promise<{ url: string }>
  /** Info tambahan di panel samping (status dokumen, riwayat tinjauan, …). */
  details?: ReactNode
  /** Aksi di panel samping, mis. tombol "Tinjau". */
  actions?: ReactNode
  onClose: () => void
}

const formatLabel = (mime: string | null) => (mime === 'application/pdf' ? 'PDF' : mime?.startsWith('image/') ? mime.split('/')[1].toUpperCase() : mime ?? '—')

/** Pratinjau dokumen di dalam halaman: gambar & PDF dirender inline, berkas lain lewat tab baru. */
export function DocumentPreviewModal({ title, versions, initialVersionId, loadUrl, details, actions, onClose }: DocumentPreviewModalProps) {
  const [versionId, setVersionId] = useState(initialVersionId)
  const [zoomed, setZoomed] = useState(false)
  const version = versions.find((v) => v.id === versionId) ?? versions[0]
  const file = useLoad(() => loadUrl(version.id), [version.id])
  const url = file.data?.url
  const isImage = Boolean(version.mime_type?.startsWith('image/'))
  const isPdf = version.mime_type === 'application/pdf'
  const latestId = versions.reduce((a, b) => (b.version_no > a.version_no ? b : a), versions[0]).id

  return (
    <Modal open onClose={onClose} title={`${title} · v${version.version_no}`} className="max-w-6xl">
      <div className="grid gap-4 lg:grid-cols-[1fr_17rem]">
        <div className="flex flex-col gap-2">
          {versions.length > 1 && (
            <div className="flex flex-wrap gap-1.5" role="tablist" aria-label="Versi dokumen">
              {versions.map((v) => (
                <button
                  key={v.id}
                  type="button"
                  role="tab"
                  aria-selected={v.id === version.id}
                  onClick={() => { setVersionId(v.id); setZoomed(false) }}
                  className={cn('rounded-lg border px-2.5 py-1 text-xs font-medium', v.id === version.id ? 'border-primary bg-primary text-white' : 'border-border text-ink-secondary hover:bg-surface-hover')}
                >
                  v{v.version_no}{v.id === latestId ? ' (terbaru)' : ''}
                </button>
              ))}
            </div>
          )}

          <div className={cn('relative flex min-h-[60vh] rounded-lg border border-border bg-surface-hover', zoomed ? 'max-h-[70vh] overflow-auto' : 'items-center justify-center overflow-hidden')}>
            {file.loading && <p className="m-auto text-sm text-ink-tertiary">Memuat dokumen…</p>}
            {file.error && <div className="m-auto p-4"><Alert variant="danger">{file.error.message}</Alert></div>}
            {url && !file.loading && (isImage
              ? (
                <button type="button" className={cn('block', zoomed ? 'cursor-zoom-out' : 'm-auto cursor-zoom-in')} onClick={() => setZoomed(!zoomed)} title={zoomed ? 'Perkecil' : 'Perbesar'}>
                  <img src={url} alt={`${title} v${version.version_no}`} className={zoomed ? 'max-w-none' : 'max-h-[68vh] w-auto object-contain'} />
                </button>
              )
              : isPdf
                ? <iframe title={`${title} v${version.version_no}`} src={url} className="h-[70vh] w-full rounded-lg" />
                : <p className="m-auto p-4 text-sm text-ink-secondary">Pratinjau tidak tersedia untuk tipe berkas ini. Gunakan “Buka di tab baru”.</p>)}
          </div>
          {isImage && url && <p className="text-xs text-ink-tertiary">Klik gambar untuk {zoomed ? 'kembali ke ukuran pas layar' : 'melihat ukuran asli'}.</p>}
        </div>

        <div className="flex flex-col gap-4">
          <KeyValue columns={1} items={[
            ['Versi', `v${version.version_no}${version.id === latestId ? ' (terbaru)' : ''}`],
            ['Format', `${formatLabel(version.mime_type)}${version.size ? ` · ${formatFileSize(version.size)}` : ''}`],
            ['Diunggah', dateTime(version.created_at)],
            ['Pemindaian', version.scan_status ? <StatusBadge key="scan" status={version.scan_status} /> : '—'],
          ]} />
          {details}
          <div className="mt-auto flex flex-col gap-2">
            {actions}
            {isImage && url && (
              <Button variant="outline" size="sm" leftIcon={zoomed ? <ZoomOut className="size-4" /> : <ZoomIn className="size-4" />} onClick={() => setZoomed(!zoomed)}>
                {zoomed ? 'Pas layar' : 'Ukuran asli'}
              </Button>
            )}
            {url && (
              <a href={url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center justify-center gap-1 text-sm text-primary hover:underline">
                <ExternalLink className="size-3.5" />Buka di tab baru
              </a>
            )}
          </div>
        </div>
      </div>
    </Modal>
  )
}
