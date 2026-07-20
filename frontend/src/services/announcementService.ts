import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Announcement } from '@/types/announcement'
import { toQueryParams } from '@/utils/listParams'

export const announcementService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Announcement>> {
    const response = await apiClient.get<ApiSuccessResponse<Announcement[]>>('/announcements', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Announcement> {
    const response = await apiClient.get<ApiSuccessResponse<Announcement>>(`/announcements/${id}`)
    return response.data.data
  },
}
