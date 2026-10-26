import { Quote } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import { BulletList } from '../student-affairs/Detail'
import { FacultySection } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

/** Official vision (prominent) and mission (list). Either one that hasn't been supplied is labelled as awaiting the official text. */
export function FacultyVisionMission({ vision, mission, tone }: { vision?: string; mission?: string[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const v = m.faculty.visionMission
  const hasMission = mission && mission.length > 0

  return (
    <FacultySection id={FACULTY_SECTION_IDS.visionMission} tone={tone} eyebrow={v.eyebrow} title={v.title} description={vision || hasMission ? undefined : v.pendingBody}>
      <div className="grid gap-5 lg:grid-cols-12">
        <figure className="relative overflow-hidden rounded-lp-xl bg-lp-band p-7 text-lp-band-fg sm:p-10 lg:col-span-7">
          <span aria-hidden data-lp-decor className="absolute -right-16 -top-16 h-64 w-64 rounded-full border-[26px] border-white/[0.06]" />
          <Quote size={34} aria-hidden className="text-lp-accent" />
          <figcaption className="mt-4 text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-lp-band-muted">{v.vision}</figcaption>
          {vision ? (
            <blockquote lang="id" className="mt-3 font-display text-[1.375rem] font-semibold leading-snug sm:text-[1.75rem]">
              {vision}
            </blockquote>
          ) : (
            <blockquote className="mt-3">
              <p className="font-display text-[1.25rem] font-semibold leading-snug text-lp-band-fg sm:text-[1.5rem]">{v.visionPending}</p>
              <p className="mt-3 text-[0.8125rem] font-semibold uppercase tracking-[0.12em] text-lp-accent">{m.faculty.pending.label}</p>
            </blockquote>
          )}
        </figure>
        <div className={hasMission ? 'rounded-lp-xl border border-lp-line bg-lp-surface p-7 sm:p-8 lg:col-span-5' : 'rounded-lp-xl border border-dashed border-lp-strong bg-lp-surface/70 p-7 sm:p-8 lg:col-span-5'}>
          <h3 className="text-[0.75rem] font-semibold uppercase tracking-[0.14em] text-lp-subtle">{v.mission}</h3>
          {hasMission ? (
            <BulletList items={mission} className="mt-5" />
          ) : (
            <>
              <p className="lp-h4 mt-4 text-lp-fg">{v.missionPending}</p>
              <p className="mt-2 text-[0.8125rem] font-semibold text-lp-gold-text">{m.faculty.pending.label}</p>
            </>
          )}
        </div>
      </div>
    </FacultySection>
  )
}
