import { apiClient } from '@/services/api'
import type { ApiSuccessResponse } from '@/types/api'
import type { ReportResult, ReportType, ReportTypeOption } from '@/types/report'

export const reportService = {
  async index(): Promise<ReportTypeOption[]> {
    const response = await apiClient.get<ApiSuccessResponse<ReportTypeOption[]>>('/reports')
    return response.data.data
  },

  async show(type: ReportType): Promise<ReportResult> {
    const response = await apiClient.get<ApiSuccessResponse<ReportResult>>(`/reports/${type}`)
    return response.data.data
  },

  /** Downloads the CSV export and triggers a browser save via a throwaway object URL. */
  async downloadCsv(type: ReportType): Promise<void> {
    const response = await apiClient.get(`/reports/${type}`, {
      params: { export: 'csv' },
      responseType: 'blob',
    })

    const url = URL.createObjectURL(response.data as Blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `laporan-${type}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  },
}
