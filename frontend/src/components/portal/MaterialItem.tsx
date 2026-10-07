import { useState } from 'react'
import { Download, ExternalLink, FileText, PlayCircle, AlignLeft } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { downloadBlob } from '@/services/studentPortalService'
import type { NormalizedApiError } from '@/services/api'
import type { CourseMaterial } from '@/types/studentPortal'
import { formatFileSize } from '@/utils/formatters'

const ICON = { file: FileText, link: ExternalLink, video: PlayCircle, text: AlignLeft } as const

/** Satu materi: unduh berkas (dicek kepemilikan kelas di backend), buka tautan/video, atau baca teks. */
export function MaterialItem({ material }: { material: CourseMaterial }) {
  const Icon = ICON[material.type]
  const [downloading, setDownloading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [expanded, setExpanded] = useState(false)

  const download = async () => {
    if (!material.file) return
    setDownloading(true)
    setError(null)

    try {
      await downloadBlob(`/file-uploads/${material.file.id}/download`, material.file.name)
    } catch (err) {
      setError((err as NormalizedApiError).message)
    } finally {
      setDownloading(false)
    }
  }

  return (
    <div className="flex flex-col gap-2 rounded-lg border border-border p-3 sm:flex-row sm:items-start sm:justify-between">
      <div className="flex min-w-0 items-start gap-3">
        <Icon className="mt-0.5 size-4 shrink-0 text-primary" aria-hidden />
        <div className="min-w-0">
          <p className="text-sm font-medium text-ink-primary">{material.title}</p>
          <p className="text-xs text-ink-tertiary">
            {material.type_label}
            {material.file && ` · ${material.file.name} (${formatFileSize(material.file.size_bytes)})`}
          </p>
          {material.description && (material.type !== 'text' || expanded) && (
            <p className="mt-1 whitespace-pre-line text-xs text-ink-secondary">{material.description}</p>
          )}
          {error && <p className="mt-1 text-xs text-danger">{error}</p>}
        </div>
      </div>
      <div className="flex shrink-0 gap-2 self-start">
        {material.type === 'file' && material.file && (
          <Button size="sm" variant="outline" leftIcon={<Download className="size-3.5" />} isLoading={downloading} onClick={download}>
            Unduh
          </Button>
        )}
        {(material.type === 'link' || material.type === 'video') && material.url && (
          <a href={material.url} target="_blank" rel="noopener noreferrer">
            <Button size="sm" variant="outline" leftIcon={<ExternalLink className="size-3.5" />}>
              {material.type === 'video' ? 'Tonton' : 'Buka'}
            </Button>
          </a>
        )}
        {material.type === 'text' && (
          <Button size="sm" variant="ghost" onClick={() => setExpanded((value) => !value)}>
            {expanded ? 'Tutup' : 'Baca'}
          </Button>
        )}
      </div>
    </div>
  )
}
