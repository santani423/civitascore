import type { LucideIcon } from 'lucide-react'
import {
  BarChart3,
  BookOpenCheck,
  Briefcase,
  ClipboardCheck,
  FileCheck2,
  GraduationCap,
  ShieldCheck,
  UserPlus,
  Wallet,
} from 'lucide-react'
import { PMB } from '@/pmb/routes'
import type { Messages } from '../i18n/messages/id'

export type CapabilityStatus = 'available' | 'phased' | 'planned'
export type CapabilityKey = keyof Messages['capabilities']

/**
 * Honest status of every capability the landing page mentions — verified
 * against the codebase (app/Modules/*, frontend routes and pages). Update
 * this table, not the copy, when a feature ships:
 * - available: usable in the web app today (read + write where applicable)
 * - phased:    partly usable (e.g. read-only, or managed via API only), being completed
 * - planned:   design document only, no implementation yet
 */
export const CAPABILITY_STATUS: Record<CapabilityKey, CapabilityStatus> = {
  pmbSite: 'available',
  pmbApply: 'available',
  pmbVerify: 'available',
  pmbSelection: 'available',
  pmbResult: 'available',
  pmbReRegister: 'available',
  // Xendit adapter exists for PMB and KEU but is only active once credentials are configured.
  onlinePayment: 'phased',
  krs: 'available',
  // Advisor approval exists in the API; its page is not routed yet.
  krsApproval: 'phased',
  classes: 'available',
  schedule: 'available',
  attendance: 'available',
  grading: 'available',
  results: 'available',
  // Read-only in the app (seeded); no create/edit UI.
  curriculum: 'phased',
  // Students can view; event management is API-only.
  calendar: 'phased',
  learningStudent: 'available',
  // Lecturer endpoints exist, the lecturer page is not routed yet.
  learningLecturer: 'phased',
  studentRecords: 'available',
  exams: 'available',
  examAccess: 'available',
  examMonitoring: 'available',
  examRecap: 'available',
  billing: 'available',
  cashier: 'available',
  aid: 'available',
  clearance: 'available',
  lecturers: 'available',
  staff: 'available',
  lecturerProfile: 'available',
  // HR histories, contracts and trainings are API-only.
  hrHistory: 'phased',
  requests: 'available',
  approvals: 'available',
  announcementsFeed: 'available',
  // Announcement CRUD is API-only; the staff page is read-only.
  announcementsAuthoring: 'phased',
  notifications: 'available',
  dashboard: 'available',
  reports: 'available',
  rbac: 'available',
  audit: 'available',
  settings: 'available',
  security: 'available',
}

export type AudienceKey = Exclude<keyof Messages['services']['filters'], 'all'>
export type ServiceKey = keyof Messages['services']['items']

export interface ServiceDef {
  key: ServiceKey
  icon: LucideIcon
  status: CapabilityStatus
  audiences: AudienceKey[]
  capabilities: CapabilityKey[]
  /** `login` goes through the existing sign-in flow; `pmb` links to the public PMB website. */
  access: { kind: 'login' } | { kind: 'pmb'; to: string }
}

/** Section D cards, in display order. A card is `available` when its core flows work end to end. */
export const SERVICES: ServiceDef[] = [
  {
    key: 'admissions',
    icon: UserPlus,
    status: 'available',
    audiences: ['applicants', 'staff'],
    capabilities: ['pmbSite', 'pmbApply', 'pmbVerify', 'pmbSelection', 'pmbResult', 'pmbReRegister', 'onlinePayment'],
    access: { kind: 'pmb', to: PMB.home },
  },
  {
    key: 'studentPortal',
    icon: GraduationCap,
    status: 'available',
    audiences: ['students'],
    capabilities: ['krs', 'schedule', 'attendance', 'results', 'learningStudent', 'exams', 'billing', 'requests', 'announcementsFeed', 'calendar'],
    access: { kind: 'login' },
  },
  {
    key: 'academic',
    icon: BookOpenCheck,
    status: 'available',
    audiences: ['lecturers', 'staff', 'leaders'],
    capabilities: ['classes', 'krs', 'attendance', 'grading', 'studentRecords', 'krsApproval', 'learningLecturer', 'curriculum'],
    access: { kind: 'login' },
  },
  {
    key: 'exams',
    icon: ClipboardCheck,
    status: 'available',
    audiences: ['lecturers', 'students'],
    capabilities: ['exams', 'examAccess', 'examMonitoring', 'examRecap'],
    access: { kind: 'login' },
  },
  {
    key: 'finance',
    icon: Wallet,
    status: 'available',
    audiences: ['students', 'staff', 'leaders'],
    capabilities: ['billing', 'cashier', 'aid', 'clearance', 'onlinePayment'],
    access: { kind: 'login' },
  },
  {
    key: 'people',
    icon: Briefcase,
    status: 'available',
    audiences: ['staff', 'lecturers', 'leaders'],
    capabilities: ['lecturers', 'staff', 'lecturerProfile', 'hrHistory'],
    access: { kind: 'login' },
  },
  {
    key: 'services',
    icon: FileCheck2,
    status: 'available',
    audiences: ['students', 'lecturers', 'staff', 'leaders'],
    capabilities: ['requests', 'approvals', 'announcementsFeed', 'notifications', 'announcementsAuthoring'],
    access: { kind: 'login' },
  },
  {
    key: 'analytics',
    icon: BarChart3,
    status: 'available',
    audiences: ['leaders', 'staff'],
    capabilities: ['dashboard', 'reports'],
    access: { kind: 'login' },
  },
  {
    key: 'governance',
    icon: ShieldCheck,
    status: 'available',
    audiences: ['staff', 'leaders'],
    capabilities: ['rbac', 'audit', 'settings', 'security'],
    access: { kind: 'login' },
  },
]

/** Modules whose records can be viewed but whose management flows are still being built (read-only today). */
export const UPCOMING: Array<keyof Messages['services']['upcoming']> = ['thesis', 'internship', 'library', 'alumni', 'scholarshipPrograms']
