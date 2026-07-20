import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Thesis } from '@/types/thesis'
import { toQueryParams } from '@/utils/listParams'

export const thesisService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Thesis>> {
    const response = await apiClient.get<ApiSuccessResponse<Thesis[]>>('/theses', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Thesis> {
    const response = await apiClient.get<ApiSuccessResponse<Thesis>>(`/theses/${id}`)
    return response.data.data
  },
}
