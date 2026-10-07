import { daysFromNow } from '../lib/dates'
import type { Tone } from './campus'

export type Level = 'undergraduate' | 'professional' | 'master' | 'doctoral' | 'specialist'

export interface Faculty {
  id: string
  name: string
  short: string
  description: string
  dean: string
  established: number
  accreditation: string
  students: number
  lecturers: number
  departments: string[]
  facilities: string[]
  tone: Tone
}

export const faculties: Faculty[] = [
  { id: 'engineering', name: 'Faculty of Engineering', short: 'FT', description: 'Engineering education rooted in research, design thinking and industry partnership — from industrial and electrical engineering to information systems.', dean: 'Prof. Dr. Hendra Kusuma', established: 1985, accreditation: 'Excellent (Unggul)', students: 3150, lecturers: 128, departments: ['Industrial Engineering', 'Electrical Engineering', 'Mechanical Engineering', 'Information Systems & Informatics'], facilities: ['Data & Computing Laboratory', 'Robotics Lab', 'Manufacturing Workshop', 'Innovation Hub'], tone: 'navy' },
  { id: 'business', name: 'Faculty of Economics & Business', short: 'FEB', description: 'Developing ethical business leaders, accountants and economists for Indonesia and the region.', dean: 'Dr. Agnes Santoso, M.B.A.', established: 1960, accreditation: 'Excellent (Unggul) · AACSB candidate', students: 5800, lecturers: 196, departments: ['Management', 'Accounting', 'Economics'], facilities: ['Trading Lab', 'Business Incubator', 'Tax Center'], tone: 'gold' },
  { id: 'law', name: 'Faculty of Law', short: 'FH', description: 'A tradition of legal scholarship committed to justice, human rights and the rule of law.', dean: 'Dr. Ignatius Wibowo, S.H., LL.M.', established: 1963, accreditation: 'Excellent (Unggul)', students: 2100, lecturers: 84, departments: ['Civil Law', 'Criminal Law', 'International Law', 'Constitutional Law'], facilities: ['Moot Court', 'Legal Aid Clinic'], tone: 'slate' },
  { id: 'medicine', name: 'Faculty of Medicine & Health Sciences', short: 'FKIK', description: 'Educating compassionate physicians and health professionals with an affiliated teaching hospital.', dean: 'dr. Natalia Susanto, Sp.PD', established: 1967, accreditation: 'Excellent (Unggul)', students: 2600, lecturers: 210, departments: ['Medicine', 'Pharmacy', 'Nursing'], facilities: ['Teaching Hospital', 'Simulation Center', 'Anatomy Lab'], tone: 'rose' },
  { id: 'psychology', name: 'Faculty of Psychology', short: 'FP', description: 'Science-based psychology education and community mental-health services.', dean: 'Dr. Felicia Tanoto, M.Psi.', established: 1992, accreditation: 'Excellent (Unggul)', students: 1400, lecturers: 62, departments: ['Clinical Psychology', 'Industrial & Organizational Psychology', 'Educational Psychology'], facilities: ['Psychology Service Center', 'Observation Lab'], tone: 'violet' },
  { id: 'education', name: 'Faculty of Education & Language', short: 'FKIP', description: 'Preparing teachers, linguists and educational innovators.', dean: 'Dr. Paulus Hadiwijaya, M.Pd.', established: 1960, accreditation: 'Very Good (Baik Sekali)', students: 1250, lecturers: 70, departments: ['English Education', 'Indonesian Language', 'Guidance & Counseling', 'Primary Teacher Education'], facilities: ['Micro-teaching Lab', 'Language Center'], tone: 'teal' },
  { id: 'communication', name: 'Faculty of Business Administration & Communication', short: 'FIABIKOM', description: 'Communication, media and administration for a connected world.', dean: 'Dr. Veronika Lestari, M.Si.', established: 2014, accreditation: 'Very Good (Baik Sekali)', students: 1650, lecturers: 58, departments: ['Communication Science', 'Business Administration'], facilities: ['Broadcast Studio', 'Media Lab'], tone: 'royal' },
  { id: 'biotech', name: 'Faculty of Biotechnology', short: 'FTB', description: 'Biotechnology research and education for food, health and environmental solutions.', dean: 'Dr. Yohana Sutanto, M.Sc.', established: 2002, accreditation: 'Very Good (Baik Sekali)', students: 520, lecturers: 34, departments: ['Biotechnology', 'Food Technology'], facilities: ['Molecular Biology Lab', 'Pilot Plant'], tone: 'green' },
]

export const facultyById = (id: string) => faculties.find((f) => f.id === id)

export interface Program {
  id: string
  facultyId: string
  name: string
  level: Level
  degree: string
  years: number
  accreditation: string
  description: string
  credits: number
  careers: string[]
  requirements: string[]
  lecturerIds: string[]
}

const ug = (id: string, facultyId: string, name: string, degree: string, description: string, careers: string[]): Program => ({
  id,
  facultyId,
  name,
  level: 'undergraduate',
  degree,
  years: 4,
  accreditation: 'Excellent',
  description,
  credits: 144,
  careers,
  requirements: ['High school diploma or equivalent', 'Academic potential test', 'Report cards from grades 10–12', 'Interview (selected programs)'],
  lecturerIds: [],
})

export const programs: Program[] = [
  { ...ug('information-systems', 'engineering', 'Information Systems', 'S.Kom.', 'Bridge business and technology: design data-driven information systems that help organizations work better.', ['Business analyst', 'Data analyst', 'IT consultant', 'Product manager', 'Enterprise architect']), lecturerIds: ['lec-1', 'lec-3', 'lec-4', 'lec-5', 'lec-6', 'lec-7'] },
  ug('industrial-engineering', 'engineering', 'Industrial Engineering', 'S.T.', 'Design and improve systems of people, materials and information for efficient operations.', ['Operations engineer', 'Supply chain analyst', 'Quality manager']),
  ug('electrical-engineering', 'engineering', 'Electrical Engineering', 'S.T.', 'From power systems to embedded electronics and IoT.', ['Electrical engineer', 'Embedded developer', 'Energy consultant']),
  ug('management', 'business', 'Management', 'S.M.', 'Strategy, marketing, finance and people management for tomorrow’s leaders.', ['Management trainee', 'Marketing manager', 'Entrepreneur']),
  ug('accounting', 'business', 'Accounting', 'S.Ak.', 'Financial reporting, audit and taxation with strong ethical foundations.', ['Auditor', 'Tax consultant', 'Financial analyst']),
  { ...ug('mm', 'business', 'Master of Management', 'M.M.', 'An executive-friendly master’s program in strategic management.', ['Senior manager', 'Consultant']), level: 'master', years: 2, credits: 42, requirements: ['Bachelor’s degree', 'GPA ≥ 2.75', 'English proficiency', 'Interview'] },
  ug('law', 'law', 'Law', 'S.H.', 'Legal reasoning, advocacy and justice in Indonesian and international contexts.', ['Lawyer', 'Legal counsel', 'Notary', 'Judge']),
  { ...ug('doctor-law', 'law', 'Doctor of Law', 'Dr.', 'Original legal research contributing to doctrine and policy.', ['Academic', 'Policy advisor']), level: 'doctoral', years: 3, credits: 48, requirements: ['Master’s degree in law', 'Research proposal', 'Publication record'] },
  ug('medicine', 'medicine', 'Medicine', 'S.Ked.', 'Pre-clinical medical education integrated with early clinical exposure.', ['Physician (after professional program)', 'Researcher']),
  { ...ug('medical-profession', 'medicine', 'Medical Doctor Profession', 'dr.', 'Clinical rotations at the teaching hospital and network hospitals.', ['General practitioner']), level: 'professional', years: 2, credits: 50, requirements: ['Bachelor of Medicine (S.Ked.)', 'Clinical readiness exam'] },
  { ...ug('internal-medicine', 'medicine', 'Internal Medicine Specialist', 'Sp.PD', 'Specialist residency in internal medicine.', ['Internist']), level: 'specialist', years: 4, credits: 90, requirements: ['Medical doctor license', 'Clinical experience', 'Entrance exam'] },
  ug('psychology', 'psychology', 'Psychology', 'S.Psi.', 'Understand human behavior through science, assessment and intervention.', ['HR specialist', 'Researcher', 'Counselor (after profession)']),
  { ...ug('professional-psychology', 'psychology', 'Professional Psychology', 'M.Psi., Psikolog', 'Professional training to become a licensed psychologist.', ['Clinical psychologist', 'Educational psychologist']), level: 'professional', years: 2, credits: 45, requirements: ['Bachelor of Psychology', 'Psychological assessment', 'Interview'] },
  ug('english-education', 'education', 'English Education', 'S.Pd.', 'Become an inspiring English teacher and curriculum designer.', ['Teacher', 'Curriculum developer', 'Translator']),
  ug('communication', 'communication', 'Communication Science', 'S.I.Kom.', 'Strategic communication, journalism and digital media.', ['PR specialist', 'Journalist', 'Content strategist']),
  ug('biotechnology', 'biotech', 'Biotechnology', 'S.Si.', 'Apply biology and engineering to food, health and environment.', ['Lab scientist', 'QA specialist', 'Biotech entrepreneur']),
]

export const programById = (id: string) => programs.find((p) => p.id === id)

export const isCurriculum = [
  { semester: 1, courses: ['Introduction to Information Systems', 'Programming Fundamentals', 'Discrete Mathematics', 'Pancasila & Civic Education', 'Academic English'] },
  { semester: 2, courses: ['Object-Oriented Programming', 'Business Process Management', 'Statistics for Business', 'Accounting for IS'] },
  { semester: 3, courses: ['Systems Analysis & Design', 'Web Programming', 'Computer Networks', 'Data Structures'] },
  { semester: 4, courses: ['IT Project Management', 'Mobile Application Development', 'Operating Systems', 'E-Business'] },
  { semester: 5, courses: ['Database Systems', 'Software Engineering', 'Human-Computer Interaction', 'Data Analytics & Visualization', 'Enterprise Architecture'] },
  { semester: 6, courses: ['IT Governance', 'Business Intelligence', 'Cloud Computing', 'Elective I'] },
  { semester: 7, courses: ['Internship (MBKM)', 'Research Methodology', 'Elective II'] },
  { semester: 8, courses: ['Undergraduate Thesis'] },
]

export const universityStats = { programs: 52, students: 18_400, alumni: 96_000, partners: 140 }

/* ---------- Research ---------- */

export const researchStats = { publications: 1284, projects: 186, citations: 21_450, researchers: 642 }

/** Publications by field, last 5 years — one series, so one hue. */
export const researchCategories = [
  { field: 'Health & Medicine', value: 362 },
  { field: 'Engineering & Technology', value: 298 },
  { field: 'Economics & Business', value: 214 },
  { field: 'Law & Social Sciences', value: 168 },
  { field: 'Psychology & Education', value: 152 },
  { field: 'Biotechnology', value: 90 },
]

export const researchTrend = [
  { year: '2022', value: 212 },
  { year: '2023', value: 238 },
  { year: '2024', value: 261 },
  { year: '2025', value: 284 },
  { year: '2026', value: 289 },
]

export const researchCenters = [
  { id: 'rc-1', name: 'Center for Urban Resilience', focus: 'Mobility, water and housing in megacities', tone: 'teal' as Tone },
  { id: 'rc-2', name: 'Center for Health Research', focus: 'Tropical medicine, public health and aging', tone: 'rose' as Tone },
  { id: 'rc-3', name: 'Center for Digital Society', focus: 'AI ethics, digital inclusion and data governance', tone: 'navy' as Tone },
  { id: 'rc-4', name: 'Center for Business Ethics', focus: 'Governance, sustainability and family business', tone: 'gold' as Tone },
  { id: 'rc-5', name: 'Center for Law & Human Rights', focus: 'Access to justice and constitutional studies', tone: 'slate' as Tone },
]

export const researchProjects = [
  { id: 'rp-1', title: 'Community flood early-warning with open data', lead: 'Dr. Kevin Halim, M.T.', funding: 'Rp 850 jt', progress: 65, field: 'Engineering & Technology' },
  { id: 'rp-2', title: 'Dementia risk screening in primary care', lead: 'dr. Natalia Susanto, Sp.PD', funding: 'Rp 1,2 M', progress: 40, field: 'Health & Medicine' },
  { id: 'rp-3', title: 'Digital literacy of older adults in Jakarta', lead: 'Dr. Clara Anindita, M.Sc.', funding: 'Rp 420 jt', progress: 80, field: 'Psychology & Education' },
  { id: 'rp-4', title: 'ESG disclosure quality of listed family firms', lead: 'Dr. Agnes Santoso, M.B.A.', funding: 'Rp 310 jt', progress: 25, field: 'Economics & Business' },
]

export const publications = [
  { id: 'pub-1', title: 'Lightweight flood nowcasting from crowdsourced reports in tropical megacities', venue: 'IEEE Access', year: 2026, citations: 12, authors: 'Halim K., Putri N., Wijaya M.' },
  { id: 'pub-2', title: 'Usability of mobile health apps among Indonesian older adults', venue: 'JMIR mHealth', year: 2026, citations: 8, authors: 'Anindita C., Susanto N.' },
  { id: 'pub-3', title: 'Enterprise architecture maturity in Indonesian state-owned enterprises', venue: 'Information Systems Frontiers', year: 2025, citations: 21, authors: 'Kristanti L., Gunawan S.' },
  { id: 'pub-4', title: 'Algorithmic accountability under Indonesia’s Personal Data Protection Law', venue: 'Asian Journal of Law and Society', year: 2025, citations: 17, authors: 'Wibowo I.' },
]

/* ---------- International ---------- */

export type Region = 'asia' | 'europe' | 'oceania' | 'americas'

export const partners: Array<{ id: string; name: string; country: string; region: Region }> = [
  { id: 'p-1', name: 'Radboud University', country: 'Netherlands', region: 'europe' },
  { id: 'p-2', name: 'KU Leuven', country: 'Belgium', region: 'europe' },
  { id: 'p-3', name: 'University of Würzburg', country: 'Germany', region: 'europe' },
  { id: 'p-4', name: 'Sophia University', country: 'Japan', region: 'asia' },
  { id: 'p-5', name: 'Fu Jen Catholic University', country: 'Taiwan', region: 'asia' },
  { id: 'p-6', name: 'Ateneo de Manila University', country: 'Philippines', region: 'asia' },
  { id: 'p-7', name: 'Sogang University', country: 'South Korea', region: 'asia' },
  { id: 'p-8', name: 'Australian Catholic University', country: 'Australia', region: 'oceania' },
  { id: 'p-9', name: 'University of Auckland', country: 'New Zealand', region: 'oceania' },
  { id: 'p-10', name: 'Boston College', country: 'United States', region: 'americas' },
  { id: 'p-11', name: 'Santa Clara University', country: 'United States', region: 'americas' },
  { id: 'p-12', name: 'Pontifical Catholic University of Chile', country: 'Chile', region: 'americas' },
]

export const intlPrograms = [
  { id: 'ip-1', name: 'Semester Exchange', duration: '1 semester', desc: 'Study at a partner university with full credit transfer.', tone: 'royal' as Tone },
  { id: 'ip-2', name: 'Double Degree — Management', duration: '2 + 2 years', desc: 'Earn degrees from Civitas and a European partner.', tone: 'gold' as Tone },
  { id: 'ip-3', name: 'Summer School in Asia', duration: '3–4 weeks', desc: 'Short programs on culture, business and technology.', tone: 'teal' as Tone },
  { id: 'ip-4', name: 'Global Research Internship', duration: '2–3 months', desc: 'Join a research lab abroad during semester break.', tone: 'violet' as Tone },
]

export const intlEvents = [
  { id: 'ie-1', title: 'Exchange Info Session: Europe', date: daysFromNow(6, 13), location: 'International Office, Building A' },
  { id: 'ie-2', title: 'Global Café: Taiwan Night', date: daysFromNow(13, 18), location: 'Student Center' },
  { id: 'ie-3', title: 'IELTS Preparation Clinic', date: daysFromNow(20, 10), location: 'Language Center' },
]
