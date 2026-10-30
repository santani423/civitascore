import { useState } from 'react'
import { Mail, UserRound } from 'lucide-react'
import { cn } from '@/utils/cn'
import { fill, useLandingI18n } from '../../i18n'
import type { FacultyPerson } from '../../data/faculties'
import { BulletList } from '../student-affairs/Detail'
import { FacultySection } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

/** Official portrait, or a neutral frame (never a stand-in face) when there is none or it fails to load. */
export function Portrait({ person, className, label }: { person?: FacultyPerson; className?: string; label: string }) {
  const { m } = useLandingI18n()
  const [failed, setFailed] = useState(false)
  if (person?.photo && !failed) {
    return (
      <img
        src={person.photo}
        alt={fill(m.faculty.people.photoAlt, { name: person.name })}
        loading="lazy"
        decoding="async"
        onError={() => setFailed(true)}
        className={cn('w-full bg-lp-sunken object-cover', className)}
      />
    )
  }
  return (
    <div className={cn('flex w-full flex-col items-center justify-center gap-3 bg-lp-sunken text-lp-subtle', className)}>
      <UserRound size={44} strokeWidth={1.4} aria-hidden />
      <span className="px-4 text-center text-[0.8125rem] font-medium">{label}</span>
    </div>
  )
}

/** "Informasi Dekan": a full profile when the faculty has supplied one, otherwise the same frame with "Informasi dekan akan diperbarui." */
export function DeanProfile({ dean, tone }: { dean?: FacultyPerson; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const d = m.faculty.dean

  return (
    <FacultySection id={FACULTY_SECTION_IDS.dean} tone={tone} eyebrow={d.eyebrow} title={d.title}>
      <div className="grid overflow-hidden rounded-lp-xl border border-lp-line bg-lp-surface shadow-lp-md md:grid-cols-12">
        <Portrait person={dean} label={m.faculty.people.photoPending} className="aspect-[4/5] md:col-span-4 md:aspect-auto md:h-full md:min-h-[22rem]" />
        <div className="p-6 sm:p-8 md:col-span-8 lg:p-10">
          {dean ? (
            <>
              <p className="lp-eyebrow text-lp-brand-text">{dean.position}</p>
              <h3 lang="id" className="lp-h2 mt-3 text-lp-fg">
                {dean.name}
              </h3>
              {dean.title && (
                <p lang="id" className="mt-1 text-[0.9375rem] font-medium text-lp-subtle">
                  {dean.title}
                </p>
              )}
              {dean.biography && (
                <p lang="id" className="lp-body mt-5 max-w-2xl text-lp-muted">
                  {dean.biography}
                </p>
              )}
              <div className="mt-6 grid gap-6 sm:grid-cols-2">
                {dean.education && dean.education.length > 0 && (
                  <div>
                    <h4 className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{d.education}</h4>
                    <BulletList items={dean.education} className="mt-3" />
                  </div>
                )}
                {dean.interests && dean.interests.length > 0 && (
                  <div>
                    <h4 className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-subtle">{d.interests}</h4>
                    <ul lang="id" className="mt-3 flex flex-wrap gap-1.5">
                      {dean.interests.map((item) => (
                        <li key={item} className="rounded-full bg-lp-sunken px-2.5 py-1 text-[0.8125rem] font-medium text-lp-muted">
                          {item}
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
              </div>
              {dean.email && (
                <a href={`mailto:${dean.email}`} className="mt-6 inline-flex min-h-11 items-center gap-2 text-[0.9375rem] font-semibold text-lp-brand-text underline underline-offset-4">
                  <Mail size={16} aria-hidden />
                  {dean.email}
                </a>
              )}
            </>
          ) : (
            <div className="flex h-full flex-col justify-center">
              <p className="text-[0.75rem] font-semibold uppercase tracking-[0.12em] text-lp-gold-text">{m.faculty.pending.label}</p>
              <p className="lp-h3 mt-3 text-[1.5rem] text-lp-fg">{d.pendingTitle}</p>
              <p className="lp-body mt-3 max-w-xl text-lp-muted">{d.pendingBody}</p>
            </div>
          )}
        </div>
      </div>
    </FacultySection>
  )
}
