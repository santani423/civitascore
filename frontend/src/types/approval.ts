export type ApprovalApproverType = 'role' | 'user' | 'position'

export type ApprovalRejectAction = 'stop_workflow' | 'return_to_previous_step'

export interface ApprovalWorkflowStep {
  id: string
  sequence: number
  name: string
  approver_type: ApprovalApproverType
  approver_role_id: string | null
  approver_user_id: string | null
  action_on_reject: ApprovalRejectAction
}

export interface ApprovalWorkflow {
  id: string
  name: string
  workflowable_type: string
  conditions: Record<string, unknown> | null
  is_active: boolean
  description: string | null
  steps: ApprovalWorkflowStep[]
  created_at: string | null
}

export type ApprovalRequestStatus = 'submitted' | 'in_progress' | 'approved' | 'rejected'

export type ApprovalHistoryEvent =
  | 'submitted'
  | 'step_advanced'
  | 'approved'
  | 'rejected'
  | 'returned_to_previous_step'

export interface ApprovalHistoryEntry {
  id: string
  event: ApprovalHistoryEvent
  actor_id: string | null
  description: string | null
  metadata: Record<string, unknown> | null
  created_at: string
}

export interface ApprovalRequest {
  id: string
  approval_workflow_id: string
  requestable_type: string
  requestable_id: string
  requested_by: string
  current_step_id: string | null
  status: ApprovalRequestStatus
  submitted_at: string | null
  completed_at: string | null
  notes: string | null
  histories: ApprovalHistoryEntry[]
  /** True only for the specific user viewing this request AND if they're the assigned approver of the current step — see ApprovalRequestResource::toArray(). */
  can_act: boolean
  created_at: string | null
}
