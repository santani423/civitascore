import { useRef, useState } from 'react'
import { Download, Trash2, Upload } from 'lucide-react'
import { Button } from '@/components/ui/Button'
import { Badge, type BadgeVariant } from '@/components/ui/Badge'
import { Alert } from '@/components/ui/Alert'
import { fileUploadService } from '@/services/fileUploadService'
import type { NormalizedApiError } from '@/services/api'
import type { FileUpload, FileUploadStatus } from '@/types/fileUpload'
import { formatFileSize } from '@/utils/formatters'

const STATUS_LABEL: Record<FileUploadStatus, string> = {
  pending: 'Menunggu',
  scanning: 'Memindai',
  clean: 'Aman',
  rejected: 'Ditolak',
}

const STATUS_VARIANT: Record<FileUploadStatus, BadgeVariant> = {
  pending: 'neutral',
  scanning: 'warning',
  clean: 'success',
  rejected: 'danger',
}

export interface FileUploadFieldProps {
  label?: string
  value: FileUpload | null
  onChange: (file: FileUpload | null) => void
  isPublic?: boolean
}

/** Komponen upload reusable di atas modul FileManagement — satu file per instance. */
export function FileUploadField({ label, value, onChange, isPublic = false }: FileUploadFieldProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const [isUploading, setIsUploading] = useState(false)
  const [isDownloading, setIsDownloading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const handleFileSelect = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    event.target.value = ''
    if (!file) return

    setError(null)
    setIsUploading(true)

    try {
      const uploaded = await fileUploadService.upload(file, isPublic)
      onChange(uploaded)
    } catch (uploadError) {
      setError((uploadError as NormalizedApiError).message ?? 'Gagal mengunggah berkas.')
    } finally {
      setIsUploading(false)
    }
  }

  const handleDownload = async () => {
    if (!value) return
    setError(null)
    setIsDownloading(true)

    try {
      await fileUploadService.download(value)
    } catch (downloadError) {
      setError((downloadError as NormalizedApiError).message ?? 'Gagal mengunduh berkas.')
    } finally {
      setIsDownloading(false)
    }
  }

  const handleRemove = async () => {
    if (!value) return
    setError(null)

    try {
      await fileUploadService.destroy(value.id)
      onChange(null)
    } catch (removeError) {
      setError((removeError as NormalizedApiError).message ?? 'Gagal menghapus berkas.')
    }
  }

  return (
    <div>
      {label && <label className="mb-1.5 block text-sm font-medium text-ink-primary">{label}</label>}

      {error && (
        <Alert variant="danger" className="mb-2" onDismiss={() => setError(null)}>
          {error}
        </Alert>
      )}

      {value ? (
        <div className="flex items-center justify-between gap-3 rounded-lg border border-border bg-surface px-3 py-2.5">
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-ink-primary">{value.original_name}</p>
            <div className="mt-0.5 flex items-center gap-2 text-xs text-ink-tertiary">
              <span>{formatFileSize(value.size_bytes)}</span>
              <Badge variant={STATUS_VARIANT[value.status]}>{STATUS_LABEL[value.status]}</Badge>
            </div>
          </div>
          <div className="flex shrink-0 items-center gap-1.5">
            <Button variant="outline" size="sm" isLoading={isDownloading} onClick={handleDownload}>
              <Download className="size-3.5" />
            </Button>
            <Button variant="outline" size="sm" className="text-danger" onClick={handleRemove}>
              <Trash2 className="size-3.5" />
            </Button>
          </div>
        </div>
      ) : (
        <Button
          type="button"
          variant="outline"
          leftIcon={<Upload className="size-4" />}
          isLoading={isUploading}
          onClick={() => inputRef.current?.click()}
        >
          Unggah Berkas
        </Button>
      )}

      <input ref={inputRef} type="file" className="hidden" onChange={handleFileSelect} />
    </div>
  )
}
