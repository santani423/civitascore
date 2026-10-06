import { apiClient } from '@/services/api'
import type { ApiPaginationMeta, ApiSuccessResponse, PaginatedResult } from '@/types/api'
import type {
  AcademicHistoryEvent,
  AcademicSummary,
  AnnouncementFilter,
  AssignmentPayload,
  AssignmentSubmissionInfo,
  AssignmentSubmissionRow,
  AttendanceDetail,
  AttendanceSummary,
  CalendarEvent,
  CourseMaterial,
  CourseMaterialPayload,
  GradesByTerm,
  InboxNotification,
  Khs,
  KrsHistoryEntry,
  KrsOfferings,
  KrsOverview,
  KrsSubmissionReview,
  PortalAnnouncement,
  PortalClassSection,
  PortalTerm,
  ScheduleOverview,
  StudentAssignment,
  StudentAssignmentList,
  StudentCourse,
  StudentCourseDetail,
  StudentDashboard,
  StudentDocument,
  StudentProfile,
  StudentRequest,
  StudentRequestOptions,
  StudentRequestPayload,
  TeachingClass,
  Transcript,
  UpdateProfilePayload,
  AssignmentBase,
} from '@/types/studentPortal'

/**
 * Layanan mandiri Portal Mahasiswa (`/student/*`) — seluruh endpoint
 * meresolusi mahasiswa dari akun yang login di backend, jadi tidak ada
 * student_id yang dikirim dari sini.
 */

async function get<T>(url: string, params?: Record<string, unknown>): Promise<T> {
  const response = await apiClient.get<ApiSuccessResponse<T>>(url, { params })
  return response.data.data
}

async function getPaginated<T>(url: string, params?: Record<string, unknown>): Promise<PaginatedResult<T> & { meta: ApiPaginationMeta & { unread_count?: number } }> {
  const response = await apiClient.get<ApiSuccessResponse<T[]>>(url, { params })
  return { data: response.data.data, meta: response.data.meta as ApiPaginationMeta & { unread_count?: number } }
}

async function send<T>(method: 'post' | 'put' | 'patch' | 'delete', url: string, body?: unknown): Promise<{ data: T; message: string | null }> {
  const response = await apiClient.request<ApiSuccessResponse<T>>({ method, url, data: body })
  return { data: response.data.data, message: response.data.message }
}

/**
 * Mengunduh PDF/berkas sebagai blob lalu memicu unduhan browser. Pesan
 * error backend tetap utuh — interceptor apiClient membaca JSON di dalam
 * Blob respons yang gagal (lihat services/api.ts).
 */
export async function downloadBlob(url: string, filename: string): Promise<void> {
  const response = await apiClient.get(url, { responseType: 'blob' })
  const objectUrl = URL.createObjectURL(response.data as Blob)
  const anchor = document.createElement('a')
  anchor.href = objectUrl
  anchor.download = filename
  document.body.appendChild(anchor)
  anchor.click()
  anchor.remove()
  URL.revokeObjectURL(objectUrl)
}

/** Menampilkan berkas (mis. foto profil) sebagai object URL — pemanggil wajib revoke. */
export async function fetchObjectUrl(url: string): Promise<string> {
  const response = await apiClient.get(url, { responseType: 'blob' })
  return URL.createObjectURL(response.data as Blob)
}

export const studentPortalService = {
  dashboard: () => get<StudentDashboard>('/student/dashboard'),

  profile: () => get<StudentProfile>('/student/profile'),
  updateProfile: (payload: UpdateProfilePayload) => send<StudentProfile>('patch', '/student/profile', payload),
  academicSummary: () => get<AcademicSummary>('/student/academic-summary'),
  academicHistory: () => get<AcademicHistoryEvent[]>('/student/academic-history'),

  krs: () => get<KrsOverview>('/student/krs'),
  krsOfferings: () => get<KrsOfferings>('/student/krs/offerings'),
  krsHistory: () => get<KrsHistoryEntry[]>('/student/krs/history'),
  addKrsItem: (classSectionId: string) => send<KrsOverview>('post', '/student/krs/items', { class_section_id: classSectionId }),
  removeKrsItem: (krsItemId: string) => send<KrsOverview>('delete', `/student/krs/items/${krsItemId}`),
  submitKrs: () => send<KrsOverview>('post', '/student/krs/submit'),
  cancelKrs: () => send<KrsOverview>('post', '/student/krs/cancel'),

  schedule: () => get<ScheduleOverview>('/student/schedule'),
  attendance: (academicTermId?: string) =>
    get<AttendanceSummary>('/student/attendance', academicTermId ? { academic_term_id: academicTermId } : undefined),
  attendanceDetail: (krsItemId: string) => get<AttendanceDetail>(`/student/attendance/${krsItemId}`),

  grades: () => get<GradesByTerm>('/student/grades'),
  khs: (academicTermId?: string) => get<Khs>('/student/khs', academicTermId ? { academic_term_id: academicTermId } : undefined),
  transcript: () => get<Transcript>('/student/transcript'),
  calendar: (from: string, to: string) => get<CalendarEvent[]>('/student/calendar', { from, to }),

  courses: () => get<{ term: PortalTerm | null; courses: StudentCourse[] }>('/student/courses'),
  materials: () =>
    get<{ term: PortalTerm | null; courses: (PortalClassSection & { materials: CourseMaterial[] })[] }>('/student/materials'),
  course: (classSectionId: string) => get<StudentCourseDetail>(`/student/courses/${classSectionId}`),
  assignments: (status?: string) => get<StudentAssignmentList>('/student/assignments', status ? { status } : undefined),
  assignment: (assignmentId: string) => get<StudentAssignment>(`/student/assignments/${assignmentId}`),
  submitAssignment: (assignmentId: string, payload: { file_upload_id?: string | null; notes?: string | null }) =>
    send<StudentAssignment>('post', `/student/assignments/${assignmentId}/submission`, payload),

  requests: (page = 1) => getPaginated<StudentRequest>('/student/requests', { page }),
  requestOptions: () => get<StudentRequestOptions>('/student/requests/options'),
  request: (id: string) => get<StudentRequest>(`/student/requests/${id}`),
  createRequest: (payload: StudentRequestPayload) => send<StudentRequest>('post', '/student/requests', payload),
  updateRequest: (id: string, payload: StudentRequestPayload) => send<StudentRequest>('put', `/student/requests/${id}`, payload),
  submitRequest: (id: string) => send<StudentRequest>('post', `/student/requests/${id}/submit`),
  cancelRequest: (id: string) => send<StudentRequest>('post', `/student/requests/${id}/cancel`),

  announcements: (params: { filter?: AnnouncementFilter; search?: string; page?: number; per_page?: number }) =>
    getPaginated<PortalAnnouncement>('/student/announcements', params),
  announcement: (id: string) => get<PortalAnnouncement>(`/student/announcements/${id}`),
  markAnnouncementRead: (id: string) =>
    send<{ id: string; is_read: boolean; unread_count: number }>('post', `/student/announcements/${id}/read`),

  documents: () => get<StudentDocument[]>('/student/documents'),
  downloadDocument: (document: StudentDocument) => downloadBlob(document.url, document.filename),
}

/** Pusat notifikasi — milik user login (semua role). */
export const notificationInboxService = {
  list: (params: { unread?: boolean; page?: number; per_page?: number } = {}) =>
    getPaginated<InboxNotification>('/notifications', { ...params, unread: params.unread ? 1 : undefined }),
  unreadCount: () => get<{ unread_count: number }>('/notifications/unread-count'),
  markRead: (id: string) => send<InboxNotification & { unread_count: number }>('patch', `/notifications/${id}/read`),
  markAllRead: () => send<{ unread_count: number }>('post', '/notifications/read-all'),
}

/** Persetujuan KRS — dosen wali (mahasiswa perwaliannya) & Bagian Akademik. */
export const krsApprovalService = {
  list: (params: { status?: string; search?: string; page?: number }) => getPaginated<KrsSubmissionReview>('/krs-submissions', params),
  show: (id: string) => get<KrsSubmissionReview>(`/krs-submissions/${id}`),
  approve: (id: string, note?: string) => send<KrsSubmissionReview>('post', `/krs-submissions/${id}/approve`, { note: note || null }),
  reject: (id: string, note: string) => send<KrsSubmissionReview>('post', `/krs-submissions/${id}/reject`, { note }),
}

/** Perkuliahan — dosen pengampu (kelasnya sendiri) & Bagian Akademik. */
export const teachingService = {
  classes: (params: { search?: string; academic_term_id?: string; page?: number } = {}) =>
    getPaginated<TeachingClass>('/teaching/classes', params),
  materials: (classSectionId: string) => get<CourseMaterial[]>(`/class-sections/${classSectionId}/materials`),
  createMaterial: (classSectionId: string, payload: CourseMaterialPayload) =>
    send<CourseMaterial>('post', `/class-sections/${classSectionId}/materials`, payload),
  updateMaterial: (materialId: string, payload: CourseMaterialPayload) => send<CourseMaterial>('put', `/course-materials/${materialId}`, payload),
  deleteMaterial: (materialId: string) => send<null>('delete', `/course-materials/${materialId}`),
  assignments: (classSectionId: string) => get<AssignmentBase[]>(`/class-sections/${classSectionId}/assignments`),
  createAssignment: (classSectionId: string, payload: AssignmentPayload) =>
    send<AssignmentBase>('post', `/class-sections/${classSectionId}/assignments`, payload),
  updateAssignment: (assignmentId: string, payload: AssignmentPayload) => send<AssignmentBase>('put', `/assignments/${assignmentId}`, payload),
  deleteAssignment: (assignmentId: string) => send<null>('delete', `/assignments/${assignmentId}`),
  submissions: (assignmentId: string) =>
    get<{ assignment: AssignmentBase; submissions: AssignmentSubmissionRow[] }>(`/assignments/${assignmentId}/submissions`),
  grade: (submissionId: string, payload: { score: number; feedback?: string | null }) =>
    send<AssignmentSubmissionInfo>('put', `/assignment-submissions/${submissionId}/grade`, payload),
}

/** Pengajuan mahasiswa — ditinjau Bagian Akademik (keputusan lewat ApprovalWorkflow). */
export const studentRequestReviewService = {
  list: (params: { search?: string; page?: number; filter?: Record<string, string> }) =>
    getPaginated<StudentRequest>('/student-requests', params),
  show: (id: string) => get<StudentRequest>(`/student-requests/${id}`),
  approve: (id: string, note?: string) => send<StudentRequest>('post', `/student-requests/${id}/approve`, { note: note || null }),
  reject: (id: string, note: string) => send<StudentRequest>('post', `/student-requests/${id}/reject`, { note }),
}
