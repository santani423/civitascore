import type { LucideIcon } from 'lucide-react'

export type TrendDirection = 'up' | 'down' | 'flat'

export interface SummaryCardData {
  id: string
  label: string
  value: string
  changePercent: number
  trend: TrendDirection
  caption: string
  icon: LucideIcon
}

export interface YearlyStudentCount {
  year: string
  students: number
}

export interface FacultyDistribution {
  faculty: string
  students: number
}

export interface PaymentStatusSlice {
  status: string
  value: number
}

export interface AttendanceTrendPoint {
  month: string
  present: number
  absent: number
}

export interface StudentStatusSlice {
  status: string
  value: number
}

export type ActivityType =
  | 'student_registered'
  | 'krs_submitted'
  | 'grade_published'
  | 'payment_received'
  | 'letter_approved'
  | 'complaint_resolved'

export interface ActivityItem {
  id: string
  type: ActivityType
  title: string
  description: string
  actor: string
  timestamp: string
}

export type AgendaCategory = 'krs' | 'exam' | 'payment' | 'grading' | 'graduation'

export interface AgendaItem {
  id: string
  title: string
  category: AgendaCategory
  startDate: string
  endDate: string
}

export type ApprovalCategory = 'krs' | 'leave' | 'grade_change' | 'letter' | 'scholarship'

export interface PendingApprovalItem {
  id: string
  category: ApprovalCategory
  title: string
  requester: string
  submittedAt: string
  waitingSteps: number
}

export type NotificationType = 'info' | 'success' | 'warning' | 'danger'

export interface NotificationItem {
  id: string
  type: NotificationType
  title: string
  description: string
  isRead: boolean
  timestamp: string
}
