import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Scholarship, ScholarshipApplication } from '@/types/scholarship'
import { toQueryParams } from '@/utils/listParams'

export const scholarshipService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Scholarship>> {
    const response = await apiClient.get<ApiSuccessResponse<Scholarship[]>>('/scholarships', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Scholarship> {
    const response = await apiClient.get<ApiSuccessResponse<Scholarship>>(`/scholarships/${id}`)
    return response.data.data
  },
}

export const scholarshipApplicationService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<ScholarshipApplication>> {
    const response = await apiClient.get<ApiSuccessResponse<ScholarshipApplication[]>>('/scholarship-applications', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },
}
