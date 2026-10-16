/**
 * Illustrative data for every dashboard mockup on the landing page.
 * Not real institutional figures — every mockup is labelled "Data ilustrasi".
 * Numbers are kept internally consistent (e.g. faculty totals sum to the
 * active-student KPI) so the product story holds together across sections.
 * Labels live in the dictionaries; this file holds language-neutral values.
 */
import type { Messages } from '../i18n/messages/id'

type Demo = Messages['demo']
export type CourseKey = keyof Demo['courses']
export type ProgramKey = keyof Demo['programs']
export type FacultyKey = keyof Demo['faculties']

export const KPI = {
  activeStudents: 12486,
  activeStudentsDelta: 2.4,
  lecturers: 642,
  lecturersDelta: 1.1,
  runningClasses: 1284,
  runningClassesDelta: 3.0,
  attendance: 92.6,
  attendanceDelta: 0.8,
  newStudents: 2318,
  onLeave: 214,
  averageGpa: 3.38,
  gradeTimeliness: 97.1,
  goodStanding: 71.4,
  lettersThisMonth: 186,
  processingDays: 1.8,
  reportsReady: 4,
}

/** Weekly academic activity (records per lecture week; week 9 is the midterm break). */
export const WEEKLY_ACTIVITY = {
  attendance: [38200, 41500, 40800, 43100, 44600, 42900, 45800, 47200, 30400, 44100, 46300, 48000],
  submissions: [6100, 8400, 9200, 10500, 9800, 11600, 12400, 10100, 4200, 11900, 13200, 14100],
}

export const FACULTY_STUDENTS: Array<{ key: FacultyKey; value: number }> = [
  { key: 'business', value: 3420 },
  { key: 'engineering', value: 2380 },
  { key: 'health', value: 1920 },
  { key: 'psychology', value: 1640 },
  { key: 'education', value: 1616 },
  { key: 'law', value: 1510 },
]

export const REGISTRATION = { done: 82, progress: 11, pending: 7 }

/** Average semester GPA and cumulative GPA over the last six terms. */
export const GPA_TREND = {
  ips: [3.21, 3.28, 3.25, 3.34, 3.31, 3.39],
  ipk: [3.24, 3.26, 3.27, 3.3, 3.32, 3.35],
}

/** Active students over the last five terms (ends at KPI.activeStudents). */
export const ENROLLMENT_TREND = [11820, 12010, 12150, 12290, 12486]

export type LectureStatus = 'done' | 'live' | 'upcoming'

export const TODAY_SCHEDULE: Array<{
  start: string
  end: string
  course: CourseKey
  section: string
  room: string
  lecturer: string
  status: LectureStatus
}> = [
  { start: '07:30', end: '09:10', course: 'algorithms', section: 'IF-A', room: 'K2-301', lecturer: 'Yohanes Hartono, M.T.', status: 'done' },
  { start: '09:20', end: '11:00', course: 'databases', section: 'IF-B', room: 'LAB-K2', lecturer: 'Dr. Maria Kristanti', status: 'live' },
  { start: '10:00', end: '11:40', course: 'statistics', section: 'MN-A', room: 'B1-105', lecturer: 'Dr. Theresia Lubis', status: 'live' },
  { start: '13:00', end: '14:40', course: 'development', section: 'PS-C', room: 'D-401', lecturer: 'Agnes Wulandari, M.Psi.', status: 'upcoming' },
  { start: '15:00', end: '16:40', course: 'civilLaw', section: 'HK-A', room: 'C3-210', lecturer: 'Antonius Siregar, M.H.', status: 'upcoming' },
]

export type CalendarEventKey = keyof Demo['academic']['events']
export type Tone = 'brand' | 'gold' | 'info'

/** Academic calendar events, as day-of-month in the month currently shown. */
export const CALENDAR_EVENTS: Array<{ key: CalendarEventKey; from: number; to: number; tone: Tone }> = [
  { key: 'krs', from: 3, to: 3, tone: 'brand' },
  { key: 'meeting', from: 8, to: 8, tone: 'info' },
  { key: 'midterm', from: 19, to: 23, tone: 'gold' },
  { key: 'seminar', from: 26, to: 26, tone: 'info' },
  { key: 'grades', from: 28, to: 28, tone: 'brand' },
]

export type ApprovalType = keyof Demo['admin']['types']
export type ApprovalStep = keyof Demo['admin']['steps']

export const APPROVALS: Array<{
  type: ApprovalType
  name: string
  nim: string
  step: ApprovalStep
  position: number
  total: number
  days: number
}> = [
  { type: 'leave', name: 'Bima Pratama', nim: '202301045', step: 'head', position: 2, total: 3, days: 2 },
  { type: 'krsChange', name: 'Nadia Halim', nim: '202402118', step: 'advisor', position: 1, total: 2, days: 0 },
  { type: 'recommendation', name: 'Kevin Santoso', nim: '202201077', step: 'bureau', position: 3, total: 3, days: 1 },
  { type: 'scholarship', name: 'Clara Wijaya', nim: '202401014', step: 'finance', position: 2, total: 3, days: 3 },
]

export type AnnouncementKey = keyof Demo['admin']['announcements']
export type Audience = keyof Demo['admin']['audience']

export const ANNOUNCEMENTS: Array<{ key: AnnouncementKey; audience: Audience; daysAgo: number }> = [
  { key: 'midterm', audience: 'students', daysAgo: 0 },
  { key: 'registration', audience: 'all', daysAgo: 2 },
  { key: 'workshop', audience: 'lecturers', daysAgo: 4 },
]

export type ReportKey = keyof Demo['analytics']['reports']

export const REPORTS: Array<{ key: ReportKey; format: 'PDF' | 'XLSX'; daysAgo: number }> = [
  { key: 'academic', format: 'PDF', daysAgo: 1 },
  { key: 'attendance', format: 'XLSX', daysAgo: 0 },
  { key: 'exams', format: 'XLSX', daysAgo: 3 },
  { key: 'lecturers', format: 'PDF', daysAgo: 6 },
]

export const STUDENT_ROWS: Array<{ name: string; nim: string; program: ProgramKey; onLeave: boolean }> = [
  { name: 'Clara Wijaya', nim: '202401014', program: 'informatics', onLeave: false },
  { name: 'Bima Pratama', nim: '202401027', program: 'management', onLeave: false },
  { name: 'Nadia Halim', nim: '202401033', program: 'psychology', onLeave: true },
  { name: 'Kevin Santoso', nim: '202401041', program: 'law', onLeave: false },
]

/** Mon–Fri mini timetable for the "Perkuliahan & Jadwal" module card. */
export const WEEK_BLOCKS: Array<Array<{ course: CourseKey; start: string; tone: Tone }>> = [
  [{ course: 'algorithms', start: '07:30', tone: 'brand' }, { course: 'ethics', start: '13:00', tone: 'info' }],
  [{ course: 'databases', start: '09:20', tone: 'gold' }],
  [{ course: 'statistics', start: '10:00', tone: 'info' }, { course: 'algorithms', start: '15:00', tone: 'brand' }],
  [{ course: 'databases', start: '07:30', tone: 'gold' }],
  [{ course: 'accounting', start: '09:20', tone: 'info' }, { course: 'ethics', start: '13:00', tone: 'brand' }],
]

export const CURRICULUM_CREDITS = [20, 22, 21, 20]

/** Letter-grade distribution (percent of class) for the assessment module card. */
export const GRADE_DISTRIBUTION: Array<{ grade: string; value: number }> = [
  { grade: 'A', value: 18 },
  { grade: 'A-', value: 22 },
  { grade: 'B+', value: 26 },
  { grade: 'B', value: 17 },
  { grade: 'B-', value: 9 },
  { grade: 'C+', value: 5 },
  { grade: 'C', value: 3 },
]
export const CLASS_AVERAGE_GPA = 3.41

export const INVOICES: Array<{ key: 'invoiceTuition' | 'invoicePractical'; amount: number; paid: boolean }> = [
  { key: 'invoiceTuition', amount: 8750000, paid: true },
  { key: 'invoicePractical', amount: 1250000, paid: false },
]

export const GRADING_PROGRESS = { done: 18, total: 32, meeting: 7 }

export const STAFF_QUEUE: Array<{ key: keyof Messages['personas']['mock']['queue']; count: number }> = [
  { key: 'krs', count: 42 },
  { key: 'letters', count: 12 },
  { key: 'lecturerData', count: 5 },
]

/** "Clara" (Informatics) — the student shown in the phone mockups. */
export const STUDENT_DAY: Array<{ course: CourseKey; start: string; end: string; room: string }> = [
  { course: 'databases', start: '09:20', end: '11:00', room: 'LAB-K2' },
  { course: 'ethics', start: '13:00', end: '14:40', room: 'D-401' },
]
