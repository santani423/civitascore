import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Student } from '@/types/academic'
import { toQueryParams } from '@/utils/listParams'

export const studentService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Student>> {
    const response = await apiClient.get<ApiSuccessResponse<Student[]>>('/students', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Student> {
    const response = await apiClient.get<ApiSuccessResponse<Student>>(`/students/${id}`)
    return response.data.data
  },
}
