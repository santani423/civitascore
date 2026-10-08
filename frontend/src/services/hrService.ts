import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type {
  EducationStaffSummary,
  ExportFormat,
  HrEmployee,
  HrEmployeeDetail,
  HrEmployeePayload,
  HrOptions,
} from '@/types/hr'
import { toQueryParams } from '@/utils/listParams'
import { triggerBlobDownload } from '@/utils/download'

/** Pilihan dropdown form SDM: label enum + master aktif universitas ini (GET /hr/options). */
export const hrOptionsService = {
  async get(): Promise<HrOptions> {
    const response = await apiClient.get<ApiSuccessResponse<HrOptions>>('/hr/options')
    return response.data.data
  },
}

/**
 * Master pegawai Modul SDM (dosen + tenaga kependidikan). Endpoint tulis
 * dipakai bersama; jenis pegawai ditentukan `employee_type` saat dibuat.
 */
export const hrEmployeeService = {
  async staff(params: ListParams = {}): Promise<PaginatedResult<HrEmployee>> {
    const response = await apiClient.get<ApiSuccessResponse<HrEmployee[]>>('/hr/staff', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async staffSummary(): Promise<EducationStaffSummary> {
    const response = await apiClient.get<ApiSuccessResponse<EducationStaffSummary>>('/hr/staff/summary')
    return response.data.data
  },

  async show(id: string): Promise<HrEmployeeDetail> {
    const response = await apiClient.get<ApiSuccessResponse<HrEmployeeDetail>>(`/hr/employees/${id}`)
    return response.data.data
  },

  async create(payload: HrEmployeePayload): Promise<HrEmployeeDetail> {
    const response = await apiClient.post<ApiSuccessResponse<HrEmployeeDetail>>('/hr/employees', payload)
    return response.data.data
  },

  /** Unit kerja, jabatan, jenis, dan status aktif tidak bisa diubah di sini (lihat UpdateHrEmployeeRequest). */
  async update(id: string, payload: Partial<HrEmployeePayload>): Promise<HrEmployeeDetail> {
    const response = await apiClient.put<ApiSuccessResponse<HrEmployeeDetail>>(`/hr/employees/${id}`, payload)
    return response.data.data
  },

  async deactivate(id: string, payload: { reason: string; inactive_at?: string | null }): Promise<HrEmployeeDetail> {
    const response = await apiClient.patch<ApiSuccessResponse<HrEmployeeDetail>>(`/hr/employees/${id}/deactivate`, payload)
    return response.data.data
  },

  async activate(id: string): Promise<HrEmployeeDetail> {
    const response = await apiClient.patch<ApiSuccessResponse<HrEmployeeDetail>>(`/hr/employees/${id}/activate`)
    return response.data.data
  },

  async remove(id: string): Promise<void> {
    await apiClient.delete(`/hr/employees/${id}`)
  },

  /** Export mengikuti filter yang sedang aktif di layar (GET /hr/employees/export). */
  async export(format: ExportFormat, params: Pick<ListParams, 'search' | 'filter'>, filename: string): Promise<void> {
    const response = await apiClient.get('/hr/employees/export', {
      params: { ...toQueryParams(params), format },
      responseType: 'blob',
    })
    triggerBlobDownload(response.data as Blob, filename)
  },
}
