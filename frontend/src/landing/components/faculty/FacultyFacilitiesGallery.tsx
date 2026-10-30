import { useState } from 'react'
import { Building } from 'lucide-react'
import { fill, useLandingI18n } from '../../i18n'
import type { Faculty, FacultyFacility } from '../../data/faculties'
import { CoverArt } from '../student-affairs/Covers'
import { PhotoGallery } from '../student-affairs/PhotoGallery'
import { FacultySection, PendingInfo } from './FacultySection'
import { FACULTY_SECTION_IDS, FACULTY_STYLE } from './styles'

function FacilityCard({ faculty, facility }: { faculty: Faculty; facility: FacultyFacility }) {
  const { m } = useLandingI18n()
  const [failed, setFailed] = useState(false)
  return (
    <article className="flex h-full flex-col overflow-hidden rounded-lp-lg border border-lp-line bg-lp-surface shadow-lp-sm">
      {facility.photo && !failed ? (
        <img src={facility.photo} alt={fill(m.faculty.facilities.photoAlt, { name: facility.name })} loading="lazy" decoding="async" onError={() => setFailed(true)} className="aspect-[16/10] w-full object-cover" />
      ) : (
        <CoverArt style={{ ...FACULTY_STYLE[faculty.slug], icon: Building }} className="aspect-[16/10]" />
      )}
      <div className="p-5">
        <h4 lang="id" className="lp-h4 text-lp-fg">
          {facility.name}
        </h4>
        {facility.description && (
          <p lang="id" className="lp-small mt-2 text-lp-muted">
            {facility.description}
          </p>
        )}
      </div>
    </article>
  )
}

/**
 * "Fasilitas dan Kehidupan Kampus": the faculty's own facilities when
 * supplied (otherwise an awaiting-information state), then photos of the
 * university campus — captioned as such, never as this faculty's facilities.
 */
export function FacultyFacilitiesGallery({ faculty, tone }: { faculty: Faculty; tone?: 'canvas' | 'warm' }) {
  const { m } = useLandingI18n()
  const f = m.faculty.facilities
  const facilities = faculty.facilities ?? []

  return (
    <FacultySection id={FACULTY_SECTION_IDS.facilities} tone={tone} eyebrow={f.eyebrow} title={f.title}>
      <h3 className="lp-h3 mb-5 text-lp-fg">{f.facultyTitle}</h3>
      {facilities.length > 0 ? (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {facilities.map((facility) => (
            <li key={facility.name}>
              <FacilityCard faculty={faculty} facility={facility} />
            </li>
          ))}
        </ul>
      ) : (
        <PendingInfo icon={Building} title={f.pendingTitle} body={f.pendingBody} />
      )}

      <h3 className="lp-h3 mb-5 mt-12 text-lp-fg">{f.campusTitle}</h3>
      <PhotoGallery photos={['campusFacade', 'campusTower', 'grotto']} caption={f.campusNote} columns={3} />
    </FacultySection>
  )
}
