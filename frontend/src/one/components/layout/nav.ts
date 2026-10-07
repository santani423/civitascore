import {
  Award,
  BookOpen,
  Briefcase,
  Building2,
  CalendarDays,
  ChartColumn,
  ClipboardList,
  Earth,
  FileCheck,
  FlaskConical,
  GraduationCap,
  Landmark,
  LayoutDashboard,
  Library,
  LifeBuoy,
  Megaphone,
  School,
  UserCheck,
  Users,
  Wallet,
  type LucideIcon,
} from 'lucide-react'
import type { TKey } from '../../i18n'

export interface NavItem {
  key: string
  label: TKey
  to: string
  icon: LucideIcon
  end?: boolean
}

export const navGroups: Array<{ key: string; label: TKey; items: NavItem[] }> = [
  {
    key: 'overview',
    label: 'nav.groups.overview',
    items: [{ key: 'dashboard', label: 'nav.dashboard', to: '/one', icon: LayoutDashboard, end: true }],
  },
  {
    key: 'learning',
    label: 'nav.groups.learning',
    items: [
      { key: 'academic', label: 'nav.academic', to: '/one/academic', icon: GraduationCap },
      { key: 'schedule', label: 'nav.schedule', to: '/one/schedule', icon: CalendarDays },
      { key: 'courses', label: 'nav.courses', to: '/one/courses', icon: BookOpen },
      { key: 'assignments', label: 'nav.assignments', to: '/one/assignments', icon: ClipboardList },
      { key: 'exams', label: 'nav.exams', to: '/one/exams', icon: FileCheck },
      { key: 'grades', label: 'nav.grades', to: '/one/grades', icon: ChartColumn },
      { key: 'attendance', label: 'nav.attendance', to: '/one/attendance', icon: UserCheck },
    ],
  },
  {
    key: 'campus',
    label: 'nav.groups.campus',
    items: [
      { key: 'finance', label: 'nav.finance', to: '/one/finance', icon: Wallet },
      { key: 'campus', label: 'nav.campus', to: '/one/campus', icon: Building2, end: true },
      { key: 'organizations', label: 'nav.organizations', to: '/one/campus/organizations', icon: Users },
      { key: 'career', label: 'nav.career', to: '/one/career', icon: Briefcase },
      { key: 'library', label: 'nav.library', to: '/one/library', icon: Library },
      { key: 'announcements', label: 'nav.announcements', to: '/one/news', icon: Megaphone },
      { key: 'services', label: 'nav.services', to: '/one/services', icon: LifeBuoy },
    ],
  },
  {
    key: 'university',
    label: 'nav.groups.university',
    items: [
      { key: 'admission', label: 'nav.admission', to: '/one/admission', icon: School },
      { key: 'faculties', label: 'nav.faculties', to: '/one/faculties', icon: Landmark },
      { key: 'research', label: 'nav.research', to: '/one/research', icon: FlaskConical },
      { key: 'international', label: 'nav.international', to: '/one/international', icon: Earth },
      { key: 'scholarships', label: 'nav.scholarships', to: '/one/scholarships', icon: Award },
    ],
  },
]

export const allNavItems = navGroups.flatMap((g) => g.items)
