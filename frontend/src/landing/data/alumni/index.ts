import { ALUMNI } from './people'
import { ALUMNI_EVENTS } from './events'
import { COMPANIES, OPPORTUNITIES } from './opportunities'
import { ALUMNI_STORIES } from './stories'
import type { Alumnus, AlumniEvent, Company, EventStatus, EventType, Industry, Opportunity, OpportunityType, RelativeTime, StoryTheme, WorkType, ProfessionalStatus } from './types'

export type * from './types'
export { ALUMNI, ALUMNI_EVENTS, ALUMNI_STORIES, COMPANIES, OPPORTUNITIES }
export { CAMPAIGNS, CONTRIBUTION_PROGRAMS, CONTRIBUTORS, DONATION_BY_FACULTY, DONATION_BY_YEAR, MENTORING_HOURS_BY_YEAR } from './contributions'

/** Display order of filters and tabs. */
export const INDUSTRIES: Industry[] = ['keuangan', 'teknologi', 'kesehatan', 'hukum', 'pendidikan', 'konsultasi', 'manufaktur', 'media', 'publik', 'bioteknologi']
export const PROFESSIONAL_STATUSES: ProfessionalStatus[] = ['employed', 'entrepreneur', 'academic', 'public', 'study']
export const EVENT_TYPES: EventType[] = ['gathering', 'reuni', 'career-talk', 'networking', 'seminar', 'job-fair', 'webinar', 'mentoring']
export const EVENT_STATUSES: EventStatus[] = ['upcoming', 'completed']
export const OPPORTUNITY_TYPES: OpportunityType[] = ['job', 'internship', 'trainee', 'development', 'partnership', 'mentoring']
export const WORK_TYPES: WorkType[] = ['fullTime', 'partTime', 'contract', 'internship', 'program']
export const STORY_THEMES: StoryTheme[] = ['kepemimpinan', 'wirausaha', 'global', 'sosial', 'ilmu']

/* ------------------------------ Headline figures ------------------------------ */

/** Sample community totals. Employed + entrepreneurs stays within registered, and the breakdowns below add up to them. */
export const ALUMNI_STATS = { total: 48_250, registered: 31_420, employed: 24_870, entrepreneurs: 4_930, countries: 38 }

/** Employed + entrepreneurs by industry (sums to 29.800); the tail is folded into `other`. */
export const INDUSTRY_BREAKDOWN: Array<{ key: Industry | 'other'; value: number }> = [
  { key: 'keuangan', value: 6_120 },
  { key: 'teknologi', value: 5_240 },
  { key: 'kesehatan', value: 4_870 },
  { key: 'hukum', value: 3_410 },
  { key: 'pendidikan', value: 3_160 },
  { key: 'konsultasi', value: 2_680 },
  { key: 'other', value: 4_320 },
]

/** The largest communities outside Indonesia (names are content, so Indonesian in every language). */
export const COUNTRY_BREAKDOWN = [
  { country: 'Singapura', value: 412 },
  { country: 'Australia', value: 356 },
  { country: 'Belanda', value: 198 },
  { country: 'Amerika Serikat', value: 176 },
  { country: 'Jepang', value: 143 },
  { country: 'Jerman', value: 96 },
]

/* ----------------------------------- Search ----------------------------------- */

function fold(text: string): string {
  return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}

/** Every word of the query appears somewhere in the given fields (case- and accent-insensitive). */
export function matchesQuery(query: string, ...fields: Array<string | undefined>): boolean {
  const words = fold(query).split(/\s+/).filter(Boolean)
  if (words.length === 0) return true
  const haystack = fold(fields.filter(Boolean).join(' '))
  return words.every((word) => haystack.includes(word))
}

/* ---------------------------------- Directory --------------------------------- */

export function findAlumnus(slug: string | undefined): Alumnus | undefined {
  return ALUMNI.find((a) => a.slug === slug)
}

/** Featured on the overview page. */
export const SPOTLIGHT_ALUMNI = ['maria-clarissa-wibisono', 'gregorius-bima-santoso', 'theresia-anindya-putri', 'stefanus-aditya-nugroho'].map((slug) => findAlumnus(slug)).filter((a): a is Alumnus => a !== undefined)

/** Programs seen in the sample data, optionally only one faculty's. */
export function alumniPrograms(faculty?: string): string[] {
  return [...new Set(ALUMNI.filter((a) => !faculty || a.faculty === faculty).map((a) => a.program))].sort((a, b) => a.localeCompare(b, 'id'))
}

/** Graduation years seen in the sample data, newest first. */
export function alumniYears(): number[] {
  return [...new Set(ALUMNI.map((a) => a.gradYear))].sort((a, b) => b - a)
}

export type AlumniSort = 'name' | 'name-desc' | 'newest' | 'oldest'
export const ALUMNI_SORTS: AlumniSort[] = ['name', 'name-desc', 'newest', 'oldest']

export function sortAlumni(list: Alumnus[], sort: AlumniSort): Alumnus[] {
  const byName = (a: Alumnus, b: Alumnus) => a.name.localeCompare(b.name, 'id')
  return [...list].sort((a, b) => {
    if (sort === 'name-desc') return byName(b, a)
    if (sort === 'newest') return b.gradYear - a.gradYear || byName(a, b)
    if (sort === 'oldest') return a.gradYear - b.gradYear || byName(a, b)
    return byName(a, b)
  })
}

/** Same faculty first, then same industry — never the person themselves. */
export function relatedAlumni(person: Alumnus, count = 4): Alumnus[] {
  const score = (a: Alumnus) => (a.faculty === person.faculty ? 2 : 0) + (a.industry === person.industry ? 1 : 0)
  return ALUMNI.filter((a) => a.slug !== person.slug)
    .map((a, i) => ({ a, s: score(a), i }))
    .sort((x, y) => y.s - x.s || x.i - y.i)
    .slice(0, count)
    .map((x) => x.a)
}

/* ----------------------------------- Events ----------------------------------- */

export interface ResolvedEvent extends AlumniEvent {
  startsAt: Date
  endsAt: Date
}

function at({ day, time }: RelativeTime, today: Date): Date {
  const [hours, minutes] = time.split(':').map(Number)
  const date = new Date(today)
  date.setDate(date.getDate() + day)
  date.setHours(hours, minutes, 0, 0)
  return date
}

const today = new Date()
today.setHours(0, 0, 0, 0)

export const RESOLVED_EVENTS: ResolvedEvent[] = ALUMNI_EVENTS.map((event) => ({ ...event, startsAt: at(event.start, today), endsAt: at(event.end, today) }))

export function eventStatus(event: ResolvedEvent, now = new Date()): EventStatus {
  return now <= event.endsAt ? 'upcoming' : 'completed'
}

/** Upcoming first (soonest first), then completed (most recent first). */
export function sortEvents(list: ResolvedEvent[]): ResolvedEvent[] {
  return [...list].sort((a, b) => {
    const sa = eventStatus(a)
    const sb = eventStatus(b)
    if (sa !== sb) return sa === 'upcoming' ? -1 : 1
    const diff = a.startsAt.getTime() - b.startsAt.getTime()
    return sa === 'completed' ? -diff : diff
  })
}

export function findEvent(slug: string | undefined): ResolvedEvent | undefined {
  return RESOLVED_EVENTS.find((event) => event.slug === slug)
}

export function upcomingEvents(): ResolvedEvent[] {
  return sortEvents(RESOLVED_EVENTS).filter((event) => eventStatus(event) === 'upcoming')
}

/** Whole days from today until the date (0 = today). */
export function daysUntil(date: Date): number {
  const target = new Date(date)
  target.setHours(0, 0, 0, 0)
  return Math.round((target.getTime() - today.getTime()) / 86_400_000)
}

/* -------------------------------- Opportunities ------------------------------- */

export interface ResolvedOpportunity extends Opportunity {
  postedAt: Date
  closesAt: Date
  employer: Company
}

function dayFromToday(day: number): Date {
  const date = new Date(today)
  date.setDate(date.getDate() + day)
  return date
}

function companyOf(slug: string): Company {
  const company = COMPANIES.find((c) => c.slug === slug)
  if (!company) throw new Error(`Unknown sample company: ${slug}`)
  return company
}

/** Newest first. */
export const RESOLVED_OPPORTUNITIES: ResolvedOpportunity[] = OPPORTUNITIES.map((o) => ({
  ...o,
  postedAt: dayFromToday(o.postedDay),
  closesAt: dayFromToday(o.closesDay),
  employer: companyOf(o.company),
})).sort((a, b) => b.postedAt.getTime() - a.postedAt.getTime())

export function findOpportunity(slug: string | undefined): ResolvedOpportunity | undefined {
  return RESOLVED_OPPORTUNITIES.find((o) => o.slug === slug)
}

export function openingsAt(company: Company): number {
  return OPPORTUNITIES.filter((o) => o.company === company.slug).length
}

/* ----------------------------------- Stories ---------------------------------- */

export function findStory(slug: string | undefined) {
  return ALUMNI_STORIES.find((story) => story.slug === slug)
}

export function storyOf(person: Alumnus) {
  return ALUMNI_STORIES.find((story) => story.alumnus === person.slug)
}

/** Reading time at about 200 words per minute, at least one minute. */
export function readingMinutes(body: string[]): number {
  return Math.max(1, Math.round(body.join(' ').split(/\s+/).length / 200))
}
