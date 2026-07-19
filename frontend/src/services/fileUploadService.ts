import { apiClient } from '@/services/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { FileUpload } from '@/types/fileUpload'

export const fileUploadService = {
  async upload(file: File, isPublic = false): Promise<FileUpload> {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('is_public', String(isPublic))

    const response = await apiClient.post<ApiSuccessResponse<FileUpload>>('/file-uploads', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },

  async show(id: string): Promise<FileUpload> {
    const response = await apiClient.get<ApiSuccessResponse<FileUpload>>(`/file-uploads/${id}`)
    return response.data.data
  },

  /** Backend men-stream file mentah (bukan JSON) — diambil sebagai blob lalu dipicu sebagai download browser lewat anchor sementara. */
  async download(fileUpload: FileUpload): Promise<void> {
    const response = await apiClient.get(`/file-uploads/${fileUpload.id}/download`, { responseType: 'blob' })
    const url = URL.createObjectURL(response.data as Blob)

    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = fileUpload.original_name
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(url)
  },

  async destroy(id: string): Promise<void> {
    await apiClient.delete(`/file-uploads/${id}`)
  },
}
