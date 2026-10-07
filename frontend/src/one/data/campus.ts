import { daysFromNow } from '../lib/dates'

export type Tone = 'navy' | 'royal' | 'teal' | 'gold' | 'rose' | 'violet' | 'slate' | 'green'

export type NewsCategory = 'campus' | 'academic' | 'research' | 'student' | 'international' | 'career' | 'events' | 'achievement'

export interface Article {
  id: string
  category: NewsCategory
  title: string
  excerpt: string
  author: string
  date: string
  readTime: number
  tone: Tone
  body: string[]
}

export const news: Article[] = [
  {
    id: 'news-1',
    category: 'achievement',
    title: 'Civitas students win gold at the ASEAN Smart City Hackathon in Singapore',
    excerpt: 'A five-person Information Systems team built a flood early-warning platform that impressed judges from 11 countries.',
    author: 'Public Relations Office',
    date: daysFromNow(-1),
    readTime: 4,
    tone: 'navy',
    body: [
      'A team of five Information Systems students took home the gold medal at this year’s ASEAN Smart City Hackathon, held over 48 hours in Singapore.',
      'Their platform, “Banjir Siaga”, combines open rainfall data, community reports and a lightweight prediction model to send neighborhood-level flood alerts over messaging apps.',
      '“We wanted something that works for people who will never install a new app,” said team lead Nadia Putri. Judges praised the project’s focus on accessibility and its realistic deployment plan with local governments.',
      'The team will present the project at the Faculty of Engineering Innovation Day next month and is in talks with two municipalities about a pilot.',
    ],
  },
  {
    id: 'news-2',
    category: 'research',
    title: 'New research center for sustainable cities opens with three industry partners',
    excerpt: 'The Center for Urban Resilience will host interdisciplinary research on mobility, water and housing.',
    author: 'Research & Innovation Office',
    date: daysFromNow(-3),
    readTime: 5,
    tone: 'teal',
    body: [
      'The university officially opened the Center for Urban Resilience, an interdisciplinary research hub bringing together engineering, economics, law and psychology.',
      'Founding partners include a national infrastructure company, a water utility and a property developer, who will co-fund doctoral fellowships and field laboratories.',
      'Students can join research projects as assistants starting next semester through the Research portal.',
    ],
  },
  {
    id: 'news-3',
    category: 'international',
    title: '42 students depart for exchange semesters across Europe and East Asia',
    excerpt: 'Partner universities in the Netherlands, Germany, Japan and Taiwan welcome the largest cohort yet.',
    author: 'International Office',
    date: daysFromNow(-5),
    readTime: 3,
    tone: 'violet',
    body: [
      'The International Office sent off 42 students for exchange semesters this term — the largest cohort in the university’s history.',
      'Students will study at 14 partner institutions, with full credit transfer arranged in advance through learning agreements.',
      'Applications for next year’s exchange open in November.',
    ],
  },
  {
    id: 'news-4',
    category: 'academic',
    title: 'Curriculum refresh brings AI literacy to every undergraduate program',
    excerpt: 'Starting next academic year, all first-year students will take a two-credit course on responsible AI.',
    author: 'Vice Rector for Academic Affairs',
    date: daysFromNow(-6),
    readTime: 6,
    tone: 'royal',
    body: [
      'The Senate approved a curriculum refresh that adds an AI literacy course to every undergraduate program.',
      'The course combines hands-on practice with ethics, privacy and critical evaluation of AI-generated content.',
    ],
  },
  {
    id: 'news-5',
    category: 'career',
    title: 'Career Fair 2026 draws 80 employers and 3,000 job seekers',
    excerpt: 'Banking, technology and healthcare led recruitment, with 600 interviews held on the day.',
    author: 'Career Development Center',
    date: daysFromNow(-9),
    readTime: 3,
    tone: 'gold',
    body: ['The annual Career Fair welcomed 80 employers and more than 3,000 students and alumni over two days.'],
  },
  {
    id: 'news-6',
    category: 'student',
    title: 'Student choir wins grand prix at the Bali International Choir Festival',
    excerpt: 'The 36-voice ensemble performed a program of Indonesian folk songs and sacred music.',
    author: 'Student Affairs',
    date: daysFromNow(-11),
    readTime: 2,
    tone: 'rose',
    body: ['The university choir took the grand prix in the mixed choir category at the Bali International Choir Festival.'],
  },
  {
    id: 'news-7',
    category: 'campus',
    title: 'Library extends opening hours to 22:00 during exam weeks',
    excerpt: 'Quiet study zones and group rooms will stay open late during midterm and final exam periods.',
    author: 'University Library',
    date: daysFromNow(-12),
    readTime: 2,
    tone: 'slate',
    body: ['The central library will extend its opening hours to 22:00 on weekdays during exam weeks.'],
  },
  {
    id: 'news-8',
    category: 'events',
    title: 'Dies Natalis week to feature concerts, alumni talks and a charity run',
    excerpt: 'Celebrations run for seven days across all campuses, open to students, staff and the public.',
    author: 'Rectorate',
    date: daysFromNow(-14),
    readTime: 3,
    tone: 'green',
    body: ['The university will celebrate its anniversary with a week of events across all campuses.'],
  },
]

export const newsById = (id: string) => news.find((n) => n.id === id)

export interface Announcement {
  id: string
  category: 'academic' | 'finance' | 'exam' | 'campus'
  title: string
  date: string
  body: string
}

export const announcements: Announcement[] = [
  {
    id: 'ann-1',
    category: 'exam',
    title: 'Midterm exam timetable published',
    date: daysFromNow(-1),
    body: 'Check your exam rooms and seat numbers on the Exams page. Bring your student ID card to every exam.',
  },
  {
    id: 'ann-2',
    category: 'finance',
    title: 'Second tuition installment due soon',
    date: daysFromNow(-2),
    body: 'Installment 2 for Semester 5 is due in 12 days. Pay via virtual account to avoid registration holds.',
  },
  {
    id: 'ann-3',
    category: 'academic',
    title: 'Lecturer evaluation opens next week',
    date: daysFromNow(-4),
    body: 'Your feedback shapes teaching quality. The anonymous survey takes about five minutes per course.',
  },
  {
    id: 'ann-4',
    category: 'campus',
    title: 'Scheduled network maintenance on Saturday night',
    date: daysFromNow(-5),
    body: 'Campus Wi-Fi and online services may be intermittent between 23:00 and 03:00.',
  },
]

export type EventCategory = 'seminar' | 'workshop' | 'competition' | 'cultural' | 'sports' | 'career'

export interface CampusEvent {
  id: string
  title: string
  date: string
  end: string
  location: string
  organizer: string
  category: EventCategory
  seats: number
  tone: Tone
  description: string
}

export const events: CampusEvent[] = [
  {
    id: 'ev-1',
    title: 'Tech Talk: Building Responsible AI Products',
    date: daysFromNow(2, 15, 0),
    end: daysFromNow(2, 17, 0),
    location: 'Auditorium Yustinus',
    organizer: 'Faculty of Engineering',
    category: 'seminar',
    seats: 48,
    tone: 'navy',
    description: 'Product leaders from regional tech companies share how they evaluate, ship and monitor AI features responsibly.',
  },
  {
    id: 'ev-2',
    title: 'UX Research Bootcamp',
    date: daysFromNow(4, 9, 0),
    end: daysFromNow(4, 16, 0),
    location: 'Design Studio 1',
    organizer: 'HCI Lab',
    category: 'workshop',
    seats: 12,
    tone: 'violet',
    description: 'A full-day, hands-on workshop on interviews, affinity mapping and usability testing.',
  },
  {
    id: 'ev-3',
    title: 'Inter-Faculty Futsal Cup — Semifinals',
    date: daysFromNow(5, 16, 0),
    end: daysFromNow(5, 19, 0),
    location: 'Sports Center Hall A',
    organizer: 'Student Executive Board',
    category: 'sports',
    seats: 200,
    tone: 'green',
    description: 'Cheer for your faculty as the top four teams battle for a place in the final.',
  },
  {
    id: 'ev-4',
    title: 'Startup Pitch Night',
    date: daysFromNow(8, 18, 30),
    end: daysFromNow(8, 21, 0),
    location: 'Innovation Hub',
    organizer: 'Entrepreneurship Club',
    category: 'competition',
    seats: 60,
    tone: 'gold',
    description: 'Ten student startups pitch to angel investors for seed funding and incubation places.',
  },
  {
    id: 'ev-5',
    title: 'Nusantara Cultural Night',
    date: daysFromNow(11, 19, 0),
    end: daysFromNow(11, 22, 0),
    location: 'Main Plaza',
    organizer: 'Indonesian Culture Club',
    category: 'cultural',
    seats: 500,
    tone: 'rose',
    description: 'Dance, music and food from across the archipelago, performed by students from 20 provinces.',
  },
  {
    id: 'ev-6',
    title: 'CV Clinic with HR Leaders',
    date: daysFromNow(13, 13, 0),
    end: daysFromNow(13, 16, 0),
    location: 'Career Center, Building K',
    organizer: 'Career Development Center',
    category: 'career',
    seats: 25,
    tone: 'teal',
    description: 'Get your CV reviewed one-on-one by recruiters from banking, consulting and technology firms.',
  },
  {
    id: 'ev-7',
    title: 'Research Methods Seminar: Mixed Methods in Practice',
    date: daysFromNow(18, 10, 0),
    end: daysFromNow(18, 12, 0),
    location: 'Graduate School Hall',
    organizer: 'Graduate School',
    category: 'seminar',
    seats: 80,
    tone: 'slate',
    description: 'A practical session on designing studies that combine surveys, interviews and analytics.',
  },
  {
    id: 'ev-8',
    title: 'National Debate Championship',
    date: daysFromNow(32, 8, 0),
    end: daysFromNow(33, 17, 0),
    location: 'Law Faculty Moot Court',
    organizer: 'Law Student Association',
    category: 'competition',
    seats: 150,
    tone: 'royal',
    description: 'Teams from 30 universities compete in British Parliamentary format.',
  },
]

export const eventById = (id: string) => events.find((e) => e.id === id)

export type OrgCategory = 'academic' | 'sports' | 'arts' | 'social' | 'religious' | 'technology' | 'entrepreneurship'

export interface Organization {
  id: string
  name: string
  short: string
  category: OrgCategory
  members: number
  description: string
  nextEvent: { title: string; date: string }
  tone: Tone
}

export const organizations: Organization[] = [
  { id: 'org-1', name: 'Student Executive Board', short: 'BEM', category: 'academic', members: 64, description: 'The university-wide student government representing every faculty.', nextEvent: { title: 'Town Hall with the Rector', date: daysFromNow(6, 15) }, tone: 'navy' },
  { id: 'org-2', name: 'Information Systems Student Association', short: 'HMSI', category: 'academic', members: 312, description: 'Study groups, tech talks and industry visits for IS students.', nextEvent: { title: 'Industry Visit: Bank Data Center', date: daysFromNow(9, 9) }, tone: 'royal' },
  { id: 'org-3', name: 'Robotics & AI Club', short: 'RAI', category: 'technology', members: 128, description: 'Build robots, train models and compete nationally.', nextEvent: { title: 'Line Follower Workshop', date: daysFromNow(3, 16) }, tone: 'teal' },
  { id: 'org-4', name: 'Google Developer Student Club', short: 'GDSC', category: 'technology', members: 240, description: 'Peer learning on cloud, mobile and web technologies.', nextEvent: { title: 'Flutter Study Jam', date: daysFromNow(7, 14) }, tone: 'violet' },
  { id: 'org-5', name: 'University Choir', short: 'UC', category: 'arts', members: 86, description: 'Award-winning choir performing sacred, classical and folk repertoire.', nextEvent: { title: 'Open Rehearsal', date: daysFromNow(2, 18) }, tone: 'rose' },
  { id: 'org-6', name: 'Theatre & Film Society', short: 'TFS', category: 'arts', members: 74, description: 'Stage productions, short films and screenwriting workshops.', nextEvent: { title: 'Short Film Screening', date: daysFromNow(12, 19) }, tone: 'gold' },
  { id: 'org-7', name: 'Futsal Club', short: 'FUT', category: 'sports', members: 150, description: 'Training, friendlies and inter-university leagues.', nextEvent: { title: 'Futsal Cup Semifinals', date: daysFromNow(5, 16) }, tone: 'green' },
  { id: 'org-8', name: 'Badminton Club', short: 'BDM', category: 'sports', members: 92, description: 'Weekly training for all levels, from beginners to varsity.', nextEvent: { title: 'Weekend Open Play', date: daysFromNow(4, 8) }, tone: 'slate' },
  { id: 'org-9', name: 'Social Service Volunteers', short: 'SSV', category: 'social', members: 180, description: 'Community teaching, disaster relief and environmental action.', nextEvent: { title: 'Coastal Clean-up', date: daysFromNow(10, 6) }, tone: 'teal' },
  { id: 'org-10', name: 'Catholic Student Fellowship', short: 'KMK', category: 'religious', members: 210, description: 'Prayer, retreats and service grounded in faith.', nextEvent: { title: 'Campus Retreat', date: daysFromNow(15, 8) }, tone: 'navy' },
  { id: 'org-11', name: 'Interfaith Dialogue Forum', short: 'IDF', category: 'religious', members: 58, description: 'Building understanding across faiths through dialogue and shared service.', nextEvent: { title: 'Peace Dialogue Evening', date: daysFromNow(14, 18) }, tone: 'violet' },
  { id: 'org-12', name: 'Entrepreneurship Club', short: 'ENT', category: 'entrepreneurship', members: 134, description: 'Ideation sprints, mentoring and a student startup incubator.', nextEvent: { title: 'Startup Pitch Night', date: daysFromNow(8, 18) }, tone: 'gold' },
]

export type FacilityType = 'library' | 'laboratory' | 'sports' | 'auditorium' | 'cafeteria' | 'clinic' | 'studentCenter' | 'ministry'

export interface Facility {
  id: string
  name: string
  type: FacilityType
  description: string
  location: string
  open: number
  close: number
  days: string
  contact: string
  services: string[]
  tone: Tone
  bookable: boolean
}

export const facilities: Facility[] = [
  { id: 'fac-1', name: 'Central Library', type: 'library', description: 'Five floors of collections, quiet study zones, group rooms and a digital media lab.', location: 'Building L, Semanggi Campus', open: 7, close: 21, days: 'Mon–Sat', contact: 'library@civitas.ac.id', services: ['Study rooms', 'Printing', 'Research consultation', 'Digital media lab'], tone: 'navy', bookable: true },
  { id: 'fac-2', name: 'Data & Computing Laboratory', type: 'laboratory', description: 'High-performance workstations, GPU servers and licensed analytics software.', location: 'Building K, Floor 3', open: 8, close: 20, days: 'Mon–Fri', contact: 'lab.data@civitas.ac.id', services: ['GPU computing', 'Software licenses', 'Lab assistants'], tone: 'royal', bookable: true },
  { id: 'fac-3', name: 'Sports Center', type: 'sports', description: 'Indoor courts for futsal, basketball and badminton, plus a fitness studio.', location: 'BSD Campus', open: 6, close: 22, days: 'Daily', contact: 'sports@civitas.ac.id', services: ['Court booking', 'Fitness studio', 'Equipment rental'], tone: 'green', bookable: true },
  { id: 'fac-4', name: 'Auditorium Yustinus', type: 'auditorium', description: 'A 1,200-seat auditorium for convocations, concerts and conferences.', location: 'Building Yustinus, Floor 15', open: 8, close: 22, days: 'By booking', contact: 'events@civitas.ac.id', services: ['Event booking', 'AV support', 'Live streaming'], tone: 'violet', bookable: true },
  { id: 'fac-5', name: 'Student Food Court', type: 'cafeteria', description: 'Twenty stalls with affordable, halal and vegetarian options.', location: 'Building G, Ground Floor', open: 7, close: 19, days: 'Mon–Sat', contact: 'facilities@civitas.ac.id', services: ['Halal food', 'Vegetarian options', 'Cashless payment'], tone: 'gold', bookable: false },
  { id: 'fac-6', name: 'Campus Clinic', type: 'clinic', description: 'General practitioners, dental care and health insurance assistance for students.', location: 'Building B, Floor 1', open: 8, close: 16, days: 'Mon–Fri', contact: '+62 21 5703 306', services: ['General check-up', 'Dental care', 'Vaccination', 'Insurance claims'], tone: 'rose', bookable: true },
  { id: 'fac-7', name: 'Student Center', type: 'studentCenter', description: 'Home to student organizations, co-working space and the Student Affairs desk.', location: 'Building S', open: 7, close: 22, days: 'Mon–Sat', contact: 'student.affairs@civitas.ac.id', services: ['Co-working', 'Meeting rooms', 'Lockers'], tone: 'teal', bookable: true },
  { id: 'fac-8', name: 'Campus Chapel & Prayer Rooms', type: 'ministry', description: 'Chapel, musholla and quiet rooms open to people of every faith.', location: 'Building A & Building G', open: 6, close: 21, days: 'Daily', contact: 'ministry@civitas.ac.id', services: ['Daily mass', 'Musholla', 'Spiritual counseling'], tone: 'slate', bookable: false },
]
