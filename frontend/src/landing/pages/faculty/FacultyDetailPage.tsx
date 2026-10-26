import { useMemo, type ReactNode } from 'react'
import { fill, useLandingI18n } from '../../i18n'
import { useDocumentMeta } from '../../hooks/useDocumentSetup'
import { findFaculty, isFacultyName, type Faculty } from '../../data/faculties'
import { ORMAWA, activitiesOf, findOrg, sortActivities, type Org } from '../../data/student-affairs'
import { usePmbPrograms } from '../../lib/pmbPrograms'
import { LANDING_PATHS, detailPath } from '../../paths'
import { StickySectionNav } from '../../components/layout/StickySectionNav'
import { NotFoundState } from '../../components/student-affairs/Detail'
import { FacultyHero, FACULTY_HERO_ID } from '../../components/faculty/FacultyHero'
import { FacultyOverview } from '../../components/faculty/FacultyOverview'
import { DeanProfile } from '../../components/faculty/DeanProfile'
import { FacultyVisionMission } from '../../components/faculty/FacultyVisionMission'
import { StudyProgramGrid } from '../../components/faculty/StudyProgramGrid'
import { FacultyAdvisorSection, FacultyLeadershipGrid } from '../../components/faculty/FacultyPeople'
import { FacultyActivitiesSection, FacultyOrmawaSection, FacultyUkmSection } from '../../components/faculty/FacultyStudentLife'
import { FacultyAchievements, type FacultyAchievement } from '../../components/faculty/FacultyAchievements'
import { FacultyFacilitiesGallery } from '../../components/faculty/FacultyFacilitiesGallery'
import { FacultyContact } from '../../components/faculty/FacultyContact'
import { RelatedFaculties } from '../../components/faculty/RelatedFaculties'
import { FACULTY_SECTION_IDS as IDS } from '../../components/faculty/styles'

const ACTIVITY_LIMIT = 6

/** /fakultas/:slug — one template for all six faculties; unknown slugs get a not-found state. */
export function FacultyDetailPage({ slug }: { slug: string }) {
  const { m } = useLandingI18n()
  const faculty = findFaculty(slug)
  const nf = m.faculty.notFound

  useDocumentMeta({
    title: fill(m.meta.pageTitle, { page: faculty ? faculty.name : nf.title }),
    description: faculty ? (faculty.tagline ?? fill(m.faculty.metaDescription, { name: faculty.name })) : nf.body,
    ogLocale: m.meta.ogLocale,
    path: detailPath(LANDING_PATHS.faculties, slug),
    robots: faculty ? undefined : 'noindex',
  })

  if (!faculty) {
    return (
      <NotFoundState
        crumbs={[{ label: m.nav.home, to: LANDING_PATHS.home }, { label: m.nav.faculties, to: LANDING_PATHS.faculties }, { label: nf.title }]}
        title={nf.title}
        body={nf.body}
        backTo={LANDING_PATHS.faculties}
        backLabel={nf.back}
      />
    )
  }
  return <FacultyProfile faculty={faculty} />
}

function FacultyProfile({ faculty }: { faculty: Faculty }) {
  const { m } = useLandingI18n()
  const s = m.faculty.sections
  const programsState = usePmbPrograms()
  const programs = programsState.status === 'ready' ? programsState.items.filter((p) => isFacultyName(faculty, p.faculty)) : []

  const { ormawa, ukm, activities, achievements } = useMemo(() => {
    const ormawa: Org[] = ORMAWA.filter((o) => o.faculty === faculty.slug)
    const ukm = faculty.ukmSuggestions.map((slug) => findOrg('ukm', slug)).filter((o): o is Org => o !== undefined)
    const activities = sortActivities(ormawa.flatMap((o) => activitiesOf(o))).slice(0, ACTIVITY_LIMIT)
    const achievements: FacultyAchievement[] = ormawa.flatMap((org) => (org.achievements ?? []).map((a) => ({ ...a, org }))).sort((x, y) => y.year - x.year)
    return { ormawa, ukm, activities, achievements }
  }, [faculty])

  // Sections in page order; optional ones only when they have content. Backgrounds alternate down the list.
  const sections: Array<{ id: string; label: string; render: (tone: 'canvas' | 'warm') => ReactNode } | null> = [
    { id: IDS.about, label: s.about, render: (tone) => <FacultyOverview faculty={faculty} tone={tone} /> },
    { id: IDS.dean, label: s.dean, render: (tone) => <DeanProfile dean={faculty.dean} tone={tone} /> },
    { id: IDS.visionMission, label: s.visionMission, render: (tone) => <FacultyVisionMission vision={faculty.vision} mission={faculty.mission} tone={tone} /> },
    { id: IDS.programs, label: s.programs, render: (tone) => <StudyProgramGrid state={programsState.status} programs={programs} tone={tone} /> },
    { id: IDS.advisors, label: s.advisors, render: (tone) => <FacultyAdvisorSection advisors={faculty.advisors} tone={tone} /> },
    { id: IDS.leadership, label: s.leadership, render: (tone) => <FacultyLeadershipGrid leadership={faculty.leadership} tone={tone} /> },
    { id: IDS.ormawa, label: s.ormawa, render: (tone) => <FacultyOrmawaSection facultySlug={faculty.slug} ormawa={ormawa} tone={tone} /> },
    ukm.length > 0 ? { id: IDS.ukm, label: s.ukm, render: (tone) => <FacultyUkmSection ukm={ukm} tone={tone} /> } : null,
    { id: IDS.activities, label: s.activities, render: (tone) => <FacultyActivitiesSection activities={activities} tone={tone} /> },
    achievements.length > 0 ? { id: IDS.achievements, label: s.achievements, render: (tone) => <FacultyAchievements items={achievements} tone={tone} /> } : null,
    { id: IDS.facilities, label: s.facilities, render: (tone) => <FacultyFacilitiesGallery faculty={faculty} tone={tone} /> },
    { id: IDS.contact, label: s.contact, render: (tone) => <FacultyContact faculty={faculty} tone={tone} /> },
  ]
  const shown = sections.filter((x) => x !== null)
  const rendered = shown.map((section, i) => ({ id: section.id, node: section.render(i % 2 === 0 ? 'canvas' : 'warm') }))
  // Ormawa → activities (→ achievements) are consecutive; they share the anchor that "Kenali Kehidupan Mahasiswa" scrolls to.
  const lifeIds: string[] = [IDS.ormawa, IDS.ukm, IDS.activities, IDS.achievements]
  const first = rendered.findIndex((x) => lifeIds.includes(x.id))
  const last = rendered.findLastIndex((x) => lifeIds.includes(x.id))
  const block = (list: typeof rendered) => list.map((x) => <div key={x.id}>{x.node}</div>)

  return (
    <article aria-labelledby="faculty-title">
      <FacultyHero faculty={faculty} programCount={programsState.status === 'ready' ? programs.length : null} />
      {/* The section nav sticks only within this wrapper. */}
      <div>
        <StickySectionNav
          label={m.faculty.sectionNav}
          path={detailPath(LANDING_PATHS.faculties, faculty.slug)}
          items={shown.map(({ id, label }) => ({ id, label }))}
          watchAlso={[FACULTY_HERO_ID]}
        />
        {block(rendered.slice(0, first))}
        {/* "Kenali Kehidupan Mahasiswa" scrolls here. */}
        <div id={IDS.studentLife} className="scroll-mt-16">
          {block(rendered.slice(first, last + 1))}
        </div>
        {block(rendered.slice(last + 1))}
      </div>
      <RelatedFaculties current={faculty} programs={programsState} />
    </article>
  )
}
