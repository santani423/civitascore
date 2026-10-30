import { ACTIVITIES } from './activities'
import { ORMAWA } from './ormawa'
import { UKM } from './ukm'
import type {
  Activity,
  ActivityCategory,
  ActivityStatus,
  Org,
  OrgKind,
  OrmawaCategory,
  RelativeTime,
  UkmCategory,
} from './types'

export type * from './types'
export { ORMAWA, UKM }

/** Display order of the filter chips. */
export const ACTIVITY_CATEGORIES: ActivityCategory[] = ['akademik', 'kompetisi', 'kepemimpinan', 'seni-budaya', 'olahraga', 'pengabdian', 'kerohanian']
export const ACTIVITY_STATUSES: ActivityStatus[] = ['upcoming', 'ongoing', 'completed']
export const ORMAWA_CATEGORIES: OrmawaCategory[] = ['eksekutif', 'legislatif', 'fakultas', 'himpunan', 'lainnya']
export const UKM_CATEGORIES: UkmCategory[] = ['olahraga', 'seni', 'musik', 'budaya', 'teknologi', 'penalaran', 'sosial', 'kerohanian']

/* ------------------------------ Activities ------------------------------ */

export interface ResolvedActivity extends Activity {
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

/** Sample activities with their relative dates turned into real ones (relative to the visitor's today). */
export const RESOLVED_ACTIVITIES: ResolvedActivity[] = ACTIVITIES.map((a) => ({ ...a, startsAt: at(a.start, today), endsAt: at(a.end, today) }))

export function activityStatus(activity: ResolvedActivity, now = new Date()): ActivityStatus {
  if (now < activity.startsAt) return 'upcoming'
  return now <= activity.endsAt ? 'ongoing' : 'completed'
}

/** Ongoing first, then upcoming (soonest first), then completed (most recent first). */
export function sortActivities(list: ResolvedActivity[], now = new Date()): ResolvedActivity[] {
  const rank: Record<ActivityStatus, number> = { ongoing: 0, upcoming: 1, completed: 2 }
  return [...list].sort((a, b) => {
    const sa = activityStatus(a, now)
    const sb = activityStatus(b, now)
    if (sa !== sb) return rank[sa] - rank[sb]
    const diff = a.startsAt.getTime() - b.startsAt.getTime()
    return sa === 'completed' ? -diff : diff
  })
}

export function findActivity(slug: string | undefined): ResolvedActivity | undefined {
  return RESOLVED_ACTIVITIES.find((a) => a.slug === slug)
}

/** Same category first, then same organizer, then whatever is nearest in time — never the activity itself. */
export function relatedActivities(activity: ResolvedActivity, count = 3): ResolvedActivity[] {
  const score = (a: ResolvedActivity) =>
    (a.category === activity.category ? 2 : 0) + (a.organizer.slug === activity.organizer.slug ? 1 : 0)
  return sortActivities(RESOLVED_ACTIVITIES.filter((a) => a.slug !== activity.slug))
    .map((a, i) => ({ a, s: score(a), i }))
    .sort((x, y) => y.s - x.s || x.i - y.i)
    .slice(0, count)
    .map((x) => x.a)
}

/** "YYYY-MM" of each month that has an activity starting in it, in calendar order. */
export function activityMonths(): string[] {
  return [...new Set(RESOLVED_ACTIVITIES.map((a) => monthKey(a.startsAt)))].sort()
}

export function monthKey(date: Date): string {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
}

/* ------------------------------ Ormawa & UKM ----------------------------- */

const ORGS: Record<OrgKind, Org[]> = { ormawa: ORMAWA, ukm: UKM }

export function orgsOf(kind: OrgKind): Org[] {
  return ORGS[kind]
}

export function findOrg(kind: OrgKind, slug: string | undefined): Org | undefined {
  return ORGS[kind].find((o) => o.slug === slug)
}

export function organizerOf(activity: Activity): Org | undefined {
  return findOrg(activity.organizer.kind, activity.organizer.slug)
}

export function activitiesOf(org: Org): ResolvedActivity[] {
  return sortActivities(RESOLVED_ACTIVITIES.filter((a) => a.organizer.kind === org.kind && a.organizer.slug === org.slug))
}

/** Same category first, then same faculty (Ormawa), then the list order. */
export function relatedOrgs(org: Org, count = 3): Org[] {
  const faculty = (o: Org) => (o.kind === 'ormawa' ? o.faculty : undefined)
  const score = (o: Org) => (o.category === org.category ? 2 : 0) + (faculty(org) && faculty(o) === faculty(org) ? 1 : 0)
  return ORGS[org.kind]
    .filter((o) => o.slug !== org.slug)
    .map((o, i) => ({ o, s: score(o), i }))
    .sort((x, y) => y.s - x.s || x.i - y.i)
    .slice(0, count)
    .map((x) => x.o)
}

/* --------------------------------- Search -------------------------------- */

function fold(text: string): string {
  return text.normalize('NFD').replace(/\p{Diacritic}/gu, '').toLowerCase()
}

/** Every word of the query appears somewhere in the given fields (case- and accent-insensitive). */
export function matchesQuery(query: string, ...fields: Array<string | undefined>): boolean {
  const words = fold(query).split(/\s+/).filter(Boolean)
  if (words.length === 0) return true
  const haystack = fold(fields.filter(Boolean).join(' '))
  return words.every((w) => haystack.includes(w))
}
