import { daysFromNow } from '../lib/dates'

/** 1 = Monday … 6 = Saturday */
export interface Slot {
  day: number
  start: string
  end: string
  room: string
}

export interface Course {
  id: string
  code: string
  name: string
  lecturerId: string
  credits: number
  semester: number
  status: 'active' | 'completed'
  slots: Slot[]
  description: string
  outcomes: string[]
  /** Current (in-progress) or final score, 0–100. */
  score: number
  grade: string
  attendance: { present: number; late: number; excused: number; absent: number; total: number }
}

const active = (c: Omit<Course, 'semester' | 'status'>): Course => ({ ...c, semester: 5, status: 'active' })

export const courses: Course[] = [
  active({
    id: 'is301',
    code: 'IS301',
    name: 'Database Systems',
    lecturerId: 'lec-1',
    credits: 3,
    slots: [
      { day: 3, start: '08:00', end: '09:40', room: 'GK-301' },
      { day: 6, start: '09:00', end: '10:40', room: 'Lab Data 2' },
    ],
    description:
      'Design and implementation of relational databases: data modeling, normalization, SQL, transactions, indexing and an introduction to NoSQL stores used in modern information systems.',
    outcomes: [
      'Model business requirements as entity-relationship diagrams',
      'Normalize schemas to 3NF/BCNF and justify trade-offs',
      'Write performant SQL including joins, window functions and views',
      'Explain transaction isolation and recovery mechanisms',
    ],
    score: 88,
    grade: 'A',
    attendance: { present: 6, late: 1, excused: 0, absent: 0, total: 7 },
  }),
  active({
    id: 'mb205',
    code: 'MB205',
    name: 'Business Communication',
    lecturerId: 'lec-2',
    credits: 2,
    slots: [{ day: 3, start: '10:00', end: '11:40', room: 'GK-205' }],
    description:
      'Effective written and spoken communication in professional settings: business writing, presentations, negotiation and cross-cultural communication.',
    outcomes: ['Write concise business correspondence', 'Deliver persuasive presentations', 'Communicate across cultures'],
    score: 84,
    grade: 'A-',
    attendance: { present: 5, late: 0, excused: 1, absent: 1, total: 7 },
  }),
  active({
    id: 'is305',
    code: 'IS305',
    name: 'Software Engineering',
    lecturerId: 'lec-3',
    credits: 3,
    slots: [{ day: 3, start: '13:00', end: '15:30', room: 'GK-402' }],
    description:
      'Principles and practices for building reliable software: requirements, architecture, agile delivery, testing, DevOps and teamwork on a semester-long project.',
    outcomes: ['Elicit and specify requirements', 'Apply design patterns and architecture styles', 'Plan sprints and deliver in teams', 'Automate testing and deployment'],
    score: 86,
    grade: 'A-',
    attendance: { present: 7, late: 0, excused: 0, absent: 0, total: 7 },
  }),
  active({
    id: 'is310',
    code: 'IS310',
    name: 'Human-Computer Interaction',
    lecturerId: 'lec-4',
    credits: 3,
    slots: [{ day: 1, start: '08:00', end: '10:30', room: 'Design Studio 1' }],
    description:
      'User-centered design methods: research, personas, prototyping, usability testing and accessibility for digital products.',
    outcomes: ['Plan and run user research', 'Prototype at multiple fidelities', 'Evaluate designs against WCAG'],
    score: 91,
    grade: 'A',
    attendance: { present: 6, late: 1, excused: 0, absent: 0, total: 7 },
  }),
  active({
    id: 'is312',
    code: 'IS312',
    name: 'Data Analytics & Visualization',
    lecturerId: 'lec-5',
    credits: 3,
    slots: [{ day: 2, start: '10:00', end: '12:30', room: 'Lab Data 1' }],
    description:
      'From raw data to decisions: data wrangling with Python, exploratory analysis, statistical inference and honest visual communication.',
    outcomes: ['Clean and transform datasets with pandas', 'Choose appropriate chart forms', 'Build interactive dashboards'],
    score: 78,
    grade: 'B+',
    attendance: { present: 5, late: 0, excused: 0, absent: 2, total: 7 },
  }),
  active({
    id: 'is315',
    code: 'IS315',
    name: 'Enterprise Architecture',
    lecturerId: 'lec-6',
    credits: 3,
    slots: [{ day: 4, start: '08:00', end: '10:30', room: 'GK-305' }],
    description: 'Aligning business and IT with architecture frameworks (TOGAF, ArchiMate), capability mapping and IT governance.',
    outcomes: ['Model architectures with ArchiMate', 'Apply the TOGAF ADM', 'Assess IT governance maturity'],
    score: 90,
    grade: 'A',
    attendance: { present: 7, late: 0, excused: 0, absent: 0, total: 7 },
  }),
  active({
    id: 'is320',
    code: 'IS320',
    name: 'Information Security Management',
    lecturerId: 'lec-7',
    credits: 3,
    slots: [{ day: 4, start: '13:00', end: '15:30', room: 'GK-402' }],
    description: 'Security governance, risk assessment, ISO 27001 controls, incident response and privacy regulation.',
    outcomes: ['Perform qualitative risk assessments', 'Map controls to ISO 27001', 'Draft an incident response plan'],
    score: 85,
    grade: 'A-',
    attendance: { present: 6, late: 0, excused: 1, absent: 0, total: 7 },
  }),
  active({
    id: 'un201',
    code: 'UN201',
    name: 'Ethics & Character Building',
    lecturerId: 'lec-8',
    credits: 2,
    slots: [{ day: 5, start: '10:00', end: '11:40', room: 'Auditorium B' }],
    description: 'Ethical reasoning, human dignity and social responsibility for professionals in a digital society.',
    outcomes: ['Analyze ethical dilemmas in technology', 'Articulate personal and professional values'],
    score: 89,
    grade: 'A',
    attendance: { present: 6, late: 0, excused: 0, absent: 1, total: 7 },
  }),
]

const done = (semester: number, code: string, name: string, credits: number, score: number, grade: string, lecturerId: string): Course => ({
  id: code.toLowerCase(),
  code,
  name,
  lecturerId,
  credits,
  semester,
  status: 'completed',
  slots: [],
  description: '',
  outcomes: [],
  score,
  grade,
  attendance: { present: 14, late: 0, excused: 0, absent: 0, total: 14 },
})

export const pastCourses: Course[] = [
  done(1, 'IS101', 'Introduction to Information Systems', 3, 86, 'A-', 'lec-6'),
  done(1, 'IS102', 'Programming Fundamentals', 4, 82, 'A-', 'lec-3'),
  done(1, 'MA101', 'Discrete Mathematics', 3, 76, 'B+', 'lec-9'),
  done(1, 'UN101', 'Pancasila & Civic Education', 2, 88, 'A', 'lec-8'),
  done(2, 'IS201', 'Object-Oriented Programming', 4, 85, 'A-', 'lec-3'),
  done(2, 'IS202', 'Business Process Management', 3, 88, 'A', 'lec-6'),
  done(2, 'MA201', 'Statistics for Business', 3, 81, 'A-', 'lec-5'),
  done(3, 'IS203', 'Systems Analysis & Design', 3, 87, 'A', 'lec-6'),
  done(3, 'IS204', 'Web Programming', 3, 90, 'A', 'lec-4'),
  done(3, 'IS205', 'Computer Networks', 3, 78, 'B+', 'lec-7'),
  done(4, 'IS206', 'IT Project Management', 3, 92, 'A', 'lec-6'),
  done(4, 'IS207', 'Mobile Application Development', 3, 91, 'A', 'lec-4'),
  done(4, 'IS208', 'Operating Systems', 3, 85, 'A-', 'lec-7'),
]

export const allCourses = [...courses, ...pastCourses]
export const courseById = (id: string) => allCourses.find((c) => c.id === id)

/** Semester GPA history (IPS), current semester not yet final. */
export const gpaHistory = [
  { semester: 1, gpa: 3.55, credits: 20 },
  { semester: 2, gpa: 3.68, credits: 21 },
  { semester: 3, gpa: 3.71, credits: 21 },
  { semester: 4, gpa: 3.84, credits: 30 },
]

export const gradeComponents = [
  { key: 'assignments', label: 'Assignments', weight: 30 },
  { key: 'quiz', label: 'Quizzes', weight: 10 },
  { key: 'midterm', label: 'Midterm exam', weight: 25 },
  { key: 'final', label: 'Final exam', weight: 35 },
]

export const weeklyTopics: Record<string, string[]> = {
  is301: [
    'Course introduction & the role of databases',
    'Entity-relationship modeling',
    'Relational model & keys',
    'Normalization: 1NF to BCNF',
    'SQL fundamentals: DDL & DML',
    'Joins, subqueries & views',
    'Window functions & analytics SQL',
    'Midterm exam',
    'Indexing & query optimization',
    'Transactions & concurrency',
  ],
}

export const materials = [
  { id: 'm1', courseId: 'is301', title: 'Week 1 — Course overview & syllabus', type: 'PDF', size: '1.2 MB', week: 1 },
  { id: 'm2', courseId: 'is301', title: 'Week 2 — ER modeling slides', type: 'PPTX', size: '4.8 MB', week: 2 },
  { id: 'm3', courseId: 'is301', title: 'Week 3 — Relational model reading', type: 'PDF', size: '2.1 MB', week: 3 },
  { id: 'm4', courseId: 'is301', title: 'Week 4 — Normalization worked examples', type: 'PDF', size: '980 KB', week: 4 },
  { id: 'm5', courseId: 'is301', title: 'Week 5 — SQL lab starter database', type: 'ZIP', size: '12.4 MB', week: 5 },
  { id: 'm6', courseId: 'is301', title: 'Week 6 — Joins & subqueries recording', type: 'Video', size: '48 min', week: 6 },
  { id: 'm7', courseId: 'is301', title: 'Week 7 — Window functions cheat sheet', type: 'PDF', size: '420 KB', week: 7 },
]

export const discussions = [
  {
    id: 'd1',
    courseId: 'is301',
    author: 'Nadia Putri',
    at: daysFromNow(-1, 19, 12),
    body: 'For the ERD case study, should weak entities be shown with double borders or is the crow’s foot notation enough?',
    replies: 4,
  },
  {
    id: 'd2',
    courseId: 'is301',
    author: 'Dr. Maria Wijaya, M.Kom.',
    at: daysFromNow(-3, 10, 5),
    body: 'Reminder: Saturday lab moves to Lab Data 2 for the rest of the semester. Bring your laptop with PostgreSQL 16 installed.',
    replies: 2,
  },
]

export type AssignmentStatus = 'upcoming' | 'inProgress' | 'submitted' | 'graded' | 'overdue'

export interface Assignment {
  id: string
  courseId: string
  title: string
  due: string
  status: AssignmentStatus
  progress: number
  score?: number
  feedback?: string
}

export const assignments: Assignment[] = [
  { id: 'a1', courseId: 'is305', title: 'Software Requirements Specification (SRS)', due: daysFromNow(2, 23, 59), status: 'inProgress', progress: 60 },
  { id: 'a9', courseId: 'is301', title: 'SQL Query Lab 3', due: daysFromNow(0, 23, 59), status: 'inProgress', progress: 50 },
  { id: 'a2', courseId: 'is301', title: 'ERD & Normalization Case Study', due: daysFromNow(4, 23, 59), status: 'upcoming', progress: 0 },
  { id: 'a3', courseId: 'is310', title: 'Usability Testing Report', due: daysFromNow(6, 17, 0), status: 'inProgress', progress: 35 },
  { id: 'a8', courseId: 'un201', title: 'Reflective Essay: Technology & Human Dignity', due: daysFromNow(9, 23, 59), status: 'upcoming', progress: 0 },
  { id: 'a4', courseId: 'is312', title: 'Dashboard Prototype with Python', due: daysFromNow(-1, 23, 59), status: 'overdue', progress: 80 },
  { id: 'a5', courseId: 'mb205', title: 'Persuasive Presentation Script', due: daysFromNow(-2, 23, 59), status: 'submitted', progress: 100 },
  {
    id: 'a6',
    courseId: 'is320',
    title: 'Risk Assessment Matrix',
    due: daysFromNow(-8, 23, 59),
    status: 'graded',
    progress: 100,
    score: 88,
    feedback: 'Strong likelihood/impact reasoning. Add residual risk after controls for full marks.',
  },
  {
    id: 'a7',
    courseId: 'is315',
    title: 'TOGAF ADM Phase B Brief',
    due: daysFromNow(-12, 23, 59),
    status: 'graded',
    progress: 100,
    score: 92,
    feedback: 'Excellent capability map. Clear baseline vs. target comparison.',
  },
]

export type ExamType = 'midterm' | 'final' | 'quiz' | 'practical'

export interface Exam {
  id: string
  courseId: string
  type: ExamType
  start: string
  duration: number
  room: string
  seat: string
  score?: number
}

export const exams: Exam[] = [
  { id: 'e1', courseId: 'is310', type: 'quiz', start: daysFromNow(1, 8, 0), duration: 30, room: 'Design Studio 1', seat: '—' },
  { id: 'e2', courseId: 'is301', type: 'midterm', start: daysFromNow(3, 9, 0), duration: 100, room: 'GK-301', seat: 'C-24' },
  { id: 'e3', courseId: 'is305', type: 'midterm', start: daysFromNow(5, 13, 0), duration: 120, room: 'GK-402', seat: 'B-11' },
  { id: 'e4', courseId: 'is312', type: 'midterm', start: daysFromNow(7, 10, 0), duration: 100, room: 'Lab Data 1', seat: 'PC-18' },
  { id: 'e5', courseId: 'is301', type: 'practical', start: daysFromNow(10, 9, 0), duration: 90, room: 'Lab Data 2', seat: 'PC-07' },
  { id: 'e6', courseId: 'is320', type: 'midterm', start: daysFromNow(12, 13, 0), duration: 100, room: 'GK-402', seat: 'A-05' },
  { id: 'e0', courseId: 'mb205', type: 'quiz', start: daysFromNow(-6, 10, 0), duration: 30, room: 'GK-205', seat: '—', score: 85 },
]

export const recentSessions = [
  { courseId: 'is301', date: daysFromNow(-7, 8, 0), status: 'present' as const },
  { courseId: 'mb205', date: daysFromNow(-7, 10, 0), status: 'excused' as const },
  { courseId: 'is305', date: daysFromNow(-7, 13, 0), status: 'present' as const },
  { courseId: 'is315', date: daysFromNow(-6, 8, 0), status: 'present' as const },
  { courseId: 'is312', date: daysFromNow(-8, 10, 0), status: 'absent' as const },
  { courseId: 'is310', date: daysFromNow(-9, 8, 0), status: 'late' as const },
]

export const ATTENDANCE_MIN = 75

export function attendanceRate(a: Course['attendance']): number {
  return a.total === 0 ? 0 : Math.round(((a.present + a.late) / a.total) * 100)
}
