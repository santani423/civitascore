import { applicantClient, idempotencyKey } from '@/pmb/api/http'
import type { ApplicantAccount, ApplicantDocument, ApplicationDetail, ApplicationSummary, CompletenessStep, Invoice, OpenWavePath, Paginated, PaymentChannel, Transaction } from '@/pmb/api/types'

async function get<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return (await applicantClient.get<{ data: T }>(url, { params })).data.data
}

async function send<T>(method: 'post' | 'put' | 'patch' | 'delete', url: string, body?: unknown, idempotent = false): Promise<T> {
  const headers = idempotent ? { 'Idempotency-Key': idempotencyKey() } : undefined
  const response = await applicantClient.request<{ data: T }>({ method, url, data: body, headers })

  return response.data?.data as T
}

export interface MeResponse {
  account: ApplicantAccount & { verified: boolean }
  applications: Array<{ id: string; registration_number: string | null; path: string | null; wave: string | null; current_stage: string }>
  marketing_consent: boolean
  unread_notifications: number
}

export interface Education {
  id: string
  level: string
  institution_name: string
  institution_npsn: string | null
  school_type: string | null
  major: string | null
  graduation_year: number | null
  is_graduating: boolean
  nisn: string | null
  gpa: string | null
  credits_earned: number | null
  city: string | null
  province: string | null
  country: string | null
  report_scores: Array<{ semester: number; subject_code: string; subject_name: string | null; score: string }>
}

export interface Guardian {
  id: string
  relationship: string
  full_name: string
  phone: string | null
  email: string | null
  nik: string | null
  birth_date: string | null
  education_level: string | null
  occupation: string | null
  income_range: string | null
  is_deceased: boolean
}

export interface Achievement {
  id: string
  name: string
  category: string
  level: string
  rank: string
  year: number
  organizer: string | null
  application_document_id: string | null
  document?: { status: string } | null
}

export interface ExamParticipation {
  id: string
  participant_number: string
  title: string
  mode: string
  instructions: string | null
  session: { code: string; starts_at: string; ends_at: string; online_url: string | null }
  location: { venue: string | null; room: string; address: string | null; map_url: string | null } | null
  attendance_status: string
  card_available: boolean
  reschedule_count: number
  cbt: { started: boolean; submitted: boolean; duration_minutes: number | null } | null
}

export interface CbtAttempt {
  attempt_id: string
  started_at: string
  deadline_at: string
  submitted_at: string | null
  server_time: string
  questions: Array<{ id: string; question: string; options: Array<{ key: string; text: string }>; answer: string | null }>
}

export interface ResultData {
  decision: string
  version: number
  program: string | null
  study_system: string | null
  waitlisted: boolean
  published_at: string | null
  corrected: boolean
  correction_note: string | null
  letter_available: boolean
  re_registration: { status: string; deadline_at: string; opens_at: string | null } | null
  scholarship_award: { percent: number | null } | null
}

export interface ReRegistrationData {
  id: string
  status: string
  deadline_at: string
  opens_at: string | null
  program: string | null
  study_system: string | null
  checks: Record<string, { status: string; evaluation: string; note: string | null }>
  invoice: Invoice | null
  data: Record<string, string | boolean | null>
  revision_targets: { fields?: string[]; documents?: string[] } | null
  declarations: Array<{ id: string; title: string; body: string; version: number }>
  conversion: { status: string; nim: string | null } | null
  orientation: { title: string; body: string } | null
  lock_version: number
}

export interface ChatThreadSummary {
  id: string
  subject: string
  status: string
  application_id: string | null
  last_message_at: string | null
  unread: number
}

export interface ChatMessage {
  id: string
  sender_type: 'applicant' | 'user' | 'system'
  body: string
  read_at: string | null
  created_at: string | null
  attachment_url: string | null
}

export const pmbApplicant = {
  // Sesi & akun
  logout: () => send<null>('post', '/auth/logout'),
  logoutAll: () => send<null>('post', '/auth/logout-all'),
  sessions: () => get<Array<{ id: string | number; device_name: string; last_used_at: string | null; created_at: string | null; current: boolean }>>('/auth/sessions'),
  revokeSession: (id: string | number) => send<null>('delete', `/auth/sessions/${id}`),
  resendVerification: () => send<null>('post', '/auth/email/resend'),
  sendPhoneOtp: () => send<null>('post', '/auth/phone/otp', { purpose: 'verify_phone' }),
  verifyPhoneOtp: (code: string) => send<null>('post', '/auth/phone/verify', { purpose: 'verify_phone', code }),
  me: () => get<MeResponse>('/me'),
  updateMe: (body: { name?: string; phone?: string | null }) => send<ApplicantAccount>('patch', '/me', body),
  changePassword: (body: { current_password: string; password: string; password_confirmation: string }) => send<null>('put', '/me/password', body),
  requestEmailChange: (body: { new_email: string; password: string }) => send<null>('post', '/me/email-change', body),
  marketingConsent: (granted: boolean) => send<{ granted: boolean }>('put', '/me/marketing-consent', { granted }),
  dataExport: () => get<Record<string, unknown>>('/me/data-export'),
  deactivate: (password: string) => send<null>('post', '/me/deactivate', { password }),

  // Profil
  profile: () => get<Record<string, unknown> & { identity_locked: boolean; locked_fields: string[]; nik_warning: string | null; lock_version: number }>('/profile'),
  saveProfile: (body: Record<string, unknown>) => send<Record<string, unknown>>('put', '/profile', body),
  claimIdentity: (body: { identity_number: string; note?: string }) => send<null>('post', '/profile/identity-claim', body),
  educations: () => get<Education[]>('/educations'),
  storeEducation: (body: Record<string, unknown>) => send<Education>('post', '/educations', body),
  updateEducation: (id: string, body: Record<string, unknown>) => send<Education>('put', `/educations/${id}`, body),
  destroyEducation: (id: string) => send<null>('delete', `/educations/${id}`),
  reportScores: (id: string, scores: Array<{ semester: number; subject_code: string; subject_name?: string; score: number }>) => send<Education>('put', `/educations/${id}/report-scores`, { scores }),
  guardians: () => get<Guardian[]>('/guardians'),
  storeGuardian: (body: Record<string, unknown>) => send<Guardian>('post', '/guardians', body),
  updateGuardian: (id: string, body: Record<string, unknown>) => send<Guardian>('put', `/guardians/${id}`, body),
  destroyGuardian: (id: string) => send<null>('delete', `/guardians/${id}`),
  achievements: () => get<Achievement[]>('/achievements'),
  storeAchievement: (body: Record<string, unknown>) => send<Achievement>('post', '/achievements', body),
  updateAchievement: (id: string, body: Record<string, unknown>) => send<Achievement>('put', `/achievements/${id}`, body),
  destroyAchievement: (id: string) => send<null>('delete', `/achievements/${id}`),
  notifications: (page = 1) => applicantClient.get<Paginated<{ id: string; title: string | null; message: string | null; event_key: string | null; link: string | null; read_at: string | null; created_at: string | null }>>('/notifications', { params: { page } }).then((r) => r.data),
  readNotification: (id: string) => send<null>('post', `/notifications/${id}/read`),

  // Pendaftaran
  openWavePaths: () => get<OpenWavePath[]>('/open-wave-paths'),
  applications: () => get<ApplicationSummary[]>('/applications'),
  createApplication: (wavePathId: string) => send<ApplicationSummary>('post', '/applications', { wave_path_id: wavePathId }, true),
  application: (id: string) => get<ApplicationDetail>(`/applications/${id}`),
  saveSection: (id: string, section: 'identity' | 'education' | 'supporting', data: Record<string, unknown>, lockVersion: number) =>
    send<{ lock_version: number }>('patch', `/applications/${id}`, { section, data, lock_version: lockVersion }),
  saveChoices: (id: string, choices: Array<{ priority: number; study_program_id: string; study_system_id: string }>, lockVersion: number) =>
    send<{ lock_version: number }>('put', `/applications/${id}/choices`, { choices, lock_version: lockVersion }),
  saveAnswers: (id: string, answers: Record<string, unknown>, lockVersion: number) => send<{ lock_version: number }>('put', `/applications/${id}/answers`, { answers, lock_version: lockVersion }),
  completeness: (id: string) => get<{ steps: CompletenessStep[]; can_submit: boolean }>(`/applications/${id}/completeness`),
  submit: (id: string, declarations: string[], lockVersion: number) =>
    send<{ id: string; registration_number: string; form_status: string; current_stage: string; lock_version: number }>('post', `/applications/${id}/submit`, { declarations, lock_version: lockVersion }, true),
  withdraw: (id: string, reason?: string) => send<ApplicationSummary>('post', `/applications/${id}/withdraw`, { reason }),
  discard: (id: string) => send<null>('delete', `/applications/${id}`),
  notes: (id: string) =>
    get<{ notes: Array<{ context: string; body: string; target_fields: Record<string, string[]> | null; due_at: string | null; created_at: string | null }>; document_reviews: Array<{ document: string | null; decision: string; reason_code: string | null; note: string | null; created_at: string | null }>; rejection_reason_code: string | null }>(`/applications/${id}/notes`),

  // Dokumen
  documents: (id: string, stage: 'application' | 're_registration' = 'application') => get<ApplicantDocument[]>(`/applications/${id}/documents`, { stage }),
  addDocumentSlot: (id: string, documentTypeId: string, stage: string) => send<{ id: string; slot: number }>('post', `/applications/${id}/documents/slots`, { document_type_id: documentTypeId, stage }),
  uploadDocument: (id: string, documentId: string, file: File, achievementId?: string) => {
    const form = new FormData()
    form.append('file', file)
    if (achievementId) form.append('achievement_id', achievementId)

    return applicantClient.post<{ data: unknown }>(`/applications/${id}/documents/${documentId}/versions`, form).then((r) => r.data.data)
  },
  documentUrl: (id: string, documentId: string) => get<{ url: string }>(`/applications/${id}/documents/${documentId}/download`),

  // Pembayaran
  paymentChannels: () => get<PaymentChannel[]>('/payment-channels'),
  invoices: (applicationId: string) => get<Invoice[]>(`/applications/${applicationId}/invoices`),
  applyVoucher: (invoiceId: string, code: string) => send<Invoice>('post', `/invoices/${invoiceId}/voucher`, { code }, true),
  createTransaction: (invoiceId: string, channel: string, amount?: string) => send<{ transaction: Transaction; invoice: Invoice }>('post', `/invoices/${invoiceId}/transactions`, { channel, amount }, true),
  receipt: (invoiceId: string) => get<{ url: string }>(`/invoices/${invoiceId}/receipt`),
  transaction: (id: string) => get<Transaction>(`/transactions/${id}`),
  cancelTransaction: (id: string) => send<Transaction>('post', `/transactions/${id}/cancel`),
  manualProof: (id: string, form: FormData) =>
    applicantClient.post<{ data: { id: string; status: string }; message: string }>(`/transactions/${id}/manual-proof`, form, { headers: { 'Idempotency-Key': idempotencyKey() } }).then((r) => r.data),

  // Ujian
  exams: (applicationId: string) => get<{ requires_exam: boolean; exam_window: { starts_on: string | null; ends_on: string | null }; participations: ExamParticipation[] }>(`/applications/${applicationId}/exams`),
  examCard: (participantId: string) => get<{ url: string }>(`/exam-participants/${participantId}/card`),
  reschedule: (participantId: string, form: FormData) => applicantClient.post(`/exam-participants/${participantId}/reschedule-requests`, form).then((r) => r.data),
  cbtStart: (participantId: string) => send<CbtAttempt>('post', `/exam-participants/${participantId}/cbt/start`),
  cbtAnswer: (participantId: string, questionId: string, option: string | null) => send<{ saved_at: string }>('put', `/exam-participants/${participantId}/cbt/answers`, { question_id: questionId, option }),
  cbtViolation: (participantId: string) => send<null>('post', `/exam-participants/${participantId}/cbt/violations`),
  cbtSubmit: (participantId: string) => send<{ submitted_at: string | null }>('post', `/exam-participants/${participantId}/cbt/submit`),

  // Hasil & daftar ulang
  result: (applicationId: string) => get<ResultData>(`/applications/${applicationId}/result`),
  letter: (applicationId: string) => get<{ url: string; letter_number: string }>(`/applications/${applicationId}/loa`),
  reRegistration: (applicationId: string) => get<ReRegistrationData>(`/applications/${applicationId}/re-registration`),
  acceptOffer: (applicationId: string, declarations: string[], lockVersion: number) =>
    send<{ status: string; withdrawn_application_ids: string[] }>('post', `/applications/${applicationId}/re-registration/accept`, { declarations, lock_version: lockVersion }, true),
  declineOffer: (applicationId: string, reason: string) => send<{ status: string }>('post', `/applications/${applicationId}/re-registration/decline`, { reason, confirm: true }, true),
  saveReRegistrationData: (applicationId: string, data: Record<string, unknown>, lockVersion: number) =>
    send<{ status: string; lock_version: number }>('put', `/applications/${applicationId}/re-registration/data`, { ...data, lock_version: lockVersion }),
  submitReRegistration: (applicationId: string, lockVersion: number) => send<{ status: string }>('post', `/applications/${applicationId}/re-registration/submit`, { lock_version: lockVersion }),
  registrationProof: (applicationId: string) => get<{ url: string }>(`/applications/${applicationId}/re-registration/proof`),

  // Pesan
  threads: () => get<ChatThreadSummary[]>('/chat-threads'),
  openThread: (form: FormData) => applicantClient.post<{ data: { id: string } }>('/chat-threads', form).then((r) => r.data.data),
  messages: (threadId: string) => get<{ thread: { id: string; subject: string; status: string }; messages: ChatMessage[] }>(`/chat-threads/${threadId}/messages`),
  reply: (threadId: string, form: FormData) => applicantClient.post(`/chat-threads/${threadId}/messages`, form).then((r) => r.data),
}
