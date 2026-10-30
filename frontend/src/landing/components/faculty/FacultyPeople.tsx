import { Clock, Mail, UsersRound } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { FacultyPerson } from '../../data/faculties'
import { FacultySection, PendingInfo } from './FacultySection'
import { Portrait } from './DeanProfile'
import { FACULTY_SECTION_IDS } from './styles'

/** One official person: portrait (or neutral frame), name, title, position, profile and contact — only the fields that exist. */
export function PersonCard({ person }: { person: FacultyPerson }) {
  const { m } = useLandingI18n()
  const p = m.faculty.people
  return (
    <article className="flex h-full flex-col overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm">
      <Portrait person={person} label={p.photoPending} className="aspect-[4/3]" />
      <div className="flex flex-1 flex-col p-5">
        <p className="text-[0.8125rem] font-semibold text-lp-brand-text" lang="id">
          {person.position}
        </p>
        <h4 lang="id" className="lp-h4 mt-1 text-lp-fg">
          {person.name}
        </h4>
        {person.title && (
          <p lang="id" className="mt-0.5 text-[0.8125rem] text-lp-subtle">
            {person.title}
          </p>
        )}
        {person.responsibility && (
          <p lang="id" className="lp-small mt-3 text-lp-muted">
            <span className="font-semibold text-lp-fg">{p.responsibility}:</span> {person.responsibility}
          </p>
        )}
        {person.biography && (
          <p lang="id" className="lp-small mt-3 line-clamp-4 text-lp-muted">
            {person.biography}
          </p>
        )}
        {person.consultation && (
          <p lang="id" className="mt-3 flex items-start gap-2 text-[0.8125rem] text-lp-muted">
            <Clock size={14} aria-hidden className="mt-0.5 shrink-0 text-lp-subtle" />
            <span>
              <span className="font-semibold text-lp-fg">{p.consultation}:</span> {person.consultation}
            </span>
          </p>
        )}
        {person.email && (
          <a href={`mailto:${person.email}`} className="mt-auto inline-flex min-h-11 items-center gap-2 pt-4 text-[0.8125rem] font-semibold text-lp-brand-text underline underline-offset-4">
            <Mail size={14} aria-hidden />
            <span className="sr-only">{fill(p.emailFor, { name: person.name })}</span>
            <span aria-hidden>{person.email}</span>
          </a>
        )}
      </div>
    </article>
  )
}

const ROLE_ORDER = ['academic', 'faculty', 'organization'] as const

/** "Penasehat Fakultas", grouped by kind of guidance when the data says which; an empty state when there is no confirmed data. */
export function FacultyAdvisorSection({ advisors = [], tone }: { advisors?: FacultyPerson[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const a = m.faculty.advisors
  const groups = ROLE_ORDER.map((role) => ({ role, people: advisors.filter((x) => x.role === role) })).filter((g) => g.people.length > 0)
  const ungrouped = advisors.filter((x) => !x.role)

  return (
    <FacultySection id={FACULTY_SECTION_IDS.advisors} tone={tone} eyebrow={a.eyebrow} title={a.title} description={a.lead}>
      {advisors.length === 0 ? (
        <PendingInfo icon={UsersRound} title={a.pendingTitle} body={a.pendingBody} />
      ) : (
        <div className="space-y-10">
          {[...groups.map((g) => ({ key: g.role, title: a.roles[g.role], people: g.people })), ...(ungrouped.length ? [{ key: 'other', title: '', people: ungrouped }] : [])].map((group) => (
            <div key={group.key}>
              {group.title ? <h3 className="lp-h3 mb-5 text-lp-fg">{group.title}</h3> : <h3 className="sr-only">{a.title}</h3>}
              <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                {group.people.map((person) => (
                  <li key={`${person.name}-${person.position}`}>
                    <PersonCard person={person} />
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      )}
    </FacultySection>
  )
}

/** "Pengurus Fakultas": only roles the faculty has confirmed; otherwise an empty state. */
export function FacultyLeadershipGrid({ leadership = [], tone }: { leadership?: FacultyPerson[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const l = m.faculty.leadership

  return (
    <FacultySection id={FACULTY_SECTION_IDS.leadership} tone={tone} eyebrow={l.eyebrow} title={l.title} description={l.lead}>
      {leadership.length === 0 ? (
        <PendingInfo icon={UsersRound} title={l.pendingTitle} body={l.pendingBody} />
      ) : (
        <>
          <h3 className="sr-only">{l.title}</h3>
          <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {leadership.map((person) => (
              <li key={`${person.name}-${person.position}`}>
                <PersonCard person={person} />
              </li>
            ))}
          </ul>
        </>
      )}
    </FacultySection>
  )
}
