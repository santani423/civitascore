export type FileUploadStatus = 'pending' | 'scanning' | 'clean' | 'rejected'

export interface FileUpload {
  id: string
  original_name: string
  mime_type: string
  extension: string
  size_bytes: number
  status: FileUploadStatus
  is_public: boolean
  uploaded_by: string | null
  created_at: string | null
}
