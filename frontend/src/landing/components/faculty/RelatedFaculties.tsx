import { FACULTIES, type Faculty } from '../../data/faculties'
import type { ProgramsState } from '../../lib/pmbPrograms'
import { useLandingI18n } from '../../i18n'
import { LANDING_PATHS } from '../../paths'
import { RelatedSection } from '../student-affairs/Detail'
import { FacultyCard } from './FacultyCard'

/** "Fakultas Lainnya": the other five faculties. */
export function RelatedFaculties({ current, programs }: { current: Faculty; programs: ProgramsState }) {
  const { m } = useLandingI18n()
  return (
    <RelatedSection titleId="faculty-related" title={m.faculty.related.title} allTo={LANDING_PATHS.faculties} allLabel={m.faculty.related.all}>
      <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6">
        {FACULTIES.filter((f) => f.slug !== current.slug).map((faculty) => (
          <li key={faculty.slug} className="flex">
            <FacultyCard faculty={faculty} programs={programs} />
          </li>
        ))}
      </ul>
    </RelatedSection>
  )
}
