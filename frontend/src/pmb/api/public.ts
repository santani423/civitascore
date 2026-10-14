import { publicClient } from '@/pmb/api/http'
import type { ApplicantAccount, OpenWavePath } from '@/pmb/api/types'

async function get<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  return (await publicClient.get<{ data: T }>(url, { params })).data.data
}

async function post<T>(url: string, body?: unknown): Promise<T> {
  return (await publicClient.post<{ data: T; message: string }>(url, body)).data.data
}

export interface SiteInfo {
  university: { id: string; code: string; name: string; short_name: string | null; primary_color: string | null; secondary_color: string | null; timezone: string; website: string | null; logo_url: string | null }
  contact: { address?: string; phone?: string; email?: string; whatsapp?: string; hours?: string; map_url?: string }
  show_quota_status: boolean
  captcha: { driver: string; site_key: string | null }
  legal: { privacy_policy: { id: string; title: string; slug: string; version: number } | null; terms: { id: string; title: string; slug: string; version: number } | null }
  banners: Array<{ id: string; title: string; body: string | null }>
}

export interface PublicWave {
  id: string
  code: string
  name: string
  status: string
  is_ods: boolean
  registration_opens_at: string | null
  registration_closes_at: string | null
  payment_deadline_at: string | null
  exam_starts_on: string | null
  exam_ends_on: string | null
  announcement_at: string | null
  re_registration_opens_at: string | null
  re_registration_closes_at: string | null
  paths: Array<{ code: string; name: string; fee: string }>
}

export interface PublicPeriod {
  id: string
  code: string
  name: string
  description: string | null
  starts_on: string
  ends_on: string
  waves: PublicWave[]
}

export interface PublicProgram {
  slug: string
  name: string
  degree_level: string
  faculty: string | null
  summary: string | null
  accreditation: string | null
  study_systems: string[]
  paths: string[]
}

export interface PublicProgramDetail {
  slug: string
  name: string
  degree_level: string
  faculty: string | null
  summary: string | null
  description: string | null
  accreditation_label: string | null
  accreditation_valid_until: string | null
  degree_title: string | null
  study_duration: string | null
  career_prospects: string | null
  brochure_url: string | null
  cover_url: string | null
  paths: Array<{ path: string; path_code: string; study_system: string; quota_status: string | null }>
  fees: Array<{ component: string; code: string; amount: string; study_system: string; path: string | null; installable: boolean }>
}

export interface PublicPath {
  code: string
  name: string
  category: string
  description: string | null
  open_waves: Array<{ wave: string; closes_at: string | null; fee: string }>
}

export interface PublicPathDetail {
  code: string
  name: string
  category: string
  description: string | null
  requires_exam: boolean
  eligible_degree_levels: string[]
  waves: Array<{
    wave: string
    status: string
    opens_at: string | null
    closes_at: string | null
    announcement_at: string | null
    fee: string
    max_choices: number
    public_notes: string | null
    selection_components: string[]
    documents: Array<{ name: string; stage: string; mandatory: boolean; instructions: string | null; formats: string[]; max_size_kb: number }>
  }>
}

export interface PublicContent {
  id: string
  type: string
  slug: string
  title: string
  category: string | null
  excerpt?: string
  body: string | null
  published_at?: string | null
  publish_at?: string | null
  version?: number
}

export const pmbPublic = {
  site: () => get<SiteInfo>('/public/site'),
  periods: () => get<PublicPeriod[]>('/public/periods/active'),
  openWavePaths: () => get<OpenWavePath[]>('/public/open-wave-paths'),
  programs: () => get<PublicProgram[]>('/public/programs'),
  program: (slug: string) => get<PublicProgramDetail>(`/public/programs/${encodeURIComponent(slug)}`),
  paths: () => get<PublicPath[]>('/public/paths'),
  path: (code: string) => get<PublicPathDetail>(`/public/paths/${encodeURIComponent(code)}`),
  fees: (params?: { study_program_id?: string; study_system_id?: string }) =>
    get<Array<{ program: string; study_program_id: string; study_system: string; study_system_id: string; path: string | null; component: string; code: string; amount: string; installable: boolean; installment_plan: Array<{ percent: number; due_days: number }> | null }>>('/public/fees', params),
  contents: (type: string, params?: { search?: string; category?: string }) => get<PublicContent[]>('/public/contents', { type, ...params }),
  content: (type: string, slug: string) => get<PublicContent>(`/public/contents/${type}/${encodeURIComponent(slug)}`),
  statusCheck: (body: { registration_number: string; birth_date: string; captcha_token?: string | null }) =>
    post<{ stage: string; stage_label: string; decision_summary: { decision: string; program: string | null; study_system: string | null } | null }>('/public/status-check', body),
  verifyLetter: (code: string) =>
    get<{ valid: boolean; revoked: boolean; name_masked: string; program: string | null; study_system: string | null; letter_number: string; issued_at: string | null; revoked_at: string | null }>(`/public/loa/${encodeURIComponent(code)}`),

  // Autentikasi pendaftar (tanpa token)
  register: (body: Record<string, unknown>) => post<{ status: string }>('/applicant/auth/register', body),
  verifyEmail: (token: string) => post<{ status: string }>('/applicant/auth/email/verify', { token }),
  login: (body: { login: string; password: string; device_name?: string }) =>
    post<{ token: string; expires_at: string | null; account: ApplicantAccount }>('/applicant/auth/login', body),
  forgotPassword: (login: string, captcha_token?: string | null) => post<null>('/applicant/auth/password/forgot', { login, captcha_token }),
  resetPassword: (body: Record<string, unknown>) => post<null>('/applicant/auth/password/reset', body),
  sendOtp: (body: Record<string, unknown>) => post<null>('/applicant/auth/otp/send', body),
  verifyOtp: (body: Record<string, unknown>) => post<unknown>('/applicant/auth/otp/verify', body),
  confirmEmailChange: (token: string) => post<unknown>('/applicant/auth/email-change/confirm', { token }),
  recoveryRequest: (form: FormData) => publicClient.post('/applicant/auth/recovery-requests', form).then((r) => r.data),
}
