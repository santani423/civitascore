import { Link } from 'react-router-dom'
import { Medal } from 'lucide-react'
import { useLandingI18n } from '../../i18n'
import type { Achievement, Org } from '../../data/student-affairs'
import { ORG_LIST_PATHS, detailPath } from '../../paths'
import { DemoBadge } from '../student-affairs/Badges'
import { FacultySection } from './FacultySection'
import { FACULTY_SECTION_IDS } from './styles'

export interface FacultyAchievement extends Achievement {
  org: Org
}

/** Sample achievements of the faculty's sample Ormawa. Render only when there are some (the page hides the section otherwise). */
export function FacultyAchievements({ items, tone }: { items: FacultyAchievement[]; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const a = m.faculty.achievements
  return (
    <FacultySection id={FACULTY_SECTION_IDS.achievements} tone={tone} eyebrow={a.eyebrow} title={a.title} description={a.lead}>
      <ul className="grid gap-4 md:grid-cols-2">
        {items.map((item) => (
          <li key={`${item.org.slug}-${item.title}`} className="flex items-start gap-4 rounded-lp-lg border border-lp-line bg-lp-surface p-5 shadow-lp-sm sm:p-6">
            <span aria-hidden className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-lp-gold/15 text-lp-gold-text">
              <Medal size={20} />
            </span>
            <div className="min-w-0 flex-1">
              <div className="flex flex-wrap items-center gap-2">
                <span className="lp-tabular text-[0.8125rem] font-semibold text-lp-brand-text">{item.year}</span>
                <DemoBadge />
              </div>
              <p lang="id" className="mt-1.5 text-[0.9375rem] font-semibold text-lp-fg">
                {item.title}
              </p>
              <p className="mt-1 text-[0.8125rem] text-lp-muted">
                {a.by}{' '}
                <Link to={detailPath(ORG_LIST_PATHS[item.org.kind], item.org.slug)} lang="id" className="font-semibold text-lp-brand-text underline underline-offset-2">
                  {item.org.name}
                </Link>
              </p>
            </div>
          </li>
        ))}
      </ul>
    </FacultySection>
  )
}
