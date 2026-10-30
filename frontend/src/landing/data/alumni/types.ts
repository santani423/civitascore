/**
 * Komunitas Alumni — demonstration data shapes.
 *
 * Everything in this folder is SAMPLE content for the UI (every page says so):
 * the people and companies are fictional and none of it is official Unika
 * Atma Jaya information. The staff Alumni module (pages/alumni, /alumni) is a
 * separate thing and is not read from here. Content text is Indonesian in
 * every language (rendered with lang="id"); only the interface around it is
 * translated (i18n `alumni.*`).
 */
import type { FacultyKey } from '../faculties'
import type { PhotoKey } from '../photos'
import type { RelativeTime } from '../student-affairs/types'

export type { FacultyKey, RelativeTime }

/* ------------------------------- People ------------------------------- */

export type ProfessionalStatus = 'employed' | 'entrepreneur' | 'academic' | 'public' | 'study'

export type Industry = 'keuangan' | 'teknologi' | 'kesehatan' | 'hukum' | 'pendidikan' | 'konsultasi' | 'manufaktur' | 'media' | 'publik' | 'bioteknologi'

export type HairStyle = 'short' | 'side' | 'curly' | 'receding' | 'bob' | 'long' | 'bun' | 'hijab'
export type Attire = 'suit' | 'blazer' | 'coat' | 'shirt' | 'batik' | 'turtleneck'
export type Backdrop = 'ember' | 'charcoal' | 'gold' | 'sand' | 'clay' | 'dusk'
export type Cloth = 'navy' | 'ink' | 'burgundy' | 'forest' | 'cream' | 'terracotta' | 'slate'

/** How the placeholder portrait is drawn (components/alumni/Portrait.tsx) while an entry has no `photo`. */
export interface AlumniLook {
  skin: 0 | 1 | 2 | 3
  hair: HairStyle
  hairTone: 'black' | 'brown' | 'grey'
  attire: Attire
  cloth: Cloth
  backdrop: Backdrop
  glasses?: boolean
  tie?: boolean
}

export interface ExperienceItem {
  role: string
  company: string
  /** e.g. "2019 – sekarang". */
  period: string
  note?: string
}

export interface EducationItem {
  /** e.g. "S1 Ilmu Hukum". */
  degree: string
  institution: string
  /** e.g. "2005 – 2009". */
  period: string
}

export interface AlumniAchievement {
  year: number
  title: string
}

export interface Alumnus {
  /** URL segment: /komunitas-alumni/direktori/:slug — lowercase, hyphenated, stable. */
  slug: string
  name: string
  /** Academic titles as written after the name, e.g. "S.H., LL.M.". */
  degree: string
  faculty: FacultyKey
  program: string
  entryYear: number
  gradYear: number
  position: string
  company: string
  /** Workplace as shown to visitors, e.g. "Jakarta" or "Singapura". */
  location: string
  industry: Industry
  status: ProfessionalStatus
  /** Open to mentoring requests (shown as a badge). */
  mentor?: boolean
  bio: string[]
  experience: ExperienceItem[]
  education: EducationItem[]
  achievements: AlumniAchievement[]
  /** Display-only handles. Fictional people have no real pages, so these never become outgoing links. */
  linkedin: string
  website?: string
  /** A real portrait URL. Sample entries have none, so an illustration is drawn from `look`. */
  photo?: string
  look: AlumniLook
}

/* ------------------------------- Events ------------------------------- */

export type EventType = 'gathering' | 'reuni' | 'career-talk' | 'networking' | 'seminar' | 'job-fair' | 'webinar' | 'mentoring'
export type EventMode = 'onsite' | 'online' | 'hybrid'
/** The brief's two states: an event that hasn't ended counts as upcoming. */
export type EventStatus = 'upcoming' | 'completed'

export interface AlumniEvent {
  /** Query value on the events page: ?acara=:slug opens its dialog. */
  slug: string
  title: string
  type: EventType
  start: RelativeTime
  end: RelativeTime
  /** Venue or online platform as shown to visitors. */
  venue: string
  mode: EventMode
  /** One or two sentences for cards. */
  excerpt: string
  description: string[]
  agenda?: Array<{ time: string; title: string }>
  /** Sample alumni speaking at the event (their profiles are linked). */
  speakers?: string[]
  capacity: number
  /** Seats already taken, for the sample "filled" figure. */
  taken: number
  /** Campus photo for on-campus venues; other events draw the type's motif. */
  photo?: PhotoKey
  /** Shown once the event is over. */
  recap?: string
}

/* ---------------------------- Opportunities --------------------------- */

export type OpportunityType = 'job' | 'internship' | 'trainee' | 'development' | 'partnership' | 'mentoring'
export type WorkType = 'fullTime' | 'partTime' | 'contract' | 'internship' | 'program'

export interface Company {
  /** Stable key, also the monogram source. */
  slug: string
  name: string
  /** Up to two words, drawn as the logo monogram. */
  short: string
  industry: Industry
  location: string
  /** Sample count of alumni working there. */
  alumniCount: number
  blurb: string
}

export interface Opportunity {
  /** Query value on the careers page: ?peluang=:slug opens its dialog. */
  slug: string
  title: string
  type: OpportunityType
  company: string
  location: string
  workType: WorkType
  /** Days before today it was published (negative). */
  postedDay: number
  /** Days from today applications close. */
  closesDay: number
  excerpt: string
  description: string[]
  requirements: string[]
  benefits: string[]
}

/* ------------------------------- Stories ------------------------------ */

export type StoryTheme = 'kepemimpinan' | 'wirausaha' | 'global' | 'sosial' | 'ilmu'

export interface AlumniStory {
  /** URL segment: /komunitas-alumni/kisah/:slug. */
  slug: string
  /** The person in the directory this story is about. */
  alumnus: string
  theme: StoryTheme
  headline: string
  /** The standfirst below the headline. */
  dek: string
  quote: string
  /** Full story, one string per paragraph. */
  body: string[]
  /** The pull quote is set after this paragraph index. */
  quoteAfter: number
  milestones: Array<{ year: number; title: string }>
  achievements: string[]
}

/* ---------------------------- Contributions --------------------------- */

export type ContributionKey = 'mentoring' | 'scholarship' | 'donation' | 'lecturer' | 'sharing' | 'industry'

export interface ContributionProgram {
  key: ContributionKey
  /** Current figure and the current cycle's target. Rupiah values are stored in billions. */
  value: number
  target: number
  unit: 'count' | 'rupiahBillions'
  title: string
  description: string
  /** What taking part looks like, shown in the dialog. */
  involvement: string[]
}

export interface Campaign {
  slug: string
  title: string
  description: string
  /** Rupiah, in billions. */
  raised: number
  goal: number
  donors: number
}

export interface Contributor {
  /** Directory slug. */
  alumnus: string
  kind: ContributionKey
  statement: string
}
