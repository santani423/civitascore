import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Alumni } from '@/types/alumni'
import { toQueryParams } from '@/utils/listParams'

export const alumniService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Alumni>> {
    const response = await apiClient.get<ApiSuccessResponse<Alumni[]>>('/alumni', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Alumni> {
    const response = await apiClient.get<ApiSuccessResponse<Alumni>>(`/alumni/${id}`)
    return response.data.data
  },
}
