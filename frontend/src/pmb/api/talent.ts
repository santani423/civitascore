import { pmbAdmin, type Params } from '@/pmb/api/admin'

/** Izin One Atma Talent (docs/one-atma-talent/03 §4). Nama mengikuti aksi standar PermissionAction. */
export const TP = {
  ASSESSMENTS_READ: 'pmb_assessments.read',
  ASSESSMENTS_CREATE: 'pmb_assessments.create',
  ASSESSMENTS_MANAGE: 'pmb_assessments.update',
  ASSESSMENTS_ARCHIVE: 'pmb_assessments.delete',
  ASSESSMENTS_APPROVE: 'pmb_assessments.approve',
  ASSESSMENTS_PUBLISH: 'pmb_assessments.publish',
  QUESTIONS_READ: 'pmb_assessment_questions.read',
  QUESTIONS_CREATE: 'pmb_assessment_questions.create',
  QUESTIONS_MANAGE: 'pmb_assessment_questions.update',
  QUESTIONS_DELETE: 'pmb_assessment_questions.delete',
  RESULTS_READ: 'pmb_assessment_results.read',
  RESULTS_REVIEW: 'pmb_assessment_results.update',
  RESULTS_PUBLISH: 'pmb_assessment_results.publish',
  SESSIONS_READ: 'pmb_assessment_sessions.read',
  SESSIONS_INVALIDATE: 'pmb_assessment_sessions.reject',
  AUDIT_READ: 'pmb_assessment_audit_logs.read',
  APPLICATIONS_READ: 'pmb_applications.read',
  SENSITIVE_READ: 'pmb_sensitive.read',
} as const

export type VersionStatus = 'draft' | 'in_review' | 'approved' | 'rejected' | 'published' | 'archived'
export type SessionStatus = 'in_progress' | 'submitted' | 'expired' | 'published' | 'invalidated'
export type ReviewStatus = 'none' | 'flagged' | 'reviewed'

export interface AssessmentTypeRow {
  id: string
  code: string
  name: string
  domain: string
  domain_supports_scoring: boolean
  description: string | null
  is_active: boolean
  versions_count?: number
  open_version?: { id: string; version_no: number; status: VersionStatus } | null
  published_version?: { id: string; version_no: number; title: string } | null
  sessions_count?: number
}

export interface VersionRow {
  id: string
  version_no: number
  title: string
  purpose: 'exploratory' | 'admissions'
  status: VersionStatus
  dimensions_count: number
  items_count: number
  sessions_count: number
  submitted_at: string | null
  approved_at: string | null
  published_at: string | null
  archived_at: string | null
  assessment_type_id: string
}

export interface DimensionRow {
  id: string
  code: string
  name: string
  description: string | null
  interpretation: string | null
  sort_order: number
  items_count: number
}

export interface VersionDetail extends VersionRow {
  instructions: string | null
  change_note: string | null
  scoring_config: Record<string, unknown>
  duration_minutes: number | null
  available_from: string | null
  available_until: string | null
  period_id: string | null
  parent_version_id: string | null
  type: { id: string; code: string; name: string; domain: string }
  dimensions: DimensionRow[]
  sessions_by_status: Record<string, number>
  readiness: { ok: boolean; code: string | null; message: string }
  approval: {
    request_id: string
    status: string
    current_step: string | null
    steps: Array<{ sequence: number; name: string | null; status: string; acted_at: string | null }>
    submitted_at: string | null
    completed_at: string | null
    can_decide: boolean
  } | null
  history: Array<{ action: string; user: string | null; old: Record<string, unknown> | null; new: Record<string, unknown> | null; reason: string | null; at: string }>
  can_edit: boolean
  can_delete: boolean
}

export interface QuestionRow {
  id: string
  code: string | null
  stem: string
  question_type: string
  status: 'draft' | 'approved'
  is_reversed: boolean
  sort_order: number
  source_item_id: string | null
  in_use: boolean
  editable: boolean | null
  dimension: { id: string; code: string; name: string } | null
  version: { id: string; version_no: number; title: string; status: VersionStatus; assessment_type_id: string } | null
  created_at: string | null
}

export interface DashboardData {
  totals: Record<'assessments' | 'published_versions' | 'participants' | 'not_started' | 'in_progress' | 'submitted' | 'pending_scoring' | 'needs_review' | 'published' | 'expired' | 'invalidated', number>
  status_distribution: Record<SessionStatus, number>
  completion_trend: Array<{ date: string; started: number; submitted: number }>
  participants_by_period: Array<{ period_id: string; period_name: string; participants: number }>
  result_distribution: { published_total: number; suppressed: boolean; min_published: number; items: Array<{ code: string; name: string; total: number }> } | null
}

export interface SessionRow {
  id: string
  registration_number: string | null
  applicant_name: string | null
  study_program: { id: string; name: string; code: string } | null
  period: { id: string; name: string } | null
  assessment: { id: string | null; name: string | null }
  version: { id: string; title: string; version_no: number; status: VersionStatus }
  mode: string
  started_at: string
  submitted_at: string | null
  status: SessionStatus
  review_status: ReviewStatus
  result_status: 'pending' | 'scored' | 'published'
  holland_code: string | null
}

export interface SessionDetail extends SessionRow {
  version_id: string
  deadline_at: string | null
  published_at: string | null
  scored_at: string | null
  scoring_engine: string | null
  dominant_dimensions: string[] | null
  invalidation: { at: string; by: string | null; reason: string | null } | null
  scores: Array<{ dimension_code: string; item_count: number; raw_score: number; mean_score: number; percent_score: number; rank: number }>
  events: Array<{ type: string; actor: string; payload: Record<string, unknown> | null; at: string }>
  review_notes: Array<{ kind: string; body: string | null; author: string | null; at: string }>
  responses: Array<{ item_id: string; question: string; dimension_code: string; value: number; reversed: boolean }> | null
  responses_restricted: boolean
}

export interface ReviewPayload {
  session_id: string
  status: SessionStatus
  review_status: ReviewStatus
  version: { id: string; title: string; version_no: number; status: VersionStatus }
  scoring_engine: string | null
  holland_code: string | null
  dominant_dimensions: string[] | null
  scored_at: string | null
  scores: SessionDetail['scores']
  checks: Array<{ key: string; label: string; ok: boolean }>
  can_publish: boolean
  review_notes: SessionDetail['review_notes']
  publication_history: Array<{ at: string; payload: Record<string, unknown> | null }>
}

export interface AuditRow {
  id: string
  action: string
  auditable_type: string
  auditable_id: string
  user: string | null
  old_values: Record<string, unknown> | null
  new_values: Record<string, unknown> | null
  reason: string | null
  at: string
}

export interface ItemPreview {
  id: string
  stem: string
  scale: { min: number; max: number }
}

const ev = (id: string) => encodeURIComponent(id)

export const talentApi = {
  dashboard: (params: Params) => pmbAdmin.get<DashboardData>('assessment-dashboard', params),

  types: () => pmbAdmin.get<AssessmentTypeRow[]>('assessment-types'),
  createType: (body: Record<string, unknown>) => pmbAdmin.post<AssessmentTypeRow>('assessment-types', body),
  updateType: (id: string, body: Record<string, unknown>) => pmbAdmin.patch<AssessmentTypeRow>(`assessment-types/${ev(id)}`, body),

  versions: (typeId: string) => pmbAdmin.get<VersionRow[]>(`assessment-types/${ev(typeId)}/versions`),
  createVersion: (typeId: string, body: Record<string, unknown>) => pmbAdmin.post<VersionRow>(`assessment-types/${ev(typeId)}/versions`, body),
  version: (id: string) => pmbAdmin.get<VersionDetail>(`assessment-versions/${ev(id)}`),
  updateVersion: (id: string, body: Record<string, unknown>) => pmbAdmin.patch<VersionRow>(`assessment-versions/${ev(id)}`, body),
  cloneVersion: (id: string, changeNote?: string) => pmbAdmin.post<VersionRow>(`assessment-versions/${ev(id)}/clone`, { change_note: changeNote || undefined }),
  diff: (id: string, other: string) => pmbAdmin.get<{
    from: { id: string; version_no: number; status: string }
    to: { id: string; version_no: number; status: string }
    dimensions: { added: string[]; removed: string[]; changed: Array<{ code: string; fields: string[] }> }
    items: { added: Array<{ key: string; stem: string }>; removed: Array<{ key: string; stem: string }>; changed: Array<{ key: string; fields: string[]; from: string; to: string }> }
  }>(`assessment-versions/${ev(id)}/diff/${ev(other)}`),
  submit: (id: string, note?: string) => pmbAdmin.post<VersionRow>(`assessment-versions/${ev(id)}/submit`, { note: note || undefined }),
  decide: (id: string, decision: 'approve' | 'reject' | 'return', comment?: string) => pmbAdmin.post<VersionRow>(`assessment-versions/${ev(id)}/decision`, { decision, comment: comment || undefined }),
  publish: (id: string) => pmbAdmin.post<VersionRow>(`assessment-versions/${ev(id)}/publish`),
  archive: (id: string, reason: string) => pmbAdmin.post<VersionRow>(`assessment-versions/${ev(id)}/archive`, { reason }),
  deleteVersion: (id: string) => pmbAdmin.del(`assessment-versions/${ev(id)}`),

  createDimension: (versionId: string, body: Record<string, unknown>) => pmbAdmin.post<DimensionRow>(`assessment-versions/${ev(versionId)}/dimensions`, body),
  updateDimension: (id: string, body: Record<string, unknown>) => pmbAdmin.patch<DimensionRow>(`assessment-dimensions/${ev(id)}`, body),
  deleteDimension: (id: string) => pmbAdmin.del(`assessment-dimensions/${ev(id)}`),

  questions: (params: Params) => pmbAdmin.list<QuestionRow>('assessment-questions', params),
  question: (id: string) => pmbAdmin.get<QuestionRow & { preview: ItemPreview }>(`assessment-items/${ev(id)}`),
  preview: (id: string) => pmbAdmin.get<ItemPreview>(`assessment-items/${ev(id)}/preview`),
  createItem: (versionId: string, body: Record<string, unknown>) => pmbAdmin.post<QuestionRow>(`assessment-versions/${ev(versionId)}/items`, body),
  updateItem: (id: string, body: Record<string, unknown>) => pmbAdmin.patch<QuestionRow>(`assessment-items/${ev(id)}`, body),
  deleteItem: (id: string) => pmbAdmin.del(`assessment-items/${ev(id)}`),
  copyItem: (id: string, versionId: string) => pmbAdmin.post<QuestionRow>(`assessment-items/${ev(id)}/copy`, { version_id: versionId }),

  sessions: (params: Params) => pmbAdmin.list<SessionRow>('assessment-sessions', params),
  session: (id: string) => pmbAdmin.get<SessionDetail>(`assessment-sessions/${ev(id)}`),
  review: (id: string) => pmbAdmin.get<ReviewPayload>(`assessment-sessions/${ev(id)}/review`),
  addNote: (id: string, body: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/notes`, { body }),
  flag: (id: string, reason: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/flag`, { reason }),
  unflag: (id: string, note: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/unflag`, { note }),
  completeReview: (id: string, note?: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/review-complete`, { note: note || undefined }),
  invalidate: (id: string, reason: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/invalidate`, { reason }),
  publishSession: (id: string) => pmbAdmin.post(`assessment-sessions/${ev(id)}/publish`),

  audit: (params: Params) => pmbAdmin.list<AuditRow>('assessment-audit-logs', params),
}

/** Label status untuk badge dan filter. */
export const VERSION_STATUS_LABEL: Record<VersionStatus, string> = {
  draft: 'Draft', in_review: 'Ditelaah', approved: 'Disetujui', rejected: 'Ditolak', published: 'Terbit', archived: 'Diarsipkan',
}

export const SESSION_STATUS_LABEL: Record<SessionStatus, string> = {
  in_progress: 'Sedang dikerjakan', submitted: 'Terkirim', expired: 'Waktu habis', published: 'Dipublikasikan', invalidated: 'Diinvalidasi',
}

export const REVIEW_STATUS_LABEL: Record<ReviewStatus, string> = { none: 'Belum direview', flagged: 'Perlu review', reviewed: 'Sudah direview' }
