import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { Invoice, Payment } from '@/types/finance'
import { toQueryParams } from '@/utils/listParams'

export const invoiceService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<Invoice>> {
    const response = await apiClient.get<ApiSuccessResponse<Invoice[]>>('/invoices', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<Invoice> {
    const response = await apiClient.get<ApiSuccessResponse<Invoice>>(`/invoices/${id}`)
    return response.data.data
  },
}

export interface PaymentListParams extends ListParams {
  /** Rentang tanggal top-level (bukan filter[...]) — cocok dengan cara PaymentController membacanya. */
  date_from?: string
  date_to?: string
}

export const paymentService = {
  async index(params: PaymentListParams = {}): Promise<PaginatedResult<Payment>> {
    const { date_from, date_to, ...listParams } = params
    const response = await apiClient.get<ApiSuccessResponse<Payment[]>>('/payments', {
      params: {
        ...toQueryParams(listParams),
        ...(date_from ? { date_from } : {}),
        ...(date_to ? { date_to } : {}),
      },
    })
    return { data: response.data.data, meta: response.data.meta! }
  },
}
