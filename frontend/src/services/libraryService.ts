import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Book } from '@/types/library'
import { toQueryParams } from '@/utils/listParams'

export const bookService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Book>> {
    const response = await apiClient.get<ApiSuccessResponse<Book[]>>('/books', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Book> {
    const response = await apiClient.get<ApiSuccessResponse<Book>>(`/books/${id}`)
    return response.data.data
  },
}
