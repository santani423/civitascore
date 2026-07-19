import { apiClient } from '@/services/api'
import type { ApiSuccessResponse, ListParams, PaginatedResult } from '@/types/api'
import type { ApprovalRequest, ApprovalWorkflow } from '@/types/approval'
import { toQueryParams } from '@/utils/listParams'

export const approvalWorkflowService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<ApprovalWorkflow>> {
    const response = await apiClient.get<ApiSuccessResponse<ApprovalWorkflow[]>>('/approval-workflows', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<ApprovalWorkflow> {
    const response = await apiClient.get<ApiSuccessResponse<ApprovalWorkflow>>(`/approval-workflows/${id}`)
    return response.data.data
  },
}

export const approvalRequestService = {
  async index(params: ListParams = {}): Promise<PaginatedResult<ApprovalRequest>> {
    const response = await apiClient.get<ApiSuccessResponse<ApprovalRequest[]>>('/approval-requests', {
      params: toQueryParams(params),
    })
    return { data: response.data.data, meta: response.data.meta! }
  },

  async show(id: string): Promise<ApprovalRequest> {
    const response = await apiClient.get<ApiSuccessResponse<ApprovalRequest>>(`/approval-requests/${id}`)
    return response.data.data
  },
}

export const approvalRequestStepService = {
  async approve(stepId: string, comment?: string): Promise<ApprovalRequest> {
    const response = await apiClient.post<ApiSuccessResponse<ApprovalRequest>>(
      `/approval-request-steps/${stepId}/approve`,
      { comment },
    )
    return response.data.data
  },

  async reject(stepId: string, comment: string): Promise<ApprovalRequest> {
    const response = await apiClient.post<ApiSuccessResponse<ApprovalRequest>>(
      `/approval-request-steps/${stepId}/reject`,
      { comment },
    )
    return response.data.data
  },

  async delegate(stepId: string, delegateTo: string, comment?: string): Promise<ApprovalRequest> {
    const response = await apiClient.post<ApiSuccessResponse<ApprovalRequest>>(
      `/approval-request-steps/${stepId}/delegate`,
      { delegate_to: delegateTo, comment },
    )
    return response.data.data
  },
}
