import type { LucideIcon } from 'lucide-react'
import {
  BarChart3,
  BookOpenCheck,
  Briefcase,
  Building2,
  CalendarDays,
  ClipboardCheck,
  GraduationCap,
  Landmark,
  LayoutDashboard,
  UserPlus,
  Users,
  Wallet,
} from 'lucide-react'
import type { Messages } from '../i18n/messages/id'

export type CapabilityStatus = 'available' | 'phased' | 'planned'
export type CapabilityKey = keyof Messages['capabilities']

/**
 * Honest status of every capability the landing page mentions — verified
 * against the codebase (app/Modules/*, frontend routes) and
 * docs/FRONTEND-IMPLEMENTASI.md. Update this table, not the copy, when a
 * module ships:
 * - available: working in the platform (read + write where applicable)
 * - phased:    data can be viewed, write flows still being built
 * - planned:   design document only, no implementation yet
 */
export const CAPABILITY_STATUS: Record<CapabilityKey, CapabilityStatus> = {
  curriculum: 'available',
  classes: 'available',
  krs: 'available',
  attendance: 'available',
  grading: 'available',
  exams: 'available',
  calendar: 'available',
  learning: 'available',
  studentPortal: 'available',
  documents: 'available',
  requests: 'available',
  scholarship: 'phased',
  thesis: 'phased',
  internship: 'phased',
  admissions: 'planned',
  lecturers: 'available',
  staff: 'available',
  hr: 'available',
  development: 'available',
  billing: 'phased',
  approvals: 'available',
  files: 'available',
  announcements: 'available',
  library: 'phased',
  alumni: 'phased',
  institution: 'available',
  dashboard: 'available',
  reports: 'available',
  examRecap: 'available',
  audit: 'available',
  rbac: 'available',
  studentRecords: 'available',
}

export type CategoryKey = keyof Messages['platform']['categories']

export interface EcosystemCategory {
  key: CategoryKey
  icon: LucideIcon
  capabilities: CapabilityKey[]
}

/** Platform overview groups (section D). */
export const ECOSYSTEM: EcosystemCategory[] = [
  {
    key: 'academic',
    icon: GraduationCap,
    capabilities: ['curriculum', 'classes', 'krs', 'attendance', 'grading', 'exams', 'calendar', 'learning'],
  },
  {
    key: 'students',
    icon: Users,
    capabilities: ['studentPortal', 'documents', 'requests', 'scholarship', 'thesis', 'internship', 'admissions'],
  },
  { key: 'people', icon: Briefcase, capabilities: ['lecturers', 'staff', 'hr', 'development'] },
  { key: 'finance', icon: Wallet, capabilities: ['billing', 'approvals', 'files'] },
  { key: 'operations', icon: Building2, capabilities: ['announcements', 'library', 'alumni', 'institution'] },
  { key: 'analytics', icon: BarChart3, capabilities: ['dashboard', 'reports', 'examRecap', 'audit'] },
]

export type ModuleKey = keyof Messages['modules']['items']

export interface ModuleDef {
  key: ModuleKey
  icon: LucideIcon
  status: CapabilityStatus
  capabilities: CapabilityKey[]
  /** Platform-overview category this module belongs to (pre-selects the demo form interest). */
  category: CategoryKey
}

/** Core modules (section E), in the order the bento grid lays them out. */
export const MODULES: ModuleDef[] = [
  { key: 'studentData', icon: Users, status: 'available', capabilities: ['studentRecords', 'documents', 'rbac'], category: 'students' },
  { key: 'lectures', icon: CalendarDays, status: 'available', capabilities: ['classes', 'krs', 'attendance', 'learning', 'calendar'], category: 'academic' },
  { key: 'curriculum', icon: BookOpenCheck, status: 'available', capabilities: ['curriculum', 'classes'], category: 'academic' },
  { key: 'assessment', icon: ClipboardCheck, status: 'available', capabilities: ['grading', 'exams', 'examRecap', 'documents'], category: 'academic' },
  { key: 'people', icon: Briefcase, status: 'available', capabilities: ['lecturers', 'staff', 'hr', 'development'], category: 'people' },
  { key: 'services', icon: GraduationCap, status: 'available', capabilities: ['studentPortal', 'requests', 'announcements', 'scholarship', 'thesis', 'internship'], category: 'students' },
  { key: 'finance', icon: Wallet, status: 'phased', capabilities: ['billing', 'approvals'], category: 'finance' },
  { key: 'admissions', icon: UserPlus, status: 'planned', capabilities: ['admissions'], category: 'students' },
  { key: 'analytics', icon: LayoutDashboard, status: 'available', capabilities: ['dashboard', 'reports', 'examRecap'], category: 'analytics' },
  { key: 'governance', icon: Landmark, status: 'available', capabilities: ['approvals', 'institution', 'rbac', 'audit'], category: 'operations' },
]
