import { daysFromNow } from '../lib/dates'
import type { Tone } from './campus'

/* ---------- Finance ---------- */

export interface Invoice {
  id: string
  description: string
  semester: number
  date: string
  due: string
  amount: number
  status: 'paid' | 'unpaid'
}

export const invoices: Invoice[] = [
  { id: 'INV-2026-05-02', description: 'Semester 5 tuition — installment 2', semester: 5, date: daysFromNow(-18), due: daysFromNow(12), amount: 6_250_000, status: 'unpaid' },
  { id: 'INV-2026-05-03', description: 'Laboratory & practicum fee', semester: 5, date: daysFromNow(-18), due: daysFromNow(12), amount: 750_000, status: 'unpaid' },
  { id: 'INV-2026-05-01', description: 'Semester 5 tuition — installment 1', semester: 5, date: daysFromNow(-62), due: daysFromNow(-48), amount: 6_250_000, status: 'paid' },
  { id: 'INV-2026-04-02', description: 'Semester 4 tuition — installment 2', semester: 4, date: daysFromNow(-160), due: daysFromNow(-146), amount: 6_000_000, status: 'paid' },
  { id: 'INV-2026-04-03', description: 'Student activity fee', semester: 4, date: daysFromNow(-200), due: daysFromNow(-186), amount: 350_000, status: 'paid' },
  { id: 'INV-2026-04-01', description: 'Semester 4 tuition — installment 1', semester: 4, date: daysFromNow(-240), due: daysFromNow(-226), amount: 6_000_000, status: 'paid' },
  { id: 'INV-2025-03-02', description: 'Semester 3 tuition — installment 2', semester: 3, date: daysFromNow(-340), due: daysFromNow(-326), amount: 6_000_000, status: 'paid' },
  { id: 'INV-2025-03-01', description: 'Semester 3 tuition — installment 1', semester: 3, date: daysFromNow(-420), due: daysFromNow(-406), amount: 6_000_000, status: 'paid' },
]

export const tuitionBreakdown = [
  { label: 'Tuition (SPP)', amount: 10_500_000 },
  { label: 'Credit fee (22 credits)', amount: 2_000_000 },
  { label: 'Laboratory & practicum', amount: 750_000 },
]

/* ---------- Library ---------- */

export interface Book {
  id: string
  title: string
  author: string
  category: string
  year: number
  available: number
  copies: number
  tone: Tone
  isbn: string
}

export const books: Book[] = [
  { id: 'bk-1', title: 'Database System Concepts', author: 'Silberschatz, Korth & Sudarshan', category: 'Computer Science', year: 2019, available: 3, copies: 8, tone: 'navy', isbn: '978-0078022159' },
  { id: 'bk-2', title: 'Designing Data-Intensive Applications', author: 'Martin Kleppmann', category: 'Computer Science', year: 2017, available: 0, copies: 4, tone: 'teal', isbn: '978-1449373320' },
  { id: 'bk-3', title: 'Don’t Make Me Think, Revisited', author: 'Steve Krug', category: 'Design', year: 2014, available: 2, copies: 3, tone: 'rose', isbn: '978-0321965516' },
  { id: 'bk-4', title: 'Software Engineering', author: 'Ian Sommerville', category: 'Computer Science', year: 2015, available: 5, copies: 10, tone: 'royal', isbn: '978-0133943030' },
  { id: 'bk-5', title: 'Storytelling with Data', author: 'Cole Nussbaumer Knaflic', category: 'Data Science', year: 2015, available: 1, copies: 3, tone: 'gold', isbn: '978-1119002253' },
  { id: 'bk-6', title: 'Enterprise Architecture as Strategy', author: 'Ross, Weill & Robertson', category: 'Management', year: 2006, available: 2, copies: 2, tone: 'slate', isbn: '978-1591398394' },
  { id: 'bk-7', title: 'Security Engineering', author: 'Ross Anderson', category: 'Computer Science', year: 2020, available: 0, copies: 2, tone: 'violet', isbn: '978-1119642787' },
  { id: 'bk-8', title: 'Business Communication Today', author: 'Bovée & Thill', category: 'Business', year: 2021, available: 4, copies: 6, tone: 'green', isbn: '978-0135891827' },
  { id: 'bk-9', title: 'The Design of Everyday Things', author: 'Don Norman', category: 'Design', year: 2013, available: 2, copies: 5, tone: 'gold', isbn: '978-0465050659' },
  { id: 'bk-10', title: 'Python for Data Analysis', author: 'Wes McKinney', category: 'Data Science', year: 2022, available: 3, copies: 6, tone: 'teal', isbn: '978-1098104030' },
  { id: 'bk-11', title: 'Clean Architecture', author: 'Robert C. Martin', category: 'Computer Science', year: 2017, available: 1, copies: 4, tone: 'navy', isbn: '978-0134494166' },
  { id: 'bk-12', title: 'Ethics in Information Technology', author: 'George Reynolds', category: 'Philosophy', year: 2018, available: 3, copies: 3, tone: 'rose', isbn: '978-1337405874' },
]

export const loans = [
  { id: 'ln-1', bookId: 'bk-4', borrowed: daysFromNow(-9), due: daysFromNow(5) },
  { id: 'ln-2', bookId: 'bk-5', borrowed: daysFromNow(-12), due: daysFromNow(2) },
]

export const loanHistory = [
  { id: 'lh-1', bookId: 'bk-3', borrowed: daysFromNow(-60), returned: daysFromNow(-46) },
  { id: 'lh-2', bookId: 'bk-11', borrowed: daysFromNow(-95), returned: daysFromNow(-82) },
  { id: 'lh-3', bookId: 'bk-9', borrowed: daysFromNow(-130), returned: daysFromNow(-118) },
  { id: 'lh-4', bookId: 'bk-1', borrowed: daysFromNow(-180), returned: daysFromNow(-166) },
]

export const digitalBooks = [
  { id: 'eb-1', title: 'Introduction to Information Retrieval', author: 'Manning, Raghavan & Schütze', format: 'PDF', tone: 'navy' as Tone },
  { id: 'eb-2', title: 'Open Data Structures', author: 'Pat Morin', format: 'EPUB', tone: 'teal' as Tone },
  { id: 'eb-3', title: 'Research Methods for Business Students', author: 'Saunders, Lewis & Thornhill', format: 'PDF', tone: 'gold' as Tone },
  { id: 'eb-4', title: 'Think Stats', author: 'Allen B. Downey', format: 'HTML', tone: 'violet' as Tone },
]

export const ejournals = [
  { id: 'ej-1', name: 'IEEE Xplore', publisher: 'IEEE', field: 'Engineering & computing', titles: '6M+ documents' },
  { id: 'ej-2', name: 'ScienceDirect', publisher: 'Elsevier', field: 'Multidisciplinary', titles: '2,500 journals' },
  { id: 'ej-3', name: 'SpringerLink', publisher: 'Springer Nature', field: 'Multidisciplinary', titles: '3,000 journals' },
  { id: 'ej-4', name: 'JSTOR', publisher: 'ITHAKA', field: 'Humanities & social sciences', titles: '2,800 journals' },
  { id: 'ej-5', name: 'ProQuest Dissertations', publisher: 'ProQuest', field: 'Theses & dissertations', titles: '5M+ theses' },
  { id: 'ej-6', name: 'Garuda', publisher: 'Kemendikbudristek', field: 'Indonesian journals', titles: '1.5M articles' },
]

/* ---------- Career ---------- */

export type Employment = 'fullTime' | 'partTime' | 'internship' | 'contract'

export interface Job {
  id: string
  company: string
  position: string
  location: string
  type: Employment
  deadline: string
  tone: Tone
  tags: string[]
}

export const jobs: Job[] = [
  { id: 'job-1', company: 'Bank Central Nusantara', position: 'Data Analyst Management Trainee', location: 'Jakarta', type: 'fullTime', deadline: daysFromNow(14), tone: 'navy', tags: ['SQL', 'Python', 'Banking'] },
  { id: 'job-2', company: 'Tokoku Digital', position: 'UX Research Intern', location: 'Jakarta · Hybrid', type: 'internship', deadline: daysFromNow(6), tone: 'rose', tags: ['UX', 'Research'] },
  { id: 'job-3', company: 'Archipelago Consulting', position: 'Business Analyst Intern', location: 'Jakarta', type: 'internship', deadline: daysFromNow(10), tone: 'gold', tags: ['Consulting', 'Excel'] },
  { id: 'job-4', company: 'Nusantara Health Group', position: 'IT Support Specialist', location: 'Tangerang', type: 'contract', deadline: daysFromNow(20), tone: 'teal', tags: ['Helpdesk', 'Networking'] },
  { id: 'job-5', company: 'Garuda Logistics', position: 'Software Engineer (Graduate)', location: 'Surabaya', type: 'fullTime', deadline: daysFromNow(25), tone: 'royal', tags: ['Java', 'Cloud'] },
  { id: 'job-6', company: 'EduSpark', position: 'Part-time Content Developer', location: 'Remote', type: 'partTime', deadline: daysFromNow(8), tone: 'violet', tags: ['Writing', 'EdTech'] },
  { id: 'job-7', company: 'Sinar Energi', position: 'Cybersecurity Intern', location: 'Jakarta', type: 'internship', deadline: daysFromNow(3), tone: 'slate', tags: ['Security', 'SOC'] },
  { id: 'job-8', company: 'Tokoku Digital', position: 'Product Manager Associate', location: 'Jakarta', type: 'fullTime', deadline: daysFromNow(30), tone: 'green', tags: ['Product', 'Agile'] },
]

export const companyPartners = [
  'Bank Central Nusantara',
  'Tokoku Digital',
  'Archipelago Consulting',
  'Garuda Logistics',
  'Nusantara Health Group',
  'Sinar Energi',
  'EduSpark',
  'Kopi Kita Group',
]

/* ---------- Scholarships ---------- */

export type ScholarshipTag = 'undergraduate' | 'master' | 'doctoral' | 'international' | 'merit' | 'financialAid'

export interface Scholarship {
  id: string
  name: string
  provider: string
  amount: string
  deadline: string
  eligibility: string
  tags: ScholarshipTag[]
  tone: Tone
}

export const scholarships: Scholarship[] = [
  { id: 'sch-1', name: 'Rector’s Excellence Scholarship', provider: 'Civitas University', amount: '100% tuition', deadline: daysFromNow(21), eligibility: 'GPA ≥ 3.60, active in student organizations', tags: ['undergraduate', 'merit'], tone: 'navy' },
  { id: 'sch-2', name: 'KIP Kuliah', provider: 'Ministry of Education', amount: 'Tuition + living allowance', deadline: daysFromNow(5), eligibility: 'Students from low-income households', tags: ['undergraduate', 'financialAid'], tone: 'teal' },
  { id: 'sch-3', name: 'LPDP Master’s Scholarship', provider: 'LPDP', amount: 'Full funding', deadline: daysFromNow(45), eligibility: 'Bachelor’s graduates, GPA ≥ 3.00, English proficiency', tags: ['master', 'international'], tone: 'royal' },
  { id: 'sch-4', name: 'Erasmus+ Exchange Grant', provider: 'European Commission', amount: '€850 / month', deadline: daysFromNow(30), eligibility: 'Semester 3–6 students with partner nomination', tags: ['undergraduate', 'international'], tone: 'violet' },
  { id: 'sch-5', name: 'Doctoral Research Fellowship', provider: 'Center for Urban Resilience', amount: 'Rp 8.000.000 / month', deadline: daysFromNow(60), eligibility: 'Doctoral candidates in urban studies', tags: ['doctoral', 'merit'], tone: 'gold' },
  { id: 'sch-6', name: 'Alumni Solidarity Fund', provider: 'Alumni Association', amount: 'Up to 50% tuition', deadline: daysFromNow(-3), eligibility: 'Students facing sudden financial hardship', tags: ['undergraduate', 'financialAid'], tone: 'rose' },
  { id: 'sch-7', name: 'Taiwan ICDF Scholarship', provider: 'Taiwan ICDF', amount: 'Full funding', deadline: daysFromNow(75), eligibility: 'Master’s and doctoral applicants from partner countries', tags: ['master', 'doctoral', 'international'], tone: 'green' },
  { id: 'sch-8', name: 'Bank Central Nusantara Tech Scholarship', provider: 'Bank Central Nusantara', amount: 'Rp 15.000.000 / year', deadline: daysFromNow(12), eligibility: 'IS/CS students, semester 3–5, GPA ≥ 3.40', tags: ['undergraduate', 'merit'], tone: 'slate' },
]

/* ---------- Notifications ---------- */

export type NotifCategory = 'academic' | 'finance' | 'assignment' | 'exam' | 'event' | 'announcement' | 'system'

export interface Notification {
  id: string
  category: NotifCategory
  title: string
  body: string
  at: string
  read: boolean
  to: string
}

const hoursAgo = (h: number) => new Date(Date.now() - h * 3_600_000).toISOString()

export const notifications: Notification[] = [
  { id: 'n-1', category: 'assignment', title: 'SQL Query Lab 3 is due today', body: 'Database Systems · due 23:59 tonight.', at: hoursAgo(1), read: false, to: '/one/assignments' },
  { id: 'n-2', category: 'exam', title: 'HCI quiz tomorrow at 08:00', body: 'Design Studio 1 · 30 minutes.', at: hoursAgo(3), read: false, to: '/one/exams' },
  { id: 'n-3', category: 'academic', title: 'New grade published', body: 'Risk Assessment Matrix — 88/100.', at: hoursAgo(6), read: false, to: '/one/grades' },
  { id: 'n-4', category: 'finance', title: 'Invoice INV-2026-05-02 issued', body: 'Semester 5 tuition installment 2 · due in 12 days.', at: hoursAgo(20), read: false, to: '/one/finance' },
  { id: 'n-5', category: 'event', title: 'Seats filling up: Tech Talk on Responsible AI', body: 'Only 48 seats left — register now.', at: hoursAgo(26), read: true, to: '/one/campus/events' },
  { id: 'n-6', category: 'announcement', title: 'Midterm exam timetable published', body: 'Check your rooms and seat numbers.', at: hoursAgo(30), read: true, to: '/one/news' },
  { id: 'n-7', category: 'academic', title: 'Course material uploaded', body: 'Week 7 — Window functions cheat sheet.', at: hoursAgo(50), read: true, to: '/one/courses/is301' },
  { id: 'n-8', category: 'system', title: 'New sign-in from Chrome on Windows', body: 'If this wasn’t you, change your password.', at: hoursAgo(72), read: true, to: '/one/settings' },
  { id: 'n-9', category: 'assignment', title: 'Feedback on TOGAF ADM Phase B Brief', body: 'Enterprise Architecture · 92/100.', at: hoursAgo(96), read: true, to: '/one/assignments' },
  { id: 'n-10', category: 'event', title: 'You joined Robotics & AI Club', body: 'Welcome! Your first meetup is this week.', at: hoursAgo(140), read: true, to: '/one/campus/organizations' },
]

/* ---------- Student services ---------- */

export const serviceCatalog = [
  { key: 'letter', eta: '1–2 days' },
  { key: 'transcript', eta: '3 days' },
  { key: 'leave', eta: '5 days' },
  { key: 'idcard', eta: '3 days' },
  { key: 'it', eta: '< 1 day' },
  { key: 'counseling', eta: '2 days' },
  { key: 'health', eta: 'Same day' },
  { key: 'dorm', eta: '7 days' },
] as const

export type ServiceKey = (typeof serviceCatalog)[number]['key']

export const documents = [
  { id: 'doc-1', name: 'Student ID card (KTM)', type: 'PDF', status: 'verified' as const, updated: daysFromNow(-400) },
  { id: 'doc-2', name: 'High school diploma', type: 'PDF', status: 'verified' as const, updated: daysFromNow(-420) },
  { id: 'doc-3', name: 'National ID (KTP)', type: 'JPG', status: 'verified' as const, updated: daysFromNow(-420) },
  { id: 'doc-4', name: 'Family card (KK)', type: 'PDF', status: 'inReview' as const, updated: daysFromNow(-4) },
  { id: 'doc-5', name: 'TOEFL ITP certificate', type: 'PDF', status: 'missing' as const, updated: '' },
]
