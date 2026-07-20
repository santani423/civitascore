import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Internship } from '@/types/internship'
import { toQueryParams } from '@/utils/listParams'

export const internshipService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Internship>> {
    const response = await apiClient.get<ApiSuccessResponse<Internship[]>>('/internships', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Internship> {
    const response = await apiClient.get<ApiSuccessResponse<Internship>>(`/internships/${id}`)
    return response.data.data
  },
}
